<?php
/** Isolated privacy-cleanup test (does not run WordPress). */
declare(strict_types=1);
define('WP_UNINSTALL_PLUGIN', true);
$deleted = [];
$cleared = [];
function delete_option(string $key): void { global $deleted; $deleted[] = $key; }
function wp_clear_scheduled_hook(string $name): void { global $cleared; $cleared[] = $name; }
require dirname(__DIR__) . '/wordpress-plugin/brendigo-pay/uninstall.php';
$expected = ['brendigo_pay_settings','bp_pay_installation_id','bp_pay_sync_status',
             'bp_pay_sync_log','woocommerce_brendigo_pay_settings'];
if ($deleted !== $expected || $cleared !== ['brendigo_pay_site_sync']) {
    throw new RuntimeException('WordPress uninstall did not purge its own options and cron event');
}
echo "PASS: WordPress uninstall clears plugin credentials, sync metadata and cron; preserves WooCommerce orders\n";
