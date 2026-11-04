#!/usr/bin/env python3
"""Offline structural UI contracts; this is NOT a visual 1:1 verification."""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
VERSION = '0.8.1'
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
assert not re.search(r'name=["\']card_number["\']', block + wc + gateway)
print('PASS: version, branding, 10 references, Gutenberg and checkout UI contracts (structural only)')
