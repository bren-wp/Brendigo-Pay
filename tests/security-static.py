#!/usr/bin/env python3
"""Static security guardrails for Brendigo Pay (not a penetration test)."""
from pathlib import Path
import re
ROOT = Path(__file__).resolve().parents[1]
web = (ROOT / 'platform/public/index.php').read_text(encoding='utf-8')
store = (ROOT / 'platform/src/JsonStore.php').read_text(encoding='utf-8')
wpbase = ROOT / 'wordpress-plugin/brendigo-pay'
api = (wpbase / 'includes/class-bp-api.php').read_text(encoding='utf-8')
wp = (wpbase / 'brendigo-pay.php').read_text(encoding='utf-8')
gateway = (wpbase / 'includes/class-bp-gateway.php').read_text(encoding='utf-8')
block = (wpbase / 'assets/block.js').read_text(encoding='utf-8')
assert "'count'] >= 30" in web, 'Missing global per-IP throttle'
assert "'count'] >= 8" in web, 'Missing per-account/IP throttle'
assert "auth_ip_" in web and "auth_" in web
assert 'session_regenerate_id(true)' in web
assert 'check_csrf();' in web
assert "password_hash($password, PASSWORD_ARGON2ID)" in web
assert "X-Frame-Options: DENY" in web and "Content-Security-Policy:" in web
assert "LOCK_EX" in store and "rename($temp, $path)" in store
assert "'redirection' => 0" in api and "'limit_response_size' => 131072" in api
assert 'wp_safe_remote_request' in api and "'reject_unsafe_urls' => true" in api
assert 'current_user_can' in wp and 'rest_api_init' in wp
assert "check_admin_referer" in (wpbase / 'includes/class-bp-settings.php').read_text(encoding='utf-8')
assert "wp_parse_url($checkoutUrl, PHP_URL_HOST)" in gateway
assert "has_status(['cancelled', 'refunded', 'failed'])" in gateway
assert 'payment_complete($txn)' in gateway
assert "amount_minor" in block and "set('amount')" not in block
for path in [ROOT / 'platform/public/index.php', ROOT / 'platform/src/Ui.php', *wpbase.rglob('*.php')]:
    text = path.read_text(encoding='utf-8')
    # Avoid unsafe PHP eval and shell execution in runtime code.
    assert not re.search(r'(?<![A-Za-z_])(?:eval|shell_exec|passthru|system)\s*\(', text), f'Unsafe function in {path}'
assert "name=\"card_number\"" not in wp
assert "name=\"cvc\"" not in wp
assert "Version: 0.8.0" in wp
assert "Contributors: brendigo" in (wpbase / 'readme.txt').read_text(encoding='utf-8')
print('PASS: static security guardrails; external audit and E2E required')
