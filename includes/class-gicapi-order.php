<?php

if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Order
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
     * Get mapped variant SKU for a WooCommerce product
     * 
     * @param int $product_id The product ID
     * @param int $variation_id The variation ID (if applicable)
     * @return string|false The variant SKU or false if not mapped
     */
    public function get_mapped_variant_sku($product_id, $variation_id = 0)
    {
        // Determine which product ID to use for mapping
        $mapping_product_id = $variation_id ? $variation_id : $product_id;

        // Get mapped variant SKUs from the new mapping system
        $mapped_variant_skus = get_post_meta($mapping_product_id, '_gicapi_mapped_variant_skus', true);

        // Fallback to old system for backward compatibility
        if (empty($mapped_variant_skus)) {
            $variant_sku = get_post_meta($mapping_product_id, '_gic_variant_sku', true);
            if ($variant_sku) {
                $mapped_variant_skus = array($variant_sku);
            }
        }

        // Ensure mapped_variant_skus is an array
        if (!is_array($mapped_variant_skus)) {
            $mapped_variant_skus = array($mapped_variant_skus);
        }

        // Filter out empty values
        $mapped_variant_skus = array_filter($mapped_variant_skus);

        if (empty($mapped_variant_skus)) {
            return false;
        }

        // Use the first mapped variant SKU
        return reset($mapped_variant_skus);
    }

    /**
     * Update a single order's Gift-i-Card orders
     */
    public function update_single_order($wc_order_id)
    {
        $order = wc_get_order($wc_order_id);
        if (!$order) {
            error_log("GICAPI Cron: WooCommerce order {$wc_order_id} not found");
            return false;
        }

        $gicapi_orders = get_post_meta($wc_order_id, '_gicapi_orders', true);
        if (empty($gicapi_orders) || !is_array($gicapi_orders)) {
            return false;
        }

        $all_mapped = false;
        $any_mapped = false;
        $mapped_count = 0;
        foreach ($order->get_items() as $item) {

            $variant_sku = $this->get_mapped_variant_sku($item->get_product_id(), $item->get_variation_id());
            if ($variant_sku) {
                $mapped_count++;
                $any_mapped = true;
            }
        }

        if ($mapped_count === count($order->get_items())) {
            $all_mapped = true;
        }

        $orders_updated = false;
        $all_completed = true;
        $any_failed = false;

        foreach ($gicapi_orders as $key => $gic_order) {
            // Only update orders that are in pending or processing status
            if (!isset($gic_order['status']) || !in_array($gic_order['status'], array('pending', 'processing'))) {
                if ($gic_order['status'] !== 'completed') {
                    $all_completed = false;
                }
                if ($gic_order['status'] === 'failed') {
                    $any_failed = true;
                }
                continue;
            }

            // Validate order_id before making API call
            if (empty($gic_order['order_id'])) {
                error_log("GICAPI Cron: Empty order_id found in WC order {$wc_order_id}, skipping API call");
                continue;
            }

            // Get updated order status from API
            $api_response = $this->api->get_order($gic_order['order_id']);

            if (!$api_response) {
                error_log("GICAPI Cron: Failed to get order status for {$gic_order['order_id']} in WC order {$wc_order_id}");
                continue;
            }

            $old_status = $gic_order['status'];
            $new_status = isset($api_response['status']) ? $api_response['status'] : $old_status;

            // Update order data
            $gicapi_orders[$key]['status'] = $new_status;

            // Add redeem data if order is completed
            if ($new_status === 'completed' && isset($api_response['redeem_data'])) {
                $gicapi_orders[$key]['redeem_data'] = $api_response['redeem_data'];
            }

            // Update other fields if available
            if (isset($api_response['total'])) {
                $gicapi_orders[$key]['total'] = $api_response['total'];
            }
            if (isset($api_response['currency'])) {
                $gicapi_orders[$key]['currency'] = $api_response['currency'];
            }
            if (isset($api_response['completed_at'])) {
                $gicapi_orders[$key]['completed_at'] = $api_response['completed_at'];
            }

            $orders_updated = true;

            // Add order note for status change
            if ($old_status !== $new_status) {
                $order->add_order_note(sprintf(
                    __('Gift-i-Card order %s status updated from %s to %s', 'gift-i-card'),
                    $gic_order['order_id'],
                    $old_status,
                    $new_status
                ));
            }

            // Check completion status
            if ($new_status !== 'completed') {
                $all_completed = false;
            }
            if ($new_status === 'failed') {
                $any_failed = true;
            }
        }

        // Update the orders meta
        if ($orders_updated) {
            update_post_meta($wc_order_id, '_gicapi_orders', $gicapi_orders);
        }

        // Handle automatic order status changes based on settings
        $this->handle_automatic_status_changes($order, $all_completed, $any_failed, $all_mapped, $any_mapped);

        return true;
    }


    /**
     * Handle automatic WooCommerce order status changes
     */
    private function handle_automatic_status_changes($order, $all_completed, $any_failed, $all_mapped, $any_mapped)
    {
        $auto_complete_orders = get_option('gicapi_auto_complete_orders', 'none');
        $change_failed_status = get_option('gicapi_change_failed_status', 'none');

        $complete_status = get_option('gicapi_complete_status', 'wc-completed');
        $failed_status = get_option('gicapi_failed_status', 'wc-failed');

        if ($all_mapped) {
            if ($all_completed) {
                if ($auto_complete_orders === 'all-mapped') {
                    $order->update_status($complete_status, __('Order completed because all mapped items are completed', 'gift-i-card'));
                }
            } elseif ($any_failed) {
                if ($change_failed_status === 'all-mapped') {
                    $order->update_status($failed_status, __('Order failed because one or more mapped items are failed', 'gift-i-card'));
                }
            }
        } elseif ($any_mapped) {
            if ($all_completed) {
                if ($auto_complete_orders === 'any-mapped') {
                    $order->update_status($complete_status, __('Order completed because one or more mapped items are completed', 'gift-i-card'));
                }
            } elseif ($any_failed) {
                if ($change_failed_status === 'any-mapped') {
                    $order->update_status($failed_status, __('Order failed because one or more mapped items are failed', 'gift-i-card'));
                }
            }
        }
    }

    /**
     * Get all WooCommerce orders that have Gift-i-Card orders in pending or processing status
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
     * Main cron job function to update pending and processing orders
     */
    public function update_processing_orders()
    {
        // Check if cron job is enabled
        if (get_option('gicapi_enable_cron_updates', 'no') !== 'yes') {
            return;
        }

        // Get pending and processing orders
        $active_orders = $this->get_processing_orders();

        if (empty($active_orders)) {
            return;
        }

        $updated_count = 0;
        $error_count = 0;

        foreach ($active_orders as $order_id) {

            if (empty($order_id)) {
                continue;
            }

            if (!is_numeric($order_id)) {
                continue;
            }

            $result = $this->update_single_order($order_id);

            if ($result === true) {
                $updated_count++;
            } else {
                $error_count++;
            }

            // Add small delay to avoid overwhelming the API
            usleep(300000); // 0.3 seconds (reduced for faster processing)
        }

        // Log results
        error_log(sprintf(
            'GICAPI Cron: Updated %d orders, %d errors out of %d total pending/processing orders',
            $updated_count,
            $error_count,
            count($active_orders)
        ));
    }
}
