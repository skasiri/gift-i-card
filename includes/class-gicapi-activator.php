<?php
if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Activator
{
    public static function activate()
    {
        self::create_options();
        flush_rewrite_rules();
    }

    private static function create_options()
    {
        add_option('gicapi_base_url', '');
        add_option('gicapi_consumer_key', '');
        add_option('gicapi_consumer_secret', '');
        add_option('gicapi_complete_status', 'wc-completed');
        add_option('gicapi_enable', 'no');
        add_option('gicapi_gift_i_card_create_order_status', 'wc-pending');
        add_option('gicapi_gift_i_card_confirm_order_status', 'wc-processing');
        add_option('gicapi_auto_complete_orders', 'none');
        add_option('gicapi_change_failed_status', 'none');
        add_option('gicapi_failed_status', 'failed');
        add_option('gicapi_hook_priority', 10);
        add_option('gicapi_add_to_email', 'no');
        add_option('gicapi_add_to_order_details', 'no');
        add_option('gicapi_add_to_thank_you', 'no');
    }
}
