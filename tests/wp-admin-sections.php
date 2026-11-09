<?php
/** @description Native WordPress menu pages must render one independent section at a time.
 *  This is a stubbed render isolation test, not a real WordPress screenshot.
 */
declare(strict_types=1);

define('ABSPATH', '/test-wordpress/');
define('BP_PAY_PATH', dirname(__DIR__) . '/wordpress-plugin/brendigo-pay/');
define('BP_PAY_FILE', BP_PAY_PATH . 'brendigo-pay.php');
define('BP_PAY_VERSION', '0.8.14');

function __(string $value, string $domain = ''): string { return $value; }
function esc_html(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_attr(mixed $value): string { return esc_html($value); }
function esc_url(mixed $value): string { return esc_html($value); }
function wp_unslash(mixed $value): mixed { return $value; }
function sanitize_key(string $value): string { return preg_replace('/[^a-z0-9_\\-]/', '', strtolower($value)); }
function untrailingslashit(string $value): string { return rtrim($value, '/'); }
function wp_parse_url(string $url): array|false { return parse_url($url); }
function current_user_can(string $cap): bool { return $cap === 'manage_options'; }
function home_url(string $path = ''): string { return 'https://wp.example.test' . $path; }
function admin_url(string $path = ''): string { return 'https://wp.example.test/wp-admin/' . $path; }
function plugins_url(string $path, string $file): string { return 'https://wp.example.test/wp-content/plugins/brendigo-pay/' . $path; }
function settings_errors(string $settings): void {}
function settings_fields(string $group): void { echo '<input type="hidden" name="option_page" value="' . esc_attr($group) . '">'; }
function wp_nonce_field(string $action): void { echo '<input type="hidden" name="_wpnonce" value="test">'; }
function is_wp_error(mixed $value): bool { return false; }
function get_option(string $name, mixed $default = false): mixed { return $default; }
function wp_die(string $reason): never { throw new RuntimeException($reason); }
function add_menu_page(mixed ...$args): string { return 'toplevel_page_brendigo-pay'; }
function add_submenu_page(mixed ...$args): string { return 'brendigo-pay_page_' . (string) ($args[4] ?? ''); }

require BP_PAY_PATH . 'includes/class-bp-settings.php';

$slugs = BP_Pay_Settings::admin_sections();
$expected = [
    'brendigo-pay' => 'bp-general',
    'brendigo-pay-providers' => 'bp-providers',
    'brendigo-pay-links' => 'bp-merchant-links',
    'brendigo-pay-qr' => 'bp-qr',
    'brendigo-pay-security' => 'bp-security',
    'brendigo-pay-logs' => 'bp-logs',
    'brendigo-pay-docs' => 'bp-docs',
];
if (array_keys($slugs) !== array_keys($expected)) {
    throw new RuntimeException('Sidebar menu registration has changed');
}

foreach ($expected as $slug => $id) {
    $_GET = ['page' => $slug];
    ob_start();
    BP_Pay_Settings::page();
    $html = (string) ob_get_clean();
    if (!str_contains($html, 'id="' . $id . '"')) {
        throw new RuntimeException($slug . ' did not render its own page');
    }
    foreach ($expected as $otherSlug => $otherId) {
        if ($slug !== $otherSlug && str_contains($html, 'id="' . $otherId . '"')) {
            throw new RuntimeException($slug . ' also rendered ' . $otherSlug . ' on the same page');
        }
    }
    if (str_contains($html, 'bp-admin-tabs') || str_contains($html, '<nav ')) {
        throw new RuntimeException('Old top navigation still appears on ' . $slug);
    }
    if (!str_contains($html, '<h1 class="bp-admin-title">' . esc_html($slugs[$slug]) . '</h1>')) {
        throw new RuntimeException($slug . ' has incorrect page title');
    }
    echo 'PASS: dedicated WP page ' . $slug . PHP_EOL;
}
echo "PASS: seven isolated native WordPress admin pages, no top scroll-to-section menu\n";
