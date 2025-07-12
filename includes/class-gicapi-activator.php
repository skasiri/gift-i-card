<?php
if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Activator
{
    public static function activate()
    {
        self::create_options();
        self::schedule_cron_job();
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
        add_option('gicapi_failed_status', 'wc-failed');
        add_option('gicapi_change_cancelled_status', 'none');
        add_option('gicapi_cancelled_status', 'wc-cancelled');
        add_option('gicapi_hook_priority', 10);
        add_option('gicapi_add_to_email', 'no');
        add_option('gicapi_add_to_order_details', 'no');
        add_option('gicapi_add_to_thank_you', 'no');

        // Cron job settings
        add_option('gicapi_enable_cron_updates', 'yes');
        add_option('gicapi_cron_interval', 'gicapi_five_minutes');

        // Products synchronization settings
        add_option('gicapi_products_sync_enabled', 'no');
        add_option('gicapi_sync_interval', 300);
        add_option('gicapi_instant_status', 'no_change');
        add_option('gicapi_manual_status', 'no_change');
        add_option('gicapi_outofstock_status', 'no_change');
        add_option('gicapi_deleted_status', 'no_change');
        add_option('gicapi_auto_sync_enabled', 'no');
    }

    private static function schedule_cron_job()
    {
        // Trigger the cron job scheduling
        do_action('gicapi_activate');
    }
}
