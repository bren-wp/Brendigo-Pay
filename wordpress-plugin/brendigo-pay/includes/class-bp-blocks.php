<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class BP_Pay_Blocks extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType
{
    protected $name = 'brendigo_pay';
    // WooCommerce AbstractPaymentMethodType already declares protected $settings.
    // Never redeclare it as private or typed: PHP fatals when Blocks loads.

    public function initialize(): void
    {
        $saved = get_option('woocommerce_brendigo_pay_settings', []);
        $this->settings = is_array($saved) ? $saved : [];
    }

    public function is_active(): bool
    {
        return ($this->settings['enabled'] ?? 'no') === 'yes'
            && BP_Pay_Settings::api_key() !== '' && BP_Pay_Settings::api_base() !== ''
            && (!function_exists('get_woocommerce_currency') || get_woocommerce_currency() === 'EUR');
    }

    public function get_payment_method_script_handles(): array
    {
        wp_register_script(
            'bp-pay-woocommerce-block',
            plugins_url('assets/woocommerce-block.js', BP_PAY_FILE),
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n'],
            BP_PAY_VERSION,
            true
        );
        return ['bp-pay-woocommerce-block'];
    }

    public function get_payment_method_data(): array
    {
        return [
            'title' => (string) ($this->settings['title'] ?? 'Brendigo Pay (TEST)'),
            'description' => (string) ($this->settings['description'] ?? 'Stripe test checkout'),
            'providers' => [
                ['id' => 'stripe', 'label' => 'Stripe TEST'],
                ['id' => 'paypal', 'label' => 'PayPal Sandbox'],
                ['id' => 'revolut', 'label' => 'Revolut Sandbox'],
            ],
            'icon' => plugins_url('assets/brand-mark.svg', BP_PAY_FILE),
            'defaultProvider' => (string) ($this->settings['default_provider'] ?? 'stripe'),
            'supports' => ['products'],
        ];
    }
}
