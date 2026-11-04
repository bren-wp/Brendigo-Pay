<?php
/** WP sync contract tests with local stubs; NOT a real WordPress integration test. */
declare(strict_types=1);
define('ABSPATH', '/wordpress-test');
define('BP_PAY_VERSION', '0.8.3');
define('BRENDIGO_PAY_API_KEY', 'bp_test_' . str_repeat('a', 64));
$GLOBALS['bp_options'] = [
    'brendigo_pay_settings' => [
        'url' => 'https://pay.example', 'site_id' => str_repeat('c', 24),
    ],
    'bp_pay_installation_id' => str_repeat('b', 32),
];
$GLOBALS['sync_simulation'] = 'active';
$GLOBALS['sync_calls'] = [];
class WP_Error { public function __construct(public string $code = 'failed') {} }
function __(string $s, string $d = ''): string { return $s; }
function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }
function get_option(string $name, mixed $default = false): mixed { return $GLOBALS['bp_options'][$name] ?? $default; }
function update_option(string $name, mixed $value, bool $autoload = false): bool {
    $GLOBALS['bp_options'][$name] = $value; return true;
}
function home_url(string $url = '/'): string { return 'https://shop.example/' . ltrim($url, '/'); }
function wp_parse_url(string $url): array|false { return parse_url($url); }
function untrailingslashit(string $s): string { return rtrim($s, '/'); }
function get_bloginfo(string $part): string { return '6.9.1'; }
function wp_salt(string $name): string { return 'stub-local-only'; }
function assert_bp(bool $condition, string $what): void {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $what); }
    echo "PASS: {$what}\n";
}
class BP_Pay_API {
    public static function request(string $method, string $path, ?array $data = null): array|WP_Error {
        $GLOBALS['sync_calls'][] = [$method, $path, $data];
        $mode = $GLOBALS['sync_simulation'];
        if ($mode === 'error') { return new WP_Error('revoked'); }
        return [
            'site_id' => str_repeat('c', 24),
            'origin' => $mode === 'wrong_origin' ? 'https://another.example' : 'https://shop.example',
            'status' => $mode === 'pending' ? 'pending' : 'active',
            'plan' => 'business', 'expires_at' => '+30 days',
            'website_limit' => 5, 'websites_used' => 2, 'mode' => 'test',
            'last_seen_at' => gmdate('c'),
        ];
    }
}
require dirname(__DIR__) . '/wordpress-plugin/brendigo-pay/includes/class-bp-settings.php';
assert_bp(!BP_Pay_Settings::license_is_current(), 'missing sync fails closed');
assert_bp(BP_Pay_Settings::sync_installation(), 'valid sync accepted');
$call = $GLOBALS['sync_calls'][0];
assert_bp($call[0] === 'POST' && $call[1] === '/api/v1/sites/sync', 'sync uses authenticated status endpoint');
assert_bp($call[2]['site_url'] === 'https://shop.example'
    && $call[2]['installation_id'] === str_repeat('b', 32)
    && $call[2]['plugin_version'] === BP_PAY_VERSION
    && $call[2]['wordpress_version'] === '6.9.1', 'correct domain, installation and version payload');
assert_bp(BP_Pay_Settings::license_is_current(), 'validated license is current');
assert_bp(BP_Pay_Settings::sync_status()['website_limit'] === 5
    && BP_Pay_Settings::sync_status()['websites_used'] === 2, 'plan quota displayed using server response');
$state = BP_Pay_Settings::sync_status();
$state['last_ok'] = time() - 7201;
update_option('bp_pay_sync_status', $state, false);
assert_bp(!BP_Pay_Settings::license_is_current(), 'expired status cache fails closed');
$GLOBALS['sync_simulation'] = 'wrong_origin';
assert_bp(!BP_Pay_Settings::sync_installation() && !BP_Pay_Settings::license_is_current(), 'wrong origin fails closed');
$GLOBALS['sync_simulation'] = 'active';
assert_bp(BP_Pay_Settings::sync_installation(), 'good sync recovers');
$GLOBALS['sync_simulation'] = 'error';
assert_bp(!BP_Pay_Settings::sync_installation() && !BP_Pay_Settings::license_is_current(), 'remote revocation/network failure fails closed');
$GLOBALS['sync_simulation'] = 'pending';
assert_bp(!BP_Pay_Settings::sync_installation(), 'pending license fails closed');
$GLOBALS['sync_simulation'] = 'active';
assert_bp(BP_Pay_Settings::sync_installation(), 'fresh valid sync enables again');
$GLOBALS['bp_options']['brendigo_pay_settings']['site_id'] = str_repeat('d', 24);
assert_bp(!BP_Pay_Settings::license_is_current(), 'local site ID change invalidates cache');
echo "Brendigo Pay WordPress sync contract tests passed.\n";
