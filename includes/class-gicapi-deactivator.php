<?php
if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Deactivator
{
    public static function deactivate()
    {
        self::delete_options();
        flush_rewrite_rules();
    }

    private static function delete_options()
    {
        delete_option('gicapi_base_url');
        delete_option('gicapi_consumer_key');
        delete_option('gicapi_consumer_secret');
        delete_option('gicapi_complete_orders');
        delete_option('gicapi_ignore_other_orders');
        delete_option('gicapi_add_to_email');
        delete_option('gicapi_add_to_order_details');
        delete_option('gicapi_add_to_thank_you');
    }
}
