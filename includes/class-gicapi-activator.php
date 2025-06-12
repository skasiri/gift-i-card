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
        add_option('gicapi_complete_orders', 'yes');
        add_option('gicapi_ignore_other_orders', 'yes');
        add_option('gicapi_add_to_email', 'yes');
        add_option('gicapi_add_to_order_details', 'yes');
        add_option('gicapi_add_to_thank_you', 'yes');
    }
}
