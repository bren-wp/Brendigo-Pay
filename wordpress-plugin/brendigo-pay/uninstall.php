<?php
/**
 * Brendigo Pay privacy cleanup on explicit WordPress uninstall.
 * Never delete WooCommerce orders, products or unrelated WordPress data.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
foreach ([
    'brendigo_pay_settings',
    'bp_pay_installation_id',
    'bp_pay_sync_status',
    'bp_pay_sync_log',
    'woocommerce_brendigo_pay_settings',
] as $option) {
    delete_option($option);
}
if (function_exists('wp_clear_scheduled_hook')) {
    wp_clear_scheduled_hook('brendigo_pay_site_sync');
}
