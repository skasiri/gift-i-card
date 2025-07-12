<?php
if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Deactivator
{
    public static function deactivate()
    {
        self::delete_options();
        self::unschedule_cron_job();
        flush_rewrite_rules();
    }

    private static function delete_options()
    {
        delete_option('gicapi_consumer_secret');
    }

    private static function unschedule_cron_job()
    {
        // Trigger the cron job unscheduling
        do_action('gicapi_deactivate');
    }
}
