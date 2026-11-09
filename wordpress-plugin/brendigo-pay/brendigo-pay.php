<?php
/**
 * Plugin Name: Brendigo Pay
 * Plugin URI: https://brendigopay.com
 * Description: Sandbox integracija Brendigo Pay naplatnih poveznica za WordPress i WooCommerce (Stripe, PayPal, Revolut).
 * Version: 0.8.15
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Brendigo
 * Author URI: https://brendigo.com
 * Text Domain: brendigo-pay
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('BP_PAY_VERSION', '0.8.15');
define('BP_PAY_FILE', __FILE__);

define('BP_PAY_PATH', plugin_dir_path(__FILE__));

// WC orders are accessed only through WooCommerce CRUD; no direct postmeta/SQL writes.
add_action('before_woocommerce_init', static function (): void {
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', BP_PAY_FILE, true);
    }
});

require_once BP_PAY_PATH . 'includes/class-bp-settings.php';
require_once BP_PAY_PATH . 'includes/class-bp-api.php';

add_action('admin_menu', ['BP_Pay_Settings', 'menu']);
add_action('admin_init', ['BP_Pay_Settings', 'register']);
add_action('admin_post_brendigo_pay_test_connection', ['BP_Pay_Settings', 'test_connection']);
add_action('admin_post_brendigo_pay_activate_license', ['BP_Pay_Settings', 'activate_license']);
add_action('admin_post_brendigo_pay_sync', ['BP_Pay_Settings', 'manual_sync']);
add_action('brendigo_pay_site_sync', ['BP_Pay_Settings', 'sync_installation']);
add_action('init', ['BP_Pay_Settings', 'schedule_sync'], 20);
if (function_exists('register_activation_hook')) {
    register_activation_hook(BP_PAY_FILE, ['BP_Pay_Settings', 'schedule_sync']);
}
if (function_exists('register_deactivation_hook')) {
    register_deactivation_hook(BP_PAY_FILE, ['BP_Pay_Settings', 'unschedule_sync']);
}
add_shortcode('brendigo_pay', 'bp_pay_shortcode');

function bp_pay_shortcode($atts = []): string
{
    // WordPress may pass a non-array value to a shortcode callback.
    // Never let malformed shortcode attributes trigger a TypeError.
    $atts = is_array($atts) ? $atts : [];
    $atts = shortcode_atts([
        'id' => '', 'label' => __('Plati putem Brendigo Paya', 'brendigo-pay'),
        'style' => 'primary', 'show_qr' => 'no', 'show_icon' => 'yes', 'qr_caption' => 'Skenirajte za testno plaćanje', 'layout' => 'button',
    ], $atts, 'brendigo_pay');
    $id = sanitize_text_field((string) $atts['id']);
    if (!preg_match('/^[a-f0-9]{24}$/D', $id)) {
        return '';
    }
    $base = BP_Pay_Settings::api_base();
    if ($base === '') { return ''; }
    // Lookup is validated against the authenticated merchant API; amounts are never taken from editor input.
    $info = BP_Pay_API::request('GET', '/api/v1/links/' . $id);
    if (is_wp_error($info) || !is_array($info) || ($info['id'] ?? '') !== $id) {
        return '<p class="bp-pay-unavailable">' . esc_html__('Naplatna poveznica nije dostupna.', 'brendigo-pay') . '</p>';
    }
    $url = $base . '/p/' . $id;
    $style = in_array($atts['style'], ['primary', 'outline', 'ghost'], true) ? $atts['style'] : 'primary';
    $label = sanitize_text_field((string) $atts['label']);
    $title = sanitize_text_field((string) ($info['title'] ?? ''));
    $minor = (int) ($info['amount_minor'] ?? 0);
    $price = number_format($minor / 100, 2, ',', '.') . ' €';
    $qr = $atts['show_qr'] === 'yes';
    $icon = $atts['show_icon'] === 'yes';
    $qrCaption = sanitize_text_field((string) $atts['qr_caption']);
    $qrCaption = function_exists('mb_substr') ? mb_substr($qrCaption, 0, 90) : substr($qrCaption, 0, 90);
    $layout = in_array($atts['layout'], ['button', 'card'], true) ? $atts['layout'] : 'button';
    wp_enqueue_style('bp-pay-public');
    if ($qr) { wp_enqueue_script('bp-pay-local-qr'); }
    $html = '<div class="bp-pay-widget bp-pay-widget-' . esc_attr($layout) . '">';
    if ($layout === 'card') {
        $html .= '<div class="bp-pay-widget-head"><img src="' . esc_url(plugins_url('assets/brand-mark.svg', BP_PAY_FILE)) . '" alt="" width="31" height="31"><span>Brendigo <b>Pay</b></span></div>';
        $html .= '<div class="bp-pay-widget-body"><h3>' . esc_html($title) . '</h3><p class="bp-pay-amount">' . esc_html($price) . '</p>';
    }
    $html .= '<a class="bp-pay-button bp-pay-' . esc_attr($style) . '" href="' . esc_url($url) . '" rel="nofollow noopener">' . ($icon ? '<span class="bp-pay-button-icon" aria-hidden="true">▣</span>' : '') . esc_html($label) . ' <span aria-hidden="true">→</span></a>';
    if ($qr) {
        $html .= '<div class="bp-pay-qr-wrap"><div class="bp-pay-qr" data-qr="' . esc_attr($url) . '"></div><small>' . esc_html($qrCaption) . '</small></div>';
    }
    if ($layout === 'card') { $html .= '</div>'; }
    return $html . '</div>';
}

// Gutenberg reads only the merchant's active payment links through an authenticated WP REST proxy.
// No API key or provider secret is ever exposed to the browser.
add_action('rest_api_init', static function (): void {
    register_rest_route('brendigo-pay/v1', '/links', [
        'methods' => 'GET',
        'permission_callback' => static fn(): bool => current_user_can('edit_posts'),
        'callback' => static function () {
            $response = BP_Pay_API::request('GET', '/api/v1/links');
            if (is_wp_error($response)) {
                return new WP_Error('bp_links_unavailable', __('Naplatne poveznice nisu dostupne. Provjerite konfiguraciju.', 'brendigo-pay'), ['status' => 503]);
            }
            $items = [];
            foreach (($response['items'] ?? []) as $link) {
                if (!is_array($link) || !preg_match('/^[a-f0-9]{24}$/D', (string) ($link['id'] ?? ''))) {
                    continue;
                }
                $items[] = [
                    'id' => sanitize_text_field((string) $link['id']),
                    'title' => sanitize_text_field((string) ($link['title'] ?? '')),
                    'description' => sanitize_textarea_field((string) ($link['description'] ?? '')),
                    'amount_minor' => max(0, (int) ($link['amount_minor'] ?? 0)),
                    'currency' => 'EUR',
                ];
            }
            return rest_ensure_response(['items' => $items, 'total' => count($items)]);
        },
    ]);
});

add_action('init', static function (): void {
    wp_register_style('bp-pay-public', plugins_url('assets/public.css', BP_PAY_FILE), [], BP_PAY_VERSION);
    wp_register_script('bp-pay-local-qr', plugins_url('assets/qr-bundle.js', BP_PAY_FILE), [], BP_PAY_VERSION, true);
    wp_register_script(
        'bp-pay-block', plugins_url('assets/block.js', BP_PAY_FILE),
        ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-api-fetch'],
        BP_PAY_VERSION, true
    );
    $common = [
        'id' => ['type' => 'string', 'default' => ''],
        'label' => ['type' => 'string', 'default' => 'Plati putem Brendigo Paya'],
        'title' => ['type' => 'string', 'default' => 'Plaćanje za uslugu'],
        'style' => ['type' => 'string', 'default' => 'primary'],
        'show_qr' => ['type' => 'boolean', 'default' => false],
        'show_icon' => ['type' => 'boolean', 'default' => true],
        'qr_caption' => ['type' => 'string', 'default' => 'Skenirajte za testno plaćanje'],
    ];
    foreach (['payment-button' => 'button', 'payment-link' => 'button', 'qr-code' => 'card', 'payment-card' => 'card'] as $slug => $layout) {
        register_block_type('brendigo-pay/' . $slug, [
            'api_version' => 2,
            'attributes' => $common,
            'editor_script' => 'bp-pay-block',
            'render_callback' => static function (array $attributes) use ($slug, $layout): string {
                $attributes['layout'] = $layout;
                $attributes['show_icon'] = !empty($attributes['show_icon']) ? 'yes' : 'no';
                if ($slug === 'qr-code') { $attributes['show_qr'] = 'yes'; }
                else { $attributes['show_qr'] = !empty($attributes['show_qr']) ? 'yes' : 'no'; }
                return bp_pay_shortcode($attributes);
            },
        ]);
    }
});

add_action('wp_enqueue_scripts', static function (): void {
    global $post;
    if (!$post instanceof WP_Post) {
        return;
    }
    $used = has_shortcode($post->post_content, 'brendigo_pay');
    foreach (['payment-button', 'payment-link', 'qr-code', 'payment-card'] as $slug) {
        if (has_block('brendigo-pay/' . $slug, $post)) {
            $used = true;
            break;
        }
    }
    if ($used) {
        wp_enqueue_style('bp-pay-public');
    }
});

add_action('plugins_loaded', static function (): void {
    if (!class_exists('WC_Payment_Gateway')) {
        return; // WordPress-only functionality remains available.
    }
    require_once BP_PAY_PATH . 'includes/class-bp-gateway.php';
    add_filter('woocommerce_payment_gateways', static function (array $gateways): array {
        $gateways[] = 'BP_Pay_Gateway';
        return $gateways;
    });
    add_action('brendigo_pay_poll_order', ['BP_Pay_Gateway', 'poll_order'], 10, 2);
    add_filter('woocommerce_order_actions', static function (array $actions): array {
        global $theorder;
        if ($theorder instanceof WC_Order && $theorder->get_payment_method() === 'brendigo_pay') {
            $actions['brendigo_pay_refresh'] = __('Provjeri Brendigo Pay transakciju', 'brendigo-pay');
        }
        return $actions;
    });
    add_action('woocommerce_order_action_brendigo_pay_refresh', static function ($order): void {
        if ($order instanceof WC_Order) {
            BP_Pay_Gateway::poll_order($order->get_id(), 0);
        }
    });
    add_action('woocommerce_thankyou_brendigo_pay', static function ($orderId): void {
        BP_Pay_Gateway::poll_order((int) $orderId, 0);
    });
});

add_action('woocommerce_blocks_loaded', static function (): void {
    if (!class_exists('Automattic\\WooCommerce\\Blocks\\Payments\\Integrations\\AbstractPaymentMethodType')) {
        return;
    }
    require_once BP_PAY_PATH . 'includes/class-bp-blocks.php';
    add_action('woocommerce_blocks_payment_method_type_registration', static function ($registry): void {
        $registry->register(new BP_Pay_Blocks());
    });
});

// Design assets are loaded only on relevant WordPress/WooCommerce pages.
add_action('admin_enqueue_scripts', static function (string $hook): void {
    if ($hook === 'toplevel_page_brendigo-pay' || str_starts_with($hook, 'brendigo-pay_page_brendigo-pay-')) {
        wp_enqueue_style('bp-pay-admin', plugins_url('assets/admin.css', BP_PAY_FILE), [], BP_PAY_VERSION);
        wp_enqueue_script('bp-pay-admin', plugins_url('assets/admin.js', BP_PAY_FILE), [], BP_PAY_VERSION, true);
    }
    if (function_exists('get_current_screen')) {
        $screen = get_current_screen();
        if ($screen && $screen->is_block_editor()) {
            wp_enqueue_style('bp-pay-editor', plugins_url('assets/editor.css', BP_PAY_FILE), [], BP_PAY_VERSION);
        }
    }
});
add_action('wp_enqueue_scripts', static function (): void {
    if (function_exists('is_checkout') && is_checkout()) {
        wp_enqueue_style('bp-pay-public');
    }
});
