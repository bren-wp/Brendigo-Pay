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
accounts = (ROOT / 'platform/src/Accounts.php').read_text(encoding='utf-8')
assert "password_hash($password, PASSWORD_ARGON2ID)" in accounts
assert "registration_rate_" in accounts and "'registration'" in accounts
assert "'/api/v1/mobile/register'" in web and "'/register'" in web
assert "X-Frame-Options: DENY" in web and "Content-Security-Policy:" in web
assert web.count("file_get_contents('php://input', false, null, 0, 1000001)") == 4, 'Webhook bodies must be size bounded'
assert "LOCK_EX" in store and "rename($temp, $path)" in store
assert "'redirection' => 0" in api and "'limit_response_size' => 131072" in api
assert 'wp_safe_remote_request' in api and "'reject_unsafe_urls' => true" in api
assert 'current_user_can' in wp and 'rest_api_init' in wp
assert "check_admin_referer" in (wpbase / 'includes/class-bp-settings.php').read_text(encoding='utf-8')
assert "wp_parse_url($checkoutUrl, PHP_URL_HOST)" in gateway
assert "has_status(['cancelled', 'refunded', 'failed'])" in gateway
assert 'payment_complete($txn)' in gateway
assert "amount_minor" in block and "set('amount')" not in block
for path in [ROOT / 'platform/public/index.php', ROOT / 'platform/src/Ui.php', ROOT / 'platform/src/Accounts.php', *wpbase.rglob('*.php')]:
    text = path.read_text(encoding='utf-8')
    # Avoid unsafe PHP eval and shell execution in runtime code.
    assert not re.search(r'(?<![A-Za-z_])(?:eval|shell_exec|passthru|system)\s*\(', text), f'Unsafe function in {path}'
assert "name=\"card_number\"" not in wp
assert "name=\"cvc\"" not in wp
assert "Version: 0.8.11" in wp
plans = (ROOT / 'platform/src/Plans.php').read_text(encoding='utf-8')
licenses = (ROOT / 'platform/src/SiteLicenses.php').read_text(encoding='utf-8')
assert "activeOrFail($merchantId)" in licenses, 'Must enforce active paid plan when issuing licenses'
assert "siteCount($merchantId) >= $limit" in licenses, 'Must enforce quota on server'
assert "hash('sha256', $plain)" in licenses, 'Must not store plaintext licenses'
assert "activeOrFail($record['user_id'])" in licenses, 'Activation requires active plan'
assert "'billing_mode' => 'manual_verified'" in plans, 'Only operator-verified billing enabled'
assert "site_id" in web and "site_id" in licenses, 'Require site-scoped API token'
assert "'admin_user_ids'" in (ROOT / 'platform/bin/setup.php').read_text(), 'Operator auth must be configured'
settings_php = (wpbase / 'includes/class-bp-settings.php').read_text(encoding='utf-8')
assert "'/api/v1/sites/sync'" in web and "'/api/v1/sites/status'" in web, 'Missing licensed site status API'
assert "installation_hash" in web and "'merchant_sites_'" in web, 'Heartbeat requires installation proof and locking'
assert "check_csrf();" in web and "'/admin/plans/suspend'" in web, 'Admin suspension must use CSRF and operator gate'
assert "license_is_current()" in gateway and "license_is_current()" in (wpbase / 'includes/class-bp-blocks.php').read_text()
assert "last_ok" in settings_php and "7200" in settings_php, 'WooCommerce site license freshness check missing'
assert "update_option('bp_pay_sync_status'" in settings_php, 'Status must be synced to WP panel'
assert "brendigo_pay_site_sync" in wp, 'WordPress cron hook missing'
assert "Contributors: brendigo" in (wpbase / 'readme.txt').read_text(encoding='utf-8')
print('PASS: static security guardrails; external audit and E2E required')
