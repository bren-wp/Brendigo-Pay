<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class BP_Pay_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'brendigo_pay';
        $this->method_title = __('Brendigo Pay (SANDBOX)', 'brendigo-pay');
        $this->method_description = __('Sandbox plaćanje putem Stripea, PayPala ili Revoluta kroz Brendigo Pay.', 'brendigo-pay');
        $this->has_fields = true; // WooCommerce classic must render the provider selector.
        $this->supports = ['products'];
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title', __('Brendigo Pay — TEST', 'brendigo-pay'));
        $this->description = $this->get_option('description', __('Testno plaćanje bez stvarnog terećenja.', 'brendigo-pay'));
        $this->enabled = $this->get_option('enabled', 'no');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => ['title' => __('Omogući', 'brendigo-pay'), 'type' => 'checkbox', 'label' => __('Prikaži Brendigo Pay Sandbox checkout', 'brendigo-pay'), 'default' => 'no'],
            'default_provider' => ['title' => __('Zadani pružatelj', 'brendigo-pay'), 'type' => 'select', 'default' => 'stripe', 'options' => ['stripe' => 'Stripe TEST', 'paypal' => 'PayPal Sandbox', 'revolut' => 'Revolut Sandbox']],
            'title' => ['title' => __('Naziv', 'brendigo-pay'), 'type' => 'text', 'default' => __('Brendigo Pay — TEST', 'brendigo-pay')],
            'description' => ['title' => __('Opis', 'brendigo-pay'), 'type' => 'textarea', 'default' => __('Testno plaćanje — bez stvarnog terećenja.', 'brendigo-pay')],
        ];
    }

    public function is_available(): bool
    {
        return parent::is_available() && BP_Pay_Settings::api_base() !== '' && BP_Pay_Settings::api_key() !== ''
            && BP_Pay_Settings::license_is_current()
            && (!function_exists('get_woocommerce_currency') || get_woocommerce_currency() === 'EUR');
    }

    /** Classic checkout dropdown; Blocks passes the same name through payment_data. */
    public function payment_fields(): void
    {
        echo '<div class="bp-wc-title"><img src="' . esc_url(plugins_url('assets/brand-mark.svg', BP_PAY_FILE)) . '" alt="" width="30" height="30">Brendigo Pay <span class="bp-wc-sandbox-message">SANDBOX</span></div>';
        echo '<p>' . esc_html($this->description) . '</p>';
        $selected = $this->get_option('default_provider', 'stripe');
        $options = [
            'stripe' => ['Stripe TEST', 'Kartično plaćanje na Stripeovoj testnoj stranici'],
            'paypal' => ['PayPal Sandbox', 'PayPal testni račun'],
            'revolut' => ['Revolut Sandbox', 'Testna naplata putem Revoluta'],
        ];
        echo '<div class="bp-wc-steps"><span>① Odabir pružatelja</span><span>② Sigurni testni checkout</span><span>③ Server potvrđuje uplatu</span></div>';
        echo '<div class="bp-wc-provider-list" role="radiogroup" aria-label="' . esc_attr__('Pružatelj plaćanja', 'brendigo-pay') . '">';
        foreach ($options as $id => $provider) {
            echo '<label class="bp-wc-provider"><input type="radio" name="bp_provider" value="' . esc_attr($id) . '" ' . checked($selected, $id, false) . '>';
            echo '<span class="bp-wc-provider-monogram bp-wc-provider-monogram-' . esc_attr($id) . '" aria-hidden="true">' . esc_html(strtoupper($id[0])) . '</span>'; 
            echo '<span class="bp-wc-provider-content"><b>' . esc_html($provider[0]) . '</b><small>' . esc_html($provider[1]) . '</small></span><span class="bp-wc-provider-end" aria-hidden="true">›</span></label>';
        }
        echo '</div><p class="bp-wc-sandbox-message">' . esc_html__('TESTNO IZDANJE — bez stvarnog terećenja.', 'brendigo-pay') . '</p>';
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        if ($order instanceof WC_Order && $order->is_paid()) {
            return ['result' => 'success', 'redirect' => $order->get_checkout_order_received_url()];
        }
        if (!$order instanceof WC_Order || strtoupper($order->get_currency()) !== 'EUR') {
            wc_add_notice(__('Brendigo Pay trenutno podržava samo EUR.', 'brendigo-pay'), 'error');
            return ['result' => 'failure'];
        }
        $minor = (int) round((float) $order->get_total() * 100);
        if ($minor < 1 || $minor > 999999999) {
            wc_add_notice(__('Neispravan iznos narudžbe.', 'brendigo-pay'), 'error');
            return ['result' => 'failure'];
        }
        $requested = isset($_POST['bp_provider']) ? sanitize_key(wp_unslash($_POST['bp_provider'])) : '';
        $provider = in_array($requested, ['stripe', 'paypal', 'revolut'], true)
            ? $requested : $this->get_option('default_provider', 'stripe');
        if (!in_array($provider, ['stripe', 'paypal', 'revolut'], true)) {
            $provider = 'stripe';
        }
        $response = BP_Pay_API::request('POST', '/api/v1/checkout', [
            'provider' => $provider,
            'amount' => number_format($minor / 100, 2, '.', ''),
            'description' => sprintf(__('WooCommerce narudžba %s', 'brendigo-pay'), $order->get_order_number()),
            'reference' => 'wp_' . hash('sha256', home_url('/') . ':' . $order->get_id()),
            'success_url' => $order->get_checkout_order_received_url(),
            'cancel_url' => $order->get_cancel_order_url_raw(),
        ]);
        if (is_wp_error($response) || empty($response['transaction_id']) || empty($response['checkout_url'])) {
            wc_add_notice(__('Testno plaćanje trenutačno nije dostupno.', 'brendigo-pay'), 'error');
            return ['result' => 'failure'];
        }
        $checkoutUrl = (string) $response['checkout_url'];
        $host = wp_parse_url($checkoutUrl, PHP_URL_HOST);
        $approvedHosts = [
            'stripe' => ['checkout.stripe.com'],
            'paypal' => ['www.sandbox.paypal.com', 'sandbox.paypal.com'],
            'revolut' => ['checkout.revolut.com', 'sandbox-checkout.revolut.com', 'checkout.sandbox.revolut.com'],
        ];
        if (!is_string($host) || !in_array(strtolower($host), $approvedHosts[$provider], true)
            || wp_parse_url($checkoutUrl, PHP_URL_SCHEME) !== 'https'
            || ($response['provider'] ?? $provider) !== $provider) {
            wc_add_notice(__('Nevaljana adresa platnog pružatelja.', 'brendigo-pay'), 'error');
            return ['result' => 'failure'];
        }
        $order->update_meta_data('_brendigo_pay_txn', sanitize_text_field((string) $response['transaction_id']));
        $order->update_meta_data('_brendigo_pay_provider', $provider);
        $order->save();
        $order->update_status('on-hold', __('Čeka se potvrda sandbox pružatelja (Stripe / PayPal / Revolut).', 'brendigo-pay'));
        if (!wp_next_scheduled('brendigo_pay_poll_order', [$order->get_id(), 1])) {
            wp_schedule_single_event(time() + 60, 'brendigo_pay_poll_order', [$order->get_id(), 1]);
        }
        return ['result' => 'success', 'redirect' => esc_url_raw($checkoutUrl)];
    }

    public static function poll_order(int $order_id, int $attempt = 0): void
    {
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order || $order->get_payment_method() !== 'brendigo_pay' || $order->is_paid()) {
            return;
        }
        $txn = (string) $order->get_meta('_brendigo_pay_txn', true);
        if (!preg_match('/^[a-f0-9]{32,64}$/D', $txn)) {
            return;
        }
        $response = BP_Pay_API::request('GET', '/api/v1/payments/' . $txn);
        if (!is_wp_error($response) && ($response['status'] ?? '') === 'paid') {
            $reference = 'wp_' . hash('sha256', home_url('/') . ':' . $order_id);
            $amount = (int) round((float) $order->get_total() * 100);
            if (($response['reference'] ?? '') === $reference
                && ($response['currency'] ?? '') === strtoupper($order->get_currency())
                && ($response['amount_minor'] ?? null) === $amount
                && ($response['id'] ?? '') === $txn
                && ($response['provider'] ?? 'stripe') === ($order->get_meta('_brendigo_pay_provider', true) ?: 'stripe')) {
                if ($order->has_status(['cancelled', 'refunded', 'failed'])) {
                    $order->add_order_note(__('Sandbox uplata je potvrđena, ali narudžba zahtijeva ručni pregled zbog postojećeg statusa.', 'brendigo-pay'));
                    return;
                }
                $order->payment_complete($txn);
                $order->add_order_note(__('Sandbox uplata potvrđena putem Brendigo Pay API-ja.', 'brendigo-pay'));
                return;
            }
        }
        if ($attempt > 0 && $attempt < 288 && !wp_next_scheduled('brendigo_pay_poll_order', [$order_id, $attempt + 1])) {
            wp_schedule_single_event(time() + 300, 'brendigo_pay_poll_order', [$order_id, $attempt + 1]);
        }
    }
}
