#!/usr/bin/env python3
"""Offline structural UI contracts; this is NOT a visual 1:1 verification."""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
VERSION = '0.8.6'
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

print('PASS: version, legal pages, user-facing guides, WP and checkout UI contracts (structural only)')
