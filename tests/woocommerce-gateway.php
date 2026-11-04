<?php
/** Offline contract test for the WC classic gateway. NOT a substitute for real WooCommerce integration tests. */
declare(strict_types=1);
define('ABSPATH', '/test');
function __(string $s, string $d = ''): string { return $s; }
function wc_add_notice(string $message, string $type): void { $GLOBALS['notices'][] = [$message, $type]; }
function get_woocommerce_currency(): string { return 'EUR'; }
function home_url(string $path = '/'): string { return 'https://example.test' . $path; }
function wp_parse_url(string $url, int $part = -1): mixed { return parse_url($url, $part); }
function esc_url_raw(string $url): string { return $url; }
function is_wp_error(mixed $value): bool { return false; }
function sanitize_text_field(string $text): string { return $text; }
function sanitize_key(string $text): string { return strtolower(preg_replace('/[^a-z0-9_\-]/', '', $text)); }
function wp_unslash(string $text): string { return $text; }
function wp_next_scheduled(string $hook, array $args): bool { return false; }
function wp_schedule_single_event(int $timestamp, string $hook, array $args): bool { $GLOBALS['scheduled'][] = $args; return true; }
function add_action(string $name, callable $callback): void {}
function wc_get_order(int $id): WC_Order { return $GLOBALS['order']; }
class WC_Payment_Gateway {
    public string $id;
    public string $method_title;
    public string $method_description;
    public bool $has_fields;
    public array $supports;
    public array $form_fields;
    public string $title;
    public string $description;
    public string $enabled;
    public function init_settings(): void {}
    public function get_option(string $key, string $default = ''): string { return $default; }
    public function is_available(): bool { return true; }
    public function process_admin_options(): void {}
}
class WC_Order {
    public array $metadata = [];
    public string $status = 'pending';
    public bool $paid = false;
    public int $completions = 0;
    public array $notes = [];
    public function get_id(): int { return 17; }
    public function get_currency(): string { return 'EUR'; }
    public function get_total(): string { return '49.90'; }
    public function get_order_number(): string { return '17'; }
    public function is_paid(): bool { return $this->paid; }
    public function get_checkout_order_received_url(): string { return 'https://example.test/received/17'; }
    public function get_cancel_order_url_raw(): string { return 'https://example.test/cancel/17'; }
    public function get_payment_method(): string { return 'brendigo_pay'; }
    public function has_status(array $statuses): bool { return in_array($this->status, $statuses, true); }
    public function update_meta_data(string $key, string $value): void { $this->metadata[$key] = $value; }
    public function get_meta(string $key, bool $single = true): string { return $this->metadata[$key] ?? ''; }
    public function save(): void {}
    public function update_status(string $status, string $note): void { $this->status = $status; }
    public function payment_complete(string $id): void { $this->paid = true; $this->completions++; $this->status = 'processing'; }
    public function add_order_note(string $note): void { $this->notes[] = $note; }
}
class BP_Pay_Settings {
    public static function api_base(): string { return 'https://pay.example.test'; }
    public static function api_key(): string { return 'test-token'; }
}
class BP_Pay_API {
    public static array $calls = [];
    public static function request(string $method, string $path, ?array $payload = null): array {
        self::$calls[] = [$method, $path, $payload];
        if ($method === 'POST') {
            $provider = $payload['provider'] ?? 'stripe';
            $urls = [
                'stripe' => 'https://checkout.stripe.com/c/pay/cs_test_fake',
                'paypal' => 'https://www.sandbox.paypal.com/checkoutnow?token=ABC1234567',
                'revolut' => 'https://checkout.revolut.com/payment-link/sandbox_test',
            ];
            return [
                'transaction_id' => str_repeat('a', 64),
                'checkout_url' => $urls[$provider] ?? 'https://bad.example/evil',
                'provider' => $provider,
            ];
        }
        return [
            'id' => str_repeat('a', 64),
            'status' => 'paid',
            'reference' => 'wp_' . hash('sha256', 'https://example.test/' . ':' . 17),
            'currency' => 'EUR',
            'amount_minor' => 4990,
            'provider' => $GLOBALS['order']->get_meta('_brendigo_pay_provider') ?: 'stripe',
        ];
    }
}
function ensure(bool $ok, string $reason): void {
    if (!$ok) { throw new RuntimeException('FAIL: ' . $reason); }
    echo 'PASS: ' . $reason . "\n";
}
require dirname(__DIR__) . '/wordpress-plugin/brendigo-pay/includes/class-bp-gateway.php';
$GLOBALS['order'] = new WC_Order();
$gateway = new BP_Pay_Gateway();
ensure($gateway->is_available(), 'gateway available for EUR test checkout');
ensure($gateway->has_fields === true, 'classic checkout provider dropdown is enabled');
$result = $gateway->process_payment(17);
ensure($result['result'] === 'success' && $result['redirect'] === 'https://checkout.stripe.com/c/pay/cs_test_fake', 'checkout redirect allowlisted');
ensure($GLOBALS['order']->status === 'on-hold' && count($GLOBALS['scheduled']) === 1, 'order on hold and polling scheduled');
BP_Pay_Gateway::poll_order(17, 1);
ensure($GLOBALS['order']->is_paid() && $GLOBALS['order']->completions === 1, 'valid API confirmation completes order');
BP_Pay_Gateway::poll_order(17, 1);
ensure($GLOBALS['order']->completions === 1, 'payment confirmation idempotent');
$GLOBALS['order'] = new WC_Order();
$GLOBALS['order']->status = 'cancelled';
$GLOBALS['order']->metadata['_brendigo_pay_txn'] = str_repeat('a', 64);
BP_Pay_Gateway::poll_order(17, 1);
ensure(!$GLOBALS['order']->is_paid() && count($GLOBALS['order']->notes) === 1, 'cancelled orders are not auto-completed');
foreach (['paypal', 'revolut'] as $provider) {
    $GLOBALS['order'] = new WC_Order();
    $_POST['bp_provider'] = $provider;
    $redirect = $gateway->process_payment(17);
    ensure($redirect['result'] === 'success' && $GLOBALS['order']->get_meta('_brendigo_pay_provider') === $provider,
        'WooCommerce routes ' . $provider . ' through the selected sandbox provider');
    BP_Pay_Gateway::poll_order(17, 1);
    ensure($GLOBALS['order']->is_paid(), 'WooCommerce checks matching ' . $provider . ' confirmation');
    unset($_POST['bp_provider']);
}
echo "All offline WooCommerce gateway tests passed.\n";
