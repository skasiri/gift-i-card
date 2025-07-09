<?php

/**
 * Common order management functionality for GICAPI
 * This class provides shared functions for both cron and webhook systems
 *
 * @package    GICAPI
 * @subpackage GICAPI/includes
 * @author     Gift-i-Card <info@gift-i-card.com>
 */

if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Order_Manager
{
    private static $instance = null;
    private $api;

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
    }

    /**
     * Update a single WooCommerce order with Gift-i-Card data
     * 
     * @param int $wc_order_id WooCommerce order ID
     * @param array $gic_order_data Gift-i-Card order data (optional, for webhook)
     * @param string $source Source of update ('cron', 'webhook', 'manual')
     * @return array Result array with success status and details
     */
    public function update_single_order($wc_order_id, $gic_order_data = null, $source = 'cron')
    {
        $order = wc_get_order($wc_order_id);
        if (!$order) {
            return array(
                'success' => false,
                'error' => 'WooCommerce order not found',
                'wc_order_id' => $wc_order_id
            );
        }

        $gicapi_orders = get_post_meta($wc_order_id, '_gicapi_orders', true);
        if (empty($gicapi_orders) || !is_array($gicapi_orders)) {
            return array(
                'success' => false,
                'error' => 'No Gift-i-Card orders found in WooCommerce order',
                'wc_order_id' => $wc_order_id
            );
        }

        $orders_updated = false;
        $updated_orders = array();
        $errors = array();

        foreach ($gicapi_orders as $key => $gic_order) {
            // Skip orders with empty order_id
            if (empty($gic_order['order_id'])) {
                continue;
            }

            // Get updated data based on source
            if ($source === 'webhook' && $gic_order_data && $gic_order['order_id'] === $gic_order_data['order_id']) {
                // Use webhook data directly
                $api_response = $gic_order_data;
            } else {
                // Get data from API (for cron and manual updates)
                $api_response = $this->api->get_order($gic_order['order_id']);
                if (!$api_response) {
                    $errors[] = "Failed to get order status for {$gic_order['order_id']}";
                    continue;
                }
            }

            $old_status = $gic_order['status'];
            $new_status = isset($api_response['status']) ? $api_response['status'] : $old_status;

            // Update order data
            $gicapi_orders[$key]['status'] = $new_status;

            // Update other fields if available
            $this->update_order_fields($gicapi_orders[$key], $api_response);

            $orders_updated = true;
            $updated_orders[] = $gic_order['order_id'];

            // Add order note for status change
            if ($old_status !== $new_status) {
                $this->add_status_change_note($order, $gic_order['order_id'], $old_status, $new_status, $source);
            }
        }

        // Update the orders meta if any changes were made
        if ($orders_updated) {
            update_post_meta($wc_order_id, '_gicapi_orders', $gicapi_orders);
            update_post_meta($wc_order_id, 'last_update_source', $source);
            update_post_meta($wc_order_id, 'last_update_time', current_time('mysql'));
        }

        // Handle automatic order status changes
        $this->handle_automatic_status_changes($order, $gicapi_orders);

        return array(
            'success' => true,
            'wc_order_id' => $wc_order_id,
            'updated_orders' => $updated_orders,
            'errors' => $errors,
            'source' => $source
        );
    }

    /**
     * Update order fields with API response data
     * 
     * @param array &$gic_order Reference to Gift-i-Card order array
     * @param array $api_response API response data
     */
    private function update_order_fields(&$gic_order, $api_response)
    {
        // Add redeem data if order is completed
        if ($gic_order['status'] === 'completed' && isset($api_response['redeem_data'])) {
            $gic_order['redeem_data'] = $api_response['redeem_data'];
        }

        // Update other fields if available
        $fields_to_update = array('total', 'currency', 'completed_at', 'expires_at', 'price');
        foreach ($fields_to_update as $field) {
            if (isset($api_response[$field])) {
                $gic_order[$field] = $api_response[$field];
            }
        }
    }

    /**
     * Add order note for status change
     * 
     * @param WC_Order $order WooCommerce order object
     * @param string $gic_order_id Gift-i-Card order ID
     * @param string $old_status Previous status
     * @param string $new_status New status
     * @param string $source Source of update
     */
    private function add_status_change_note($order, $gic_order_id, $old_status, $new_status, $source)
    {
        $source_text = $source === 'webhook' ? 'via webhook' : 'via ' . $source;
        // translators: %1$s: Gift-i-Card order ID, %2$s: old status, %3$s: new status, %4$s: source
        $order->add_order_note(sprintf(
            __('Gift-i-Card order %1$s status updated from %2$s to %3$s %4$s', 'gift-i-card'),
            $gic_order_id,
            $old_status,
            $new_status,
            $source_text
        ));
    }

    /**
     * Handle automatic WooCommerce order status changes based on Gift-i-Card order statuses
     * 
     * @param WC_Order $order WooCommerce order object
     * @param array $gicapi_orders Array of Gift-i-Card orders
     */
    public function handle_automatic_status_changes($order, $gicapi_orders)
    {
        $auto_complete_orders = get_option('gicapi_auto_complete_orders', 'none');
        $change_failed_status = get_option('gicapi_change_failed_status', 'none');

        $complete_status = get_option('gicapi_complete_status', 'wc-completed');
        $failed_status = get_option('gicapi_failed_status', 'wc-failed');

        // Check if all mapped items are completed
        $all_completed = true;
        $any_failed = false;
        $mapped_count = 0;
        $completed_count = 0;
        $failed_count = 0;

        foreach ($gicapi_orders as $gic_order) {
            if (!empty($gic_order['order_id'])) {
                $mapped_count++;
                if ($gic_order['status'] === 'completed') {
                    $completed_count++;
                } elseif ($gic_order['status'] === 'failed') {
                    $failed_count++;
                    $any_failed = true;
                } else {
                    $all_completed = false;
                }
            }
        }

        if ($mapped_count === 0) {
            return; // No mapped items
        }

        // Check if all mapped items are completed
        if ($completed_count === $mapped_count) {
            $all_completed = true;
        } else {
            $all_completed = false;
        }

        // Apply automatic status changes based on settings
        if ($all_completed && $auto_complete_orders === 'all-mapped') {
            try {
                $order->update_status($complete_status, __('Order completed because all Gift-i-Card orders are completed', 'gift-i-card'));
            } catch (Exception $e) {
                // Error updating order status
            }
        } elseif ($any_failed && $change_failed_status === 'all-mapped') {
            try {
                $order->update_status($failed_status, __('Order failed because one or more Gift-i-Card orders are failed', 'gift-i-card'));
            } catch (Exception $e) {
                // Error updating order status
            }
        }
    }

    /**
     * Get all WooCommerce orders that have Gift-i-Card orders in pending or processing status
     * 
     * @return array Array of WooCommerce order IDs
     */
    public function get_processing_orders()
    {
        global $wpdb;

        // Get orders that have Gift-i-Card orders stored
        $order_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = %s 
            AND meta_value != ''
        ", '_gicapi_orders'));

        if (empty($order_ids)) {
            return array();
        }

        $active_orders = array();

        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if (!$order) {
                continue;
            }

            $gicapi_orders = get_post_meta($order_id, '_gicapi_orders', true);
            if (empty($gicapi_orders) || !is_array($gicapi_orders)) {
                continue;
            }

            // Check if any Gift-i-Card orders are in pending or processing status
            $has_active_status = false;
            foreach ($gicapi_orders as $gic_order) {
                // Skip orders with empty order_id
                if (empty($gic_order['order_id'])) {
                    continue;
                }

                if (isset($gic_order['status']) && in_array($gic_order['status'], array('pending', 'processing'))) {
                    $has_active_status = true;
                    break;
                }
            }

            if ($has_active_status) {
                $active_orders[] = $order_id;
            }
        }

        return $active_orders;
    }

    /**
     * Find WooCommerce orders that contain a specific Gift-i-Card order ID
     * 
     * @param string $gic_order_id Gift-i-Card order ID
     * @return array Array of WooCommerce order IDs
     */
    public function find_wc_orders_by_gic_order_id($gic_order_id)
    {
        global $wpdb;

        // Get orders that have this specific Gift-i-Card order ID
        $order_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = %s 
            AND meta_value LIKE %s
        ", '_gicapi_orders', '%' . $wpdb->esc_like($gic_order_id) . '%'));

        return $order_ids;
    }

    /**
     * Update multiple orders (for cron job)
     * 
     * @return array Result array with update statistics
     */
    public function update_processing_orders()
    {
        // Check if cron job is enabled
        if (get_option('gicapi_enable_cron_updates', 'no') !== 'yes') {
            return array('success' => false, 'error' => 'Cron updates are disabled');
        }

        // Get pending and processing orders
        $active_orders = $this->get_processing_orders();

        if (empty($active_orders)) {
            return array('success' => true, 'message' => 'No active orders to update');
        }

        $updated_count = 0;
        $error_count = 0;
        $errors = array();

        foreach ($active_orders as $order_id) {
            if (empty($order_id) || !is_numeric($order_id)) {
                continue;
            }

            $result = $this->update_single_order($order_id, null, 'cron');

            if ($result['success']) {
                $updated_count++;
            } else {
                $error_count++;
                $errors[] = $result['error'];
            }

            // Add small delay to avoid overwhelming the API
            usleep(300000); // 0.3 seconds
        }



        return array(
            'success' => true,
            'updated_count' => $updated_count,
            'error_count' => $error_count,
            'total_orders' => count($active_orders),
            'errors' => $errors
        );
    }
}
