#!/usr/bin/env python3
"""Offline structural UI contracts; this is NOT a visual 1:1 verification."""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
VERSION = '0.8.12'
wp = ROOT / 'wordpress-plugin' / 'brendigo-pay'
required = [
    'assets/public.css', 'assets/admin.css', 'assets/editor.css',
    'assets/block.js', 'assets/woocommerce-block.js',
    'includes/class-bp-gateway.php', 'brendigo-pay.php',
    'readme.txt', 'views/admin-settings.php',
]
for relative in required:
    assert (wp / relative).is_file(), f'Missing plugin file: {relative}'

refs = [
    '01-woocommerce-checkout.jpeg', '02-gutenberg.jpeg', '03-wp-admin.jpeg',
    '04-transactions.jpeg', '05-payment-link-modal.jpeg',
    '06-merchant-dashboard.jpeg', '07-payment-checkout.jpeg',
    '08-pricing.jpeg', '09-home.jpeg', '10-brand-identity.jpeg',
]
for name in refs:
    assert (ROOT / 'design' / 'references' / name).is_file(), f'Missing design reference: {name}'

plugin = (wp / 'brendigo-pay.php').read_text(encoding='utf-8')
readme = (wp / 'readme.txt').read_text(encoding='utf-8')
package = (ROOT / 'scripts' / 'package_release.py').read_text(encoding='utf-8')
assert f'Version: {VERSION}' in plugin
assert f"define('BP_PAY_VERSION', '{VERSION}')" in plugin
assert f'Stable tag: {VERSION}' in readme
assert f"VERSION = '{VERSION}'" in package
for snippet in ['Author: Brendigo', 'https://brendigopay.com', 'https://brendigo.com', 'Text Domain: brendigo-pay']:
    assert snippet in plugin, snippet
assert 'Contributors: brendigo' in readme
assert 'bp_lic_[a-f0-9]{64}' in (wp / 'views' / 'admin-settings.php').read_text(encoding='utf-8')
assert 'admin_post_brendigo_pay_activate_license' in plugin
assert 'class SiteLicenses' in (ROOT / 'platform/src/SiteLicenses.php').read_text()
assert 'class Plans' in (ROOT / 'platform/src/Plans.php').read_text()
for tier, count in [('start', 1), ('business', 5), ('pro', 25)]:
    assert tier in (ROOT / 'platform/src/Plans.php').read_text()

front = (wp / 'assets' / 'public.css').read_text(encoding='utf-8')
admin = (wp / 'assets' / 'admin.css').read_text(encoding='utf-8')
editor = (wp / 'assets' / 'editor.css').read_text(encoding='utf-8')
block = (wp / 'assets' / 'block.js').read_text(encoding='utf-8')
wc = (wp / 'assets' / 'woocommerce-block.js').read_text(encoding='utf-8')
gateway = (wp / 'includes' / 'class-bp-gateway.php').read_text(encoding='utf-8')
for snippet in ['.bp-wc-provider-monogram', '.bp-wc-provider:focus-within', '@media(max-width:600px)', 'prefers-reduced-motion']:
    assert snippet in front, snippet
assert '.bp-editor-verified-amount' in editor
assert 'Iznos s platforme:' in block
assert 'bp-wc-provider-monogram-' in wc and 'bp-wc-provider-monogram-' in gateway
for pid in ['stripe', 'paypal', 'revolut']:
    assert pid in wc and pid in gateway
assert "canMakePayment: function () { return options.length > 0; }" in wc
assert 'class="bp-admin-hero"' in (wp / 'views' / 'admin-settings.php').read_text(encoding='utf-8')
assert '--bp-control-height' in admin
assert '.bp-admin-sync-details' in admin and '.bp-admin-sync-panel' in admin
wp_admin_view=(wp / 'views/admin-settings.php').read_text(encoding='utf-8')
assert 'Bankovna uplata (BACS)' in wp_admin_view
assert 'Pouzećem (COD)' in wp_admin_view
assert 'wc-settings&tab=checkout' in wp_admin_view
assert 'bp-merchant-main .table-wrap table' in (ROOT / 'platform/public/assets/app.css').read_text(encoding='utf-8')
assert 'bp-admin-sync-heading' in (wp / 'views/admin-settings.php').read_text(encoding='utf-8')
assert not re.search(r'name=["\']card_number["\']', block + wc + gateway)
legal = (ROOT / 'platform/src/LegalPages.php').read_text(encoding='utf-8')
for path in ['/privacy', '/terms', '/cookies', '/refund-policy', '/contact', '/about', '/security', '/providers']:
    assert f"'{path}'" in legal
assert 'Built with' in (ROOT / 'platform/public/index.php').read_text()
assert '.bp-footer-grid' in (ROOT / 'platform/public/assets/app.css').read_text()
guide_source=(ROOT / 'platform/src/PublicGuides.php').read_text(encoding='utf-8')
assert 'function bp_guide_catalog()' in guide_source
assert 'function bp_guide_page(string $path)' in guide_source
assert '/features' in guide_source and '/integrations' in guide_source
assert "bp_guide_page($path)" in (ROOT / 'platform/public/index.php').read_text()
assert 'bp-admin-guide-links' in (wp / 'views/admin-settings.php').read_text()
assert '<nav class="bp-admin-tabs"' not in wp_admin_view, 'top navigation must be removed'
assert (ROOT / 'tests/wp-admin-sections.php').is_file()
assert 'add_submenu_page(' in (wp / 'includes/class-bp-settings.php').read_text()
assert "'brendigo-pay-logs'" in (wp / 'includes/class-bp-settings.php').read_text()
assert "'brendigo-pay-qr'" in (wp / 'includes/class-bp-settings.php').read_text()
assert 'bp-pay-admin' in plugin and "str_starts_with($hook, 'brendigo-pay_page_brendigo-pay-')" in plugin
assert 'bp-admin-tabs .bp-admin-tab' not in (wp / 'assets/admin.js').read_text()
assert 'showSupportGuideMenu()' in (ROOT / 'android/app/src/main/java/com/brendigo/pay/MainActivity.java').read_text()
assert (ROOT / 'platform/public/assets/screenshots/user-dashboard-desktop.png').is_file()


# Provider status must reflect only the authenticated merchant's configured sandbox providers.
merchant_ui = (ROOT / 'platform/src/Ui.php').read_text(encoding='utf-8')
merchant_route = (ROOT / 'platform/public/index.php').read_text(encoding='utf-8')
assert 'configuredProviderCount' in merchant_ui
assert 'count(array_filter($providers' in merchant_ui
assert 'Nije povezan' in merchant_ui
assert "configured_providers($user['id'])" in merchant_route
assert '<strong>3</strong><small>Stripe, PayPal, Revolut</small>' not in merchant_ui

# Design tokens and responsive desktop/tablet/mobile treatment follow the ten references.
web_css = (ROOT / 'platform/public/assets/app.css').read_text(encoding='utf-8')
for token in ['--bp-indigo:#1e2bb8', '--bp-royal:#3b5bff', '--bp-cyan:#06c6b6',
              '.bp-body-marketing .bp-home-hero', '.bp-price-cards .bp-price-featured',
              '.bp-link-dialog .bp-dialog-heading', '.bp-ref-checkout .bp-provider-tile',
              '@media(max-width:800px)', '@media(max-width:560px)']:
    assert token in web_css, token


# Reference geometry applies to actual source components, not screenshot backgrounds.
for selector in ['.bp-body-marketing .bp-pricing-intro',
                 '.bp-body-marketing .bp-price-cards article',
                 '.bp-body-marketing .bp-home-hero',
                 '.bp-link-dialog .bp-dialog-inner']:
    assert selector in web_css, selector

print('PASS: version, legal pages, user-facing guides, WP and checkout UI contracts (structural only)')

# Functional link expiration, merchant provider preference, no fake QR activation.
ui_link = (ROOT / 'platform/src/Ui.php').read_text()
route_link = (ROOT / 'platform/public/index.php').read_text()
for marker in ['name="preferred_provider"', 'name="valid_days"', 'bp-preview-qr-placeholder']:
    assert marker in ui_link, marker
for marker in ["$preferredProvider = strtolower", "'expires_at' => gmdate", "payment_link_is_current($link)"]:
    assert marker in route_link, marker
assert 'bp-link-provider-choice:has(input:disabled)' in web_css

# Real desktop merchant dashboard uses independent columns, not a stretched single-row grid.
merchant = (ROOT / 'platform/src/Ui.php').read_text()
assert 'bp-dashboard-layout' in merchant
assert 'bp-dashboard-side' in merchant and 'bp-provider-panel' in merchant
assert 'bp-dashboard-maincol' in merchant and 'bp-transactions-card' in merchant
assert '.bp-dashboard-maincol .bp-transactions-card { order:2; }' in web_css

# Transaction list and detail panel must be independent responsive views.
merchant_ui = (ROOT / 'platform/src/Ui.php').read_text()
merchant_js = (ROOT / 'platform/public/assets/app.js').read_text()
assert 'bp-transaction-details-side' in merchant_ui
assert 'bp-transaction-layout' in merchant_ui
assert 'detailSide.scrollIntoView' in merchant_js
assert '.bp-side-details' in web_css

# Public home follows five-part social-proof proportions without synthetic review claims.
public_ui=(ROOT/'platform/src/Ui.php').read_text()
assert 'class="bp-home-proof"' in public_ui
assert 'podržana sandbox pružatelja' in public_ui
assert '10.000+' not in public_ui
assert '.bp-home-proof { display:grid' in web_css

# Unconfigured checkout presents disabled, non-misleading payment choices.
checkout_source=(ROOT/'platform/src/Ui.php').read_text()
assert 'bp-checkout-provider-inactive' in checkout_source
assert 'aria-disabled="true"' in checkout_source
assert 'bp-hosted-disabled' in checkout_source
assert '.bp-body-checkout .bp-ref-checkout>.bp-checkout-payment' in web_css

# The numeric <10% target is reported explicitly and cannot pass with missing WP screenshots.
audit_code=(ROOT/'scripts/visual_audit_10.py').read_text()
assert 'all_nine_screens_below_target' in audit_code
assert 'unmeasured_screens' in audit_code
assert 'args.require_target and not target_met' in audit_code
assert 'below-10-percent acceptance' in (ROOT/'.github/workflows/visual-parity.yml').read_text()

# Plugin uninstall must remove its own sensitive installation metadata, never order data.
uninstall=(wp/'uninstall.php').read_text()
for option in ['brendigo_pay_settings', 'bp_pay_installation_id', 'bp_pay_sync_status',
               'bp_pay_sync_log', 'woocommerce_brendigo_pay_settings']:
    assert option in uninstall, option
assert 'wp_clear_scheduled_hook' in uninstall
assert 'delete_posts' not in uninstall and 'wc_delete_order' not in uninstall

# The obsolete dashboard grid was replaced with the responsive two-column layout.
assert '.bp-dashboard-grid {' not in web_css
assert 'class="bp-dashboard-layout"' in (ROOT/'platform/src/Ui.php').read_text()

# Independent CTA composition and checkout security/price/payment flow.
visual=(ROOT/'platform/src/Ui.php').read_text()
assert visual.count('class="bp-cta-visual" aria-hidden="true"') == 2
assert 'class="bp-bottom-cta bp-bottom-cta-home"' in visual
assert 'class="bp-bottom-cta bp-bottom-cta-pricing"' in visual
assert 'bp-preview-link-placeholder' in visual
assert visual.count('class="bp-provider-monogram" aria-hidden="true"') >= 2
checkout=visual.split('function bp_public_payment(',1)[1].split('function bp_site_plugin(',1)[0]
assert checkout.count('class="bp-payment-total"') == 2
assert checkout.count('class="bp-checkout-security"') == 2

# Pricing estimator changes visible prices and table only, never creates a billing entitlement.
pricing=(ROOT/'platform/src/Ui.php').read_text(encoding='utf-8')
pricing_js=(ROOT/'platform/public/assets/app.js').read_text(encoding='utf-8')
assert 'bp-pricing-intro bp-pricing-only' in pricing
assert 'data-billing-period="monthly"' in pricing and 'data-billing-period="yearly"' in pricing
for amount in ('238,80 €', '598,80 €', '1.198,80 €'):
    assert amount in pricing, amount
assert 'data-billing-disclaimer' in pricing
assert 'showBillingPeriod' in pricing_js and "setAttribute('aria-pressed'" in pricing_js


# Visual regression guard: there must be one effective overlay background,
# not a stronger late declaration silently overriding the intended opacity.
web_css = (ROOT / 'platform/public/assets/app.css').read_text(encoding='utf-8')
backdrop_rules = re.findall(r'\.bp-link-dialog::backdrop\s*\{([^}]*)\}', web_css)
assert len(backdrop_rules) == 2, 'Review added modal backdrop rule'
assert sum('background:' in rule for rule in backdrop_rules) == 1
assert 'rgba(19,34,83,.37)' in ''.join(backdrop_rules)
assert '.bp-link-dialog .bp-dialog-heading::before' in web_css
assert '.bp-link-dialog .bp-dialog-heading::after' in web_css

# Keep informative annual prices synchronized with paid-plan amounts.
plans_source = (ROOT / 'platform/src/Plans.php').read_text(encoding='utf-8')
for tier, minor in [('start', 1990), ('business', 4990), ('pro', 9990)]:
    assert re.search(r"'"+tier+r"'\s*=>\s*\[[^\]]*'monthly_minor'\s*=>\s*"+str(minor), plans_source), tier
