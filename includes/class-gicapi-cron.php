<?php

/**
 * Cron job functionality for updating gift card orders
 *
 * @package    GICAPI
 * @subpackage GICAPI/includes
 * @author     Gift-i-Card <info@gift-i-card.com>
 */

if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Cron
{
    private static $instance = null;
    private $api;
    private $order_manager;
    private $cron_hook = 'gicapi_update_processing_orders';
    private $cron_interval = 'gicapi_five_minutes';
    private $product_sync_cron_hook = 'gicapi_sync_products';
    private $product_sync_cron_interval = 'twicedaily';


    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->api = GICAPI_API::get_instance();
        $this->order_manager = GICAPI_Order_Manager::get_instance();
        $this->init_hooks();
    }

    private function init_hooks()
    {
        // Register cron interval
        add_filter('cron_schedules', array($this, 'add_cron_interval'));

        // Register cron hook
        add_action($this->cron_hook, array($this->order_manager, 'update_processing_orders'));

        // Register product sync cron hook
        add_action($this->product_sync_cron_hook, array($this, 'sync_products_cron'));

        // Activation/deactivation hooks
        add_action('gicapi_activate', array($this, 'schedule_cron'));
        add_action('gicapi_deactivate', array($this, 'unschedule_cron'));

        // Manual trigger for testing
        add_action('wp_ajax_gicapi_manual_update_orders', array($this, 'manual_update_orders'));

        // Debug tool to reschedule the cron job (AJAX)
        add_action('wp_ajax_gicapi_reschedule_cron', array($this, 'ajax_reschedule_cron'));

        // Debug tool to test cron execution (AJAX)
        add_action('wp_ajax_gicapi_test_cron_execution', array($this, 'ajax_test_cron_execution'));

        // Reschedule cron when interval changes
        add_action('update_option_gicapi_cron_interval', array($this, 'reschedule_cron_on_interval_change'), 10, 3);

        // Reschedule cron when enable setting changes
        add_action('update_option_gicapi_enable_cron_updates', array($this, 'reschedule_cron_on_enable_change'), 10, 3);

        // Reschedule product sync cron when settings change
        add_action('update_option_gicapi_products_sync_enabled', array($this, 'reschedule_product_sync_cron_on_enable_change'), 10, 3);
        add_action('update_option_gicapi_products_sync_interval', array($this, 'reschedule_product_sync_cron_on_interval_change'), 10, 3);

        // Debug tools
        add_action('wp_ajax_gicapi_check_repair_cron', array($this, 'ajax_check_repair_cron'));
    }

    /**
     * Add custom cron interval (15 minutes)
     */
    public function add_cron_interval($schedules)
    {
        $schedules[$this->cron_interval] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => __('Every 5 minutes', 'gift-i-card')
        );
        return $schedules;
    }

    /**
     * Schedule the cron job
     */
    public function schedule_cron()
    {
        // Clear any existing cron job first
        $this->unschedule_cron();

        // Get the configured interval
        $configured_interval = get_option('gicapi_cron_interval', $this->cron_interval);

        // Check if cron is enabled
        if (get_option('gicapi_enable_cron_updates', 'yes') !== 'yes') {
            return;
        }

        // Schedule the cron job
        if (!wp_next_scheduled($this->cron_hook)) {
            wp_schedule_event(time(), $configured_interval, $this->cron_hook);
        }

        // Also schedule product sync cron
        $this->schedule_product_sync_cron();
    }

    /**
     * Unschedule the cron job
     */
    public function unschedule_cron()
    {
        $timestamp = wp_next_scheduled($this->cron_hook);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $this->cron_hook);
        }
        wp_clear_scheduled_hook($this->cron_hook);

        // Also unschedule product sync cron
        $this->unschedule_product_sync_cron();
    }

    /**
     * Reschedule cron when interval changes
     */
    public function reschedule_cron_on_interval_change($old_value, $new_value, $option)
    {
        if ($old_value !== $new_value) {
            $this->schedule_cron();
        }
    }

    /**
     * Reschedule cron when enable setting changes
     */
    public function reschedule_cron_on_enable_change($old_value, $new_value, $option)
    {
        if ($old_value !== $new_value) {
            if ($new_value === 'yes') {
                $this->schedule_cron();
            } else {
                $this->unschedule_cron();
            }
        }
    }

    /**
     * Schedule the product sync cron job
     */
    public function schedule_product_sync_cron()
    {
        // Clear any existing product sync cron job first
        $this->unschedule_product_sync_cron();

        // Get the configured interval
        $configured_interval = get_option('gicapi_products_sync_interval', $this->product_sync_cron_interval);

        // Check if product sync cron is enabled
        if (get_option('gicapi_products_sync_enabled', 'no') !== 'yes') {
            return;
        }

        // Schedule the product sync cron job
        if (!wp_next_scheduled($this->product_sync_cron_hook)) {
            wp_schedule_event(time(), $configured_interval, $this->product_sync_cron_hook);
        }
    }

    /**
     * Unschedule the product sync cron job
     */
    public function unschedule_product_sync_cron()
    {
        $timestamp = wp_next_scheduled($this->product_sync_cron_hook);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $this->product_sync_cron_hook);
        }
        wp_clear_scheduled_hook($this->product_sync_cron_hook);
    }

    /**
     * Reschedule product sync cron when enable setting changes
     */
    public function reschedule_product_sync_cron_on_enable_change($old_value, $new_value, $option)
    {
        if ($old_value !== $new_value) {
            if ($new_value === 'yes') {
                $this->schedule_product_sync_cron();
            } else {
                $this->unschedule_product_sync_cron();
            }
        }
    }

    /**
     * Reschedule product sync cron when interval changes
     */
    public function reschedule_product_sync_cron_on_interval_change($old_value, $new_value, $option)
    {
        if ($old_value !== $new_value) {
            $this->schedule_product_sync_cron();
        }
    }

    /**
     * Product sync cron job handler
     */
    public function sync_products_cron()
    {
        // Check if product sync is enabled
        if (get_option('gicapi_products_sync_enabled', 'no') !== 'yes') {
            return;
        }

        // Get the product sync class instance
        $product_sync = GICAPI_Product_Sync::get_instance();
        if (!$product_sync) {
            return;
        }

        // Run the product sync
        $product_sync->sync_all_products();
    }


    /**
     * Manual trigger for testing (AJAX)
     */
    public function manual_update_orders()
    {
        // Verify nonce
        check_ajax_referer('gicapi_manual_update_orders', 'nonce');

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        // Run the update
        $this->order_manager->update_processing_orders();

        wp_send_json_success(__('Manual order update completed', 'gift-i-card'));
    }

    /**
     * Debug tool to reschedule the cron job (AJAX)
     */
    public function ajax_reschedule_cron()
    {
        // Verify nonce
        check_ajax_referer('gicapi_reschedule_cron', 'nonce');

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        // Force reschedule the cron job
        $this->force_reschedule();

        wp_send_json_success(__('Cron job rescheduled successfully', 'gift-i-card'));
    }

    /**
     * Debug tool to test cron execution (AJAX)
     */
    public function ajax_test_cron_execution()
    {
        // Verify nonce
        check_ajax_referer('gicapi_test_cron_execution', 'nonce');

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        // Capture logs for this test run
        $logs = array();

        // Check if cron is enabled
        if (get_option('gicapi_enable_cron_updates', 'yes') !== 'yes') {
            $logs[] = 'Cron job is disabled in settings';
            wp_send_json_success(array('logs' => $logs));
            return;
        }

        // Check if API is configured
        if (!$this->api || !$this->api->is_configured()) {
            $logs[] = 'API is not properly configured';
            wp_send_json_success(array('logs' => $logs));
            return;
        }

        $logs[] = 'API is properly configured';

        // Get pending and processing orders
        $active_orders = $this->order_manager->get_processing_orders();
        $logs[] = 'Found ' . count($active_orders) . ' orders to update';

        if (empty($active_orders)) {
            $logs[] = 'No pending or processing orders found';
            wp_send_json_success(array('logs' => $logs));
            return;
        }

        $updated_count = 0;
        $error_count = 0;

        foreach ($active_orders as $order_id) {
            try {
                $result = $this->order_manager->update_single_order($order_id);
                if ($result === true) {
                    $updated_count++;
                    $logs[] = 'Successfully updated order ' . $order_id;
                } else {
                    $error_count++;
                    $logs[] = 'Failed to update order ' . $order_id;
                }
            } catch (Exception $e) {
                $error_count++;
                $logs[] = 'Exception while updating order ' . $order_id . ': ' . $e->getMessage();
            }
        }

        $logs[] = 'Test completed - Updated: ' . $updated_count . ', Errors: ' . $error_count;

        wp_send_json_success(array('logs' => $logs));
    }

    /**
     * Get cron status information
     */
    public function get_cron_status()
    {
        $next_scheduled = wp_next_scheduled($this->cron_hook);
        $is_enabled = get_option('gicapi_enable_cron_updates', 'yes') === 'yes';
        $configured_interval = get_option('gicapi_cron_interval', $this->cron_interval);

        return array(
            'enabled' => $is_enabled,
            'next_run' => $next_scheduled ? gmdate('Y-m-d H:i:s', $next_scheduled) : null,
            'interval' => $configured_interval,
            'hook' => $this->cron_hook,
            'is_scheduled' => $next_scheduled !== false
        );
    }

    /**
     * Force reschedule the cron job (for debugging)
     */
    public function force_reschedule()
    {
        $this->schedule_cron();
    }

    /**
     * Check and repair cron job if needed
     */
    public function check_and_repair_cron()
    {
        $configured_interval = get_option('gicapi_cron_interval', $this->cron_interval);
        $is_enabled = get_option('gicapi_enable_cron_updates', 'yes') === 'yes';
        $next_scheduled = wp_next_scheduled($this->cron_hook);

        // If cron is disabled, unschedule it
        if (!$is_enabled) {
            if ($next_scheduled !== false) {
                $this->unschedule_cron();
            }
            return false;
        }

        // If cron is enabled but not scheduled, schedule it
        if ($next_scheduled === false) {
            $this->schedule_cron();
            return true;
        }

        // Check if the scheduled interval matches the configured interval
        $scheduled_events = _get_cron_array();
        $current_interval = null;

        foreach ($scheduled_events as $timestamp => $events) {
            if (isset($events[$this->cron_hook])) {
                foreach ($events[$this->cron_hook] as $event) {
                    if (isset($event['schedule'])) {
                        $current_interval = $event['schedule'];
                        break 2;
                    }
                }
            }
        }

        // If interval doesn't match, reschedule
        if ($current_interval !== $configured_interval) {
            $this->schedule_cron();
            return true;
        }

        return false;
    }

    /**
     * AJAX handler for checking and repairing the cron job
     */
    public function ajax_check_repair_cron()
    {
        // Verify nonce
        check_ajax_referer('gicapi_check_repair_cron', 'nonce');

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        // Run the check and repair
        $result = $this->check_and_repair_cron();

        if ($result) {
            wp_send_json_success(__('Cron job checked and repaired successfully', 'gift-i-card'));
        } else {
            wp_send_json_error(__('Cron job check and repair failed', 'gift-i-card'));
        }
    }
}
