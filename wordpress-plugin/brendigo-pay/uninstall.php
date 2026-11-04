<?php
/** Only remove settings on explicit plugin uninstall. */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
delete_option('brendigo_pay_settings');
