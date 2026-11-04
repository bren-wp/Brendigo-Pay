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
                browser=pp.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
                context=browser.new_context(viewport={'width':width,'height':height},device_scale_factor=1)
                page=context.new_page()
                errors=[]
                page.on('pageerror',lambda err:errors.append(str(err)))
                for path,name in [('/','home'),('/pricing','pricing'),('/plugin','plugin'),('/docs','docs'),('/login','login')]:
                    html=fetch(path)
                    scroll=render(page,html)
                    assert page.locator('h1').count()>=1,(name,'heading missing')
                    assert scroll<=width+2,(name,label,'horizontal overflow',scroll)
                    if label in ('desktop','mobile'):
                        page.screenshot(path=str(OUTPUT/f'{name}-{label}.png'),full_page=True)
                html=fetch('/register');csrf=re.search('name="csrf" value="([a-f0-9]+)"',html).group(1)
                html=fetch('/register',{'name':'Visual Merchant','email':'visual@example.com','password':'visual-qa-long-password-123','csrf':csrf})
                scroll=render(page,html)
                assert page.locator('h1').inner_text().strip()=='Pregled'
                assert 'Potvrđene sandbox uplate' in page.content()
                assert scroll<=width+2,('dashboard',label,'overflow',scroll)
                if label in ('desktop','mobile'):
                    page.screenshot(path=str(OUTPUT/f'dashboard-{label}.png'),full_page=True)
                page.locator('[data-open-dialog="bp-new-link"]').click()
                assert page.locator('dialog#bp-new-link').evaluate('(x)=>x.open')
                page.locator('[name="title"]').fill('Usluga testiranja')
                page.locator('[name="amount"]').fill('49,90')
                assert '49,90' in page.locator('[data-live-amount]').inner_text()
                if label in ('desktop','mobile'):
                    page.screenshot(path=str(OUTPUT/f'payment-dialog-{label}.png'))
                csrf=re.search('name="csrf" value="([a-f0-9]+)"',html).group(1)
                html=fetch('/dashboard/links',{'title':'Usluga testiranja','description':'Testna usluga','amount':'49,90','csrf':csrf})
                link_id=re.search('/p/([a-f0-9]{24})',html).group(1)
                checkout=fetch('/p/'+link_id)
                scroll=render(page,checkout)
                assert page.locator('.bp-ref-checkout').count()==1
                assert scroll<=width+2,('checkout',label,'overflow',scroll)
                assert page.locator('[name="card_number"]').count()==0
                if label in ('desktop','mobile'):
                    page.screenshot(path=str(OUTPUT/f'checkout-{label}.png'),full_page=True)
                assert not errors,(label,errors)
                print('PASS',label,'5 public routes, dashboard, live modal and checkout; no overflow or JS errors')
                context.close();browser.close()
    finally:
        server.terminate();server.wait(timeout=10)
