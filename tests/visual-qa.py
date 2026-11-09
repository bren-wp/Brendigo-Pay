#!/usr/bin/env python3
"""Offline screenshot QA: Chromium is not permitted to fetch localhost in this environment.
We fetch PHP pages with urllib, then render their real HTML/CSS/JS with set_content.
This validates layout but does not substitute for live WooCommerce/browser integration tests.
"""
from pathlib import Path
import tempfile, subprocess, shutil, socket, re, os, urllib.request, urllib.parse, http.cookiejar, time, base64
from playwright.sync_api import sync_playwright

ROOT=Path(__file__).resolve().parents[1]
OUTPUT=Path(os.environ.get('BP_VISUAL_QA_DIR', str(ROOT/'visual-qa')))
OUTPUT.mkdir(parents=True,exist_ok=True)
with tempfile.TemporaryDirectory(prefix='brendigo-qa-') as td:
    src=ROOT/'platform'; dest=Path(td)/'platform'
    shutil.copytree(src,dest,ignore=shutil.ignore_patterns('config.php','*.json','*.lock'))
    with socket.socket() as sock:
        sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]
    base=f'http://127.0.0.1:{port}'
    subprocess.run(['php','bin/setup.php',base],cwd=dest,check=True,capture_output=True)
    server=subprocess.Popen(['php','-S',f'127.0.0.1:{port}','router.php'],cwd=dest,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    jar=http.cookiejar.CookieJar()
    opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    def fetch(path,values=None):
        data=urllib.parse.urlencode(values).encode() if values else None
        with opener.open(base+path,data=data,timeout=10) as resp:
            return resp.read().decode('utf-8')
    def render(page,html,js=True):
        icon=base64.b64encode((dest/'public/assets/brand-mark.svg').read_bytes()).decode()
        html=html.replace('src="/assets/brand-mark.svg"',f'src="data:image/svg+xml;base64,{icon}"')
        html=re.sub(r'<link[^>]+href="/assets/app.css"[^>]*>','',html)
        html=re.sub(r'<script[^>]+src="/assets/app.js"[^>]*></script>','',html)
        page.set_content(html,wait_until='load')
        page.add_style_tag(content=(dest/'public/assets/app.css').read_text())
        if js: page.add_script_tag(content=(dest/'public/assets/app.js').read_text())
        return page.evaluate('document.documentElement.scrollWidth')
    try:
        for _ in range(40):
            try: fetch('/api/v1/health');break
            except Exception: time.sleep(.1)
        for width,height,label in [(1448,1086,'desktop'),(768,1024,'tablet'),(390,844,'mobile')]:
            if os.environ.get('BP_VISUAL_ONLY','desktop') != label: continue
            jar.clear()
            with sync_playwright() as pp:
                browser=pp.chromium.launch(**({'executable_path':'/usr/bin/chromium'} if Path('/usr/bin/chromium').is_file() else {}),headless=True,args=['--no-sandbox'])
                context=browser.new_context(viewport={'width':width,'height':height},device_scale_factor=1)
                page=context.new_page()
                errors=[]
                page.on('pageerror',lambda err:errors.append(str(err)))
                for path,name in [('/','home'),('/pricing','pricing'),('/plugin','plugin'),('/docs','docs'),('/login','login')]:
                    html=fetch(path)
                    scroll=render(page,html)
                    assert page.locator('h1').count()>=1,(name,'heading missing')
                    assert scroll<=width+2,(name,label,'horizontal overflow',scroll)
                    if name == 'home':
                        for tier in ('start', 'business', 'pro'):
                            assert page.locator('.bp-home-plan-list a[href="/register?plan='+tier+'"]').count() == 1
                        assert page.locator('.bp-home-wordpress-card .bp-wp-decoration').count() == 1
                    if name == 'pricing':
                        # Both options must update card and comparison-table amounts,
                        # and the 12-month estimate must disclose that it is not a paid contract.
                        assert page.locator('[data-billing-period]').count() == 2
                        page.locator('[data-billing-period="yearly"]').click()
                        assert '598,80' in page.locator('.bp-price-featured .bp-price-value').inner_text()
                        assert page.locator('[data-billing-disclaimer]').is_visible()
                        assert 'Godišnji' in page.locator('[data-billing-price-label]').inner_text()
                        assert page.locator('[data-billing-period="yearly"]').get_attribute('aria-pressed') == 'true'
                        page.locator('[data-billing-period="monthly"]').click()
                        assert '49,90' in page.locator('.bp-price-featured .bp-price-value').inner_text()
                        assert not page.locator('[data-billing-disclaimer]').is_visible()
                    if label in ('desktop','mobile'):
                        page.screenshot(path=str(OUTPUT/f'{name}-{label}.png'),full_page=True)
                    if label == 'desktop' and name in ('home','pricing'):
                        # Confirm actual CTA lives within the 1086 px reference capture.
                        banner = page.locator('.bp-bottom-cta')
                        assert banner.count() == 1, (name, 'single real CTA required')
                        bounds = banner.bounding_box()
                        assert bounds is not None and bounds['y'] < 1040 and bounds['width'] >= 0.8 * width, (
                            name, 'CTA slipped below reference viewport', bounds)
                        assert banner.locator('.bp-cta-actions a[href]').count() == 2, (name, 'functional CTA links')
                        assert banner.locator('.bp-cta-payment-card').count() == 1
                        if name == 'pricing':
                            assert page.locator('.bp-price-cards article').count() == 3
                        page.screenshot(path=str(OUTPUT/('09-home.png' if name=='home' else '08-pricing.png')),full_page=False)
                html=fetch('/register');csrf=re.search('name="csrf" value="([a-f0-9]+)"',html).group(1)
                # Register a test-only merchant, then activate a sandbox entitlement/site
                # in this temporary fixture. Never bypass paid-plan checks in production code.
                fetch('/register', {'name':'Visual Merchant','email':'visual@example.com',
                                    'password':'visual-qa-long-password-123',
                                    'requested_tier':'business', 'website':base,'csrf':csrf})
                fixture = r'''
require "src/JsonStore.php";
require "src/Support.php";
require "src/Plans.php";
require "src/SiteLicenses.php";
$config = require "config.php";
$store = new \BrendigoPay\JsonStore($config["data_dir"]);
$matches = array_values(array_filter($store->all("users"),
    static fn($u) => ($u["email"] ?? "") === "visual@example.com"));
if (count($matches) !== 1) { throw new \RuntimeException("Visual test merchant unavailable"); }
$merchant = $matches[0];
$plans = new \BrendigoPay\Plans($store);
$plans->grant($merchant["id"], "business", gmdate("c", time() + 60 * 86400), $merchant["id"]);
$licenses = new \BrendigoPay\SiteLicenses($store, $plans, true);
$issued = $licenses->issue($merchant["id"], getenv("BP_QA_BASE"));
$licenses->activate($issued["license"], getenv("BP_QA_BASE"), str_repeat("a", 32));
echo $issued["site"]["id"];
'''
                test_env = dict(os.environ, BP_QA_BASE=base)
                fixture_run = subprocess.run(['php','-r',fixture], cwd=dest,
                                             env=test_env,check=True,capture_output=True,text=True)
                site_id = fixture_run.stdout.strip()
                assert re.fullmatch('[a-f0-9]{24}',site_id), 'Sandbox fixture did not activate a site'
                html=fetch('/dashboard')
                scroll=render(page,html)
                assert page.locator('h1').inner_text().strip()=='Pregled'
                assert 'Potvrđene sandbox uplate' in page.content()
                assert scroll<=width+2,('dashboard',label,'overflow',scroll)
                if label in ('desktop','mobile'):
                    page.screenshot(path=str(OUTPUT/f'dashboard-{label}.png'),full_page=True)
                if label == 'desktop':
                    page.screenshot(path=str(OUTPUT/'06-merchant-dashboard.png'),full_page=False)
                    render(page,fetch('/dashboard?tab=transactions'))
                    page.screenshot(path=str(OUTPUT/'04-transactions.png'),full_page=False)
                    render(page,html)
                page.locator('[data-open-dialog="bp-new-link"]').click()
                assert page.locator('dialog#bp-new-link').evaluate('(x)=>x.open')
                page.locator('[name="title"]').fill('Usluga testiranja')
                page.locator('[name="amount"]').fill('49,90')
                assert '49,90' in page.locator('[data-live-amount]').inner_text()
                if label in ('desktop','mobile'):
                    page.screenshot(path=str(OUTPUT/f'payment-dialog-{label}.png'))
                if label == 'desktop':
                    page.screenshot(path=str(OUTPUT/'05-payment-link-modal.png'),full_page=False)
                csrf=re.search('name="csrf" value="([a-f0-9]+)"',html).group(1)
                html=fetch('/dashboard/links',{'site_id':site_id,'title':'Usluga testiranja','description':'Testna usluga','amount':'49,90','csrf':csrf})
                link_id=re.search('/p/([a-f0-9]{24})',html).group(1)
                checkout=fetch('/p/'+link_id)
                scroll=render(page,checkout)
                assert page.locator('.bp-ref-checkout').count()==1
                assert scroll<=width+2,('checkout',label,'overflow',scroll)
                assert page.locator('[name="card_number"]').count()==0
                if label in ('desktop','mobile'):
                    page.screenshot(path=str(OUTPUT/f'checkout-{label}.png'),full_page=True)
                if label == 'desktop':
                    page.screenshot(path=str(OUTPUT/'07-payment-checkout.png'),full_page=False)
                assert not errors,(label,errors)
                print('PASS',label,'5 public routes, dashboard, live modal and checkout; no overflow or JS errors')
                context.close();browser.close()
    finally:
        server.terminate();server.wait(timeout=10)
