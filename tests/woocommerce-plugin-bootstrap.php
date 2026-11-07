<?php
/** WooCommerce Blocks fatal regression test, with WP/WC bootstrap stubs. NOT live WP E2E. */
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\Payments\Integrations {
    // The upstream WooCommerce AbstractPaymentMethodType defines both properties
    // as untyped/protected. In v0.8.0 our private typed redeclaration caused a fatal.
    abstract class AbstractPaymentMethodType {
        protected $name = '';
        protected $settings = [];
        public function initialize(): void {}
        public function is_active(): bool { return true; }
        public function get_payment_method_script_handles(): array { return []; }
        public function get_payment_method_data(): array { return []; }
    }
}

namespace {
    define('ABSPATH', '/wordpress-test');
    $GLOBALS['wp_test_actions'] = [];
    $GLOBALS['wp_test_registered_scripts'] = [];
    $GLOBALS['wc_test_settings'] = [
        'enabled' => 'yes',
        'title' => 'Brendigo Pay (TEST)',
        'description' => 'Sandbox gateway',
        'default_provider' => 'paypal',
    ];

    $GLOBALS['bp_test_main_menu'] = [];
    $GLOBALS['bp_test_submenus'] = [];
    function add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon = '', $position = null): string {
        $GLOBALS['bp_test_main_menu'][] = $slug;
        return 'toplevel_page_' . $slug;
    }
    function add_submenu_page($parent, $page_title, $menu_title, $capability, $slug, $callback): string {
        $GLOBALS['bp_test_submenus'][] = [$parent, $slug, $menu_title];
        return $parent . '_page_' . $slug;
    }

    function plugin_dir_path(string $file): string { return dirname($file) . DIRECTORY_SEPARATOR; }
    function plugins_url(string $path, string $file): string { return 'https://example.test/wp-content/plugins/brendigo-pay/' . $path; }
    function add_action(string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): void {
        $GLOBALS['wp_test_actions'][$hook][] = $callback;
    }
    function add_filter(string $hook, callable|array|string $callback, int $priority = 10, int $accepted_args = 1): void {
        add_action($hook, $callback, $priority, $accepted_args);
    }
    function add_shortcode(string $name, callable|string $callback): void { $GLOBALS['wp_test_shortcodes'][$name] = $callback; }
    function get_option(string $name, mixed $default = false): mixed {
        if ($name === 'woocommerce_brendigo_pay_settings') { return $GLOBALS['wc_test_settings']; }
        if ($name === 'brendigo_pay_settings') { return []; }
        return $default;
    }
    function get_woocommerce_currency(): string { return $GLOBALS['test_currency'] ?? 'EUR'; }
    function wp_register_script(string $name, string $src, array $deps, string $version, bool $footer): void {
        $GLOBALS['wp_test_registered_scripts'][$name] = [$src, $deps, $version, $footer];
    }
    function __(string $text, string $domain = ''): string { return $text; }
    function shortcode_atts(array $pairs, array $atts, string $shortcode = ''): array { return array_merge($pairs, $atts); }
    function sanitize_text_field(string $s): string { return $s; }
    function untrailingslashit(string $s): string { return rtrim($s, '/'); }

    class WC_Payment_Gateway {}
    class WP_Post { public string $post_content = ''; }

    require dirname(__DIR__) . '/wordpress-plugin/brendigo-pay/brendigo-pay.php';
    function verify(bool $pass, string $label): void {
        if (!$pass) { throw new \RuntimeException('FAIL: ' . $label); }
        echo "PASS: {$label}\n";
    }

    verify(defined('BP_PAY_VERSION') && BP_PAY_VERSION === '0.8.8', 'plugin loads and version matches');
    foreach ($GLOBALS['wp_test_actions']['admin_menu'] as $callback) { $callback(); }
    verify($GLOBALS['bp_test_main_menu'] === ['brendigo-pay'], 'single Brendigo Pay WordPress parent menu');
    verify(count($GLOBALS['bp_test_submenus']) === 7, 'seven native sidebar sections');
    verify(array_column($GLOBALS['bp_test_submenus'], 1) === array_keys(\BP_Pay_Settings::admin_sections()),
        'all sidebar sections have registered and unique page slugs');
    verify(isset($GLOBALS['wp_test_actions']['plugins_loaded']), 'WooCommerce classic hook registered');
    verify(isset($GLOBALS['wp_test_actions']['woocommerce_blocks_loaded']), 'WooCommerce Blocks hook registered');

    // Loading the inherited property is the original fatal path.
    foreach ($GLOBALS['wp_test_actions']['woocommerce_blocks_loaded'] as $callback) { $callback(); }
    verify(isset($GLOBALS['wp_test_actions']['woocommerce_blocks_payment_method_type_registration']), 'Blocks registrar hook created');
    $registry = new class {
        public array $methods = [];
        public function register($method): void { $this->methods[] = $method; }
    };
    foreach ($GLOBALS['wp_test_actions']['woocommerce_blocks_payment_method_type_registration'] as $callback) {
        $callback($registry);
    }
    verify(count($registry->methods) === 1 && $registry->methods[0] instanceof \BP_Pay_Blocks, 'Blocks integration loads and registers');

    $property = new \ReflectionProperty(\BP_Pay_Blocks::class, 'settings');
    verify($property->isProtected() && $property->getDeclaringClass()->getName() ===
        \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType::class,
        'inherited protected untyped settings property retained');

    $method = $registry->methods[0];
    $method->initialize();
    verify(!$method->is_active(), 'Blocks disabled without token/platform configuration');
    $data = $method->get_payment_method_data();
    verify($data['title'] === 'Brendigo Pay (TEST)' && $data['defaultProvider'] === 'paypal',
        'Blocks settings loaded correctly');
    verify(count($data['providers']) === 3 && $data['supports'] === ['products'], 'provider data exposed to Checkout Blocks');
    verify($method->get_payment_method_script_handles() === ['bp-pay-woocommerce-block'] &&
        $GLOBALS['wp_test_registered_scripts']['bp-pay-woocommerce-block'][2] === '0.8.8',
        'Blocks frontend script registered locally at current version');

    // Corrupted WP option rows sometimes return false/non-array data.
    $GLOBALS['wc_test_settings'] = false;
    $method->initialize();
    verify(!$method->is_active(), 'invalid persisted settings safely normalized');

    // WordPress shortcode hooks can receive unexpected attribute types.
    verify(\bp_pay_shortcode('') === '' && \bp_pay_shortcode(false) === '',
        'malformed/empty shortcode input does not crash PHP');
    echo "All WooCommerce Blocks bootstrap regression tests passed.\n";
}
