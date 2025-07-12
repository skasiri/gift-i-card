<?php

/**
 * Product Status Synchronization functionality for GICAPI
 * Handles synchronization of Gift-i-Card product statuses with WooCommerce stock status
 *
 * @package    GICAPI
 * @subpackage GICAPI/includes
 * @author     Gift-i-Card <info@gift-i-card.com>
 */

if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Product_Sync
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
     * Sync product status from Gift-i-Card to WooCommerce
     * 
     * @param int $product_id WooCommerce product ID
     * @param int $variation_id WooCommerce variation ID (optional)
     * @param string $gic_status Gift-i-Card delivery status
     * @return array Result array with success status and details
     */
    public function sync_product_status($product_id, $variation_id = 0, $gic_status = null)
    {
        // Determine which product to sync (variation or main product)
        $sync_product_id = $variation_id > 0 ? $variation_id : $product_id;
        $product = wc_get_product($sync_product_id);

        if (!$product) {
            return array(
                'success' => false,
                'error' => 'WooCommerce product not found',
                'product_id' => $sync_product_id
            );
        }

        // Get Gift-i-Card status if not provided
        if ($gic_status === null) {
            $gic_status = $this->get_gic_product_status($product_id, $variation_id);
            if ($gic_status === false) {
                return array(
                    'success' => false,
                    'error' => 'Failed to get Gift-i-Card product status',
                    'product_id' => $sync_product_id
                );
            }
        }

        // Get status mapping from settings
        $status_mapping = $this->get_status_mapping();
        $target_status = $this->get_target_status($gic_status, $status_mapping);

        // Update WooCommerce stock status
        $result = $this->update_woocommerce_stock_status($product, $target_status);

        // Log the sync operation
        $this->log_sync_operation($sync_product_id, $gic_status, $target_status, $result['success']);

        return $result;
    }

    /**
     * Get Gift-i-Card product status from API
     * 
     * @param int $product_id WooCommerce product ID
     * @param int $variation_id WooCommerce variation ID (optional)
     * @return string|false Gift-i-Card status or false on failure
     */
    private function get_gic_product_status($product_id, $variation_id = 0)
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }

        // Get the mapped SKU for this product/variation
        $gicapi_order = GICAPI_Order::get_instance();
        $variant_sku = $gicapi_order->get_mapped_variant_sku($product_id, $variation_id);

        if (!$variant_sku) {
            return false;
        }

        // Get product status from Gift-i-Card API
        // For variants, we need to get the parent product SKU first
        $parent_sku = $this->get_parent_sku_from_variant_sku($variant_sku, $product_id, $variation_id);
        if (!$parent_sku) {
            return false;
        }

        $api_response = $this->api->get_variants($parent_sku);
        if (!$api_response) {
            return false;
        }

        // Find the specific variant in the response
        $variant_status = $this->find_variant_status_in_response($api_response, $variant_sku);
        if ($variant_status === false) {
            return false;
        }

        return $variant_status;
    }

    /**
     * Get parent product SKU for API call
     * 
     * @param string $variant_sku The variant SKU
     * @param int $product_id WooCommerce product ID
     * @param int $variation_id WooCommerce variation ID
     * @return string|false Parent SKU or false if not found
     */
    private function get_parent_sku_from_variant_sku($variant_sku, $product_id, $variation_id = 0)
    {
        // Get the product object
        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }

        // If this is a simple product, use the variant SKU as-is
        if ($product->is_type('simple')) {
            return $variant_sku;
        }

        // If this is a variable product, we need to get the parent product SKU
        if ($product->is_type('variable')) {
            // For variable products, we need to find the parent product SKU
            // Get all mapped products that have this variant SKU
            $args = array(
                'post_type' => array('product', 'product_variation'),
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_gicapi_mapped_variant_skus',
                        'value' => $variant_sku,
                        'compare' => 'LIKE'
                    )
                )
            );

            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $mapped_product_id = get_the_ID();
                    $mapped_product = wc_get_product($mapped_product_id);

                    if ($mapped_product) {
                        // Get the parent product SKU from the mapping
                        $mapped_category_skus = get_post_meta($mapped_product_id, '_gicapi_mapped_category_skus', true);
                        $mapped_product_skus = get_post_meta($mapped_product_id, '_gicapi_mapped_product_skus', true);

                        if (is_array($mapped_product_skus) && !empty($mapped_product_skus)) {
                            $parent_sku = reset($mapped_product_skus);
                            wp_reset_postdata();
                            return $parent_sku;
                        }
                    }
                }
            }
            wp_reset_postdata();
        }

        // If this is a variation, get the parent product
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();
            if ($parent_id) {
                // Get the parent product SKU mapping
                $gicapi_order = GICAPI_Order::get_instance();
                $parent_sku = $gicapi_order->get_mapped_variant_sku($parent_id, 0);
                if ($parent_sku) {
                    return $parent_sku;
                }
            }
        }

        return false;
    }

    /**
     * Find variant status in API response
     * 
     * @param array $api_response The API response
     * @param string $variant_sku The variant SKU to find
     * @return string|false Variant status or false if not found
     */
    private function find_variant_status_in_response($api_response, $variant_sku)
    {
        // Check if response is an array of variants (direct array)
        if (is_array($api_response) && !empty($api_response)) {
            foreach ($api_response as $variant) {
                if (isset($variant['sku']) && $variant['sku'] === $variant_sku) {
                    return isset($variant['stock_status']) ? $variant['stock_status'] : false;
                }
            }
        }

        // If response has variants array, search for the specific variant
        if (isset($api_response['variants']) && is_array($api_response['variants'])) {
            foreach ($api_response['variants'] as $variant) {
                if (isset($variant['sku']) && $variant['sku'] === $variant_sku) {
                    return isset($variant['stock_status']) ? $variant['stock_status'] : false;
                }
            }
        }

        // If response has a single status field (for simple products)
        if (isset($api_response['status'])) {
            return $api_response['status'];
        }

        return false;
    }

    /**
     * Get status mapping from settings
     * 
     * @return array Status mapping configuration
     */
    private function get_status_mapping()
    {
        return array(
            'instant' => get_option('gicapi_instant_status', 'no_change'),
            'manual' => get_option('gicapi_manual_status', 'no_change'),
            'outofstock' => get_option('gicapi_outofstock_status', 'no_change'),
            'deleted' => get_option('gicapi_deleted_status', 'no_change')
        );
    }

    /**
     * Get target WooCommerce status based on Gift-i-Card status
     * 
     * @param string $gic_status Gift-i-Card status
     * @param array $status_mapping Status mapping configuration
     * @return string Target WooCommerce status
     */
    private function get_target_status($gic_status, $status_mapping)
    {
        // Map Gift-i-Card status to mapping key
        $status_key = $this->map_gic_status_to_key($gic_status);

        if (!isset($status_mapping[$status_key])) {
            return 'no_change';
        }

        return $status_mapping[$status_key];
    }

    /**
     * Map Gift-i-Card status to mapping key
     * 
     * @param string $gic_status Gift-i-Card status
     * @return string Mapping key
     */
    private function map_gic_status_to_key($gic_status)
    {
        $status_mapping = array(
            'instant' => 'instant',
            'automatic' => 'instant',
            'manual' => 'manual',
            'operator' => 'manual',
            'outofstock' => 'outofstock',
            'out_of_stock' => 'outofstock',
            'deleted' => 'deleted',
            'not_available' => 'deleted',
            'unavailable' => 'deleted'
        );

        $normalized_status = strtolower(trim($gic_status));
        return isset($status_mapping[$normalized_status]) ? $status_mapping[$normalized_status] : 'manual';
    }

    /**
     * Update WooCommerce stock status
     * 
     * @param WC_Product $product WooCommerce product object
     * @param string $target_status Target status ('instock', 'outofstock', 'onbackorder', 'no_change')
     * @return array Result array
     */
    private function update_woocommerce_stock_status($product, $target_status)
    {
        if ($target_status === 'no_change') {
            return array(
                'success' => true,
                'message' => 'No status change required',
                'old_status' => $product->get_stock_status(),
                'new_status' => $product->get_stock_status()
            );
        }

        $old_status = $product->get_stock_status();

        // Update stock status
        $product->set_stock_status($target_status);

        // Handle stock quantity based on status
        if ($target_status === 'outofstock') {
            $product->set_stock_quantity(0);
        } elseif ($target_status === 'instock' && $product->get_stock_quantity() === 0) {
            // Set a default stock quantity for instock products
            $product->set_stock_quantity(1);
        }

        // Save the product
        $product->save();

        return array(
            'success' => true,
            'message' => 'Stock status updated successfully',
            'old_status' => $old_status,
            'new_status' => $target_status
        );
    }

    /**
     * Log synchronization operation
     * 
     * @param int $product_id WooCommerce product ID
     * @param string $gic_status Gift-i-Card status
     * @param string $target_status Target WooCommerce status
     * @param bool $success Operation success status
     */
    private function log_sync_operation($product_id, $gic_status, $target_status, $success)
    {
        $log_data = array(
            'product_id' => $product_id,
            'gic_status' => $gic_status,
            'target_status' => $target_status,
            'success' => $success,
            'timestamp' => current_time('mysql')
        );

        // Store in product meta for debugging
        $product = wc_get_product($product_id);
        if ($product) {
            $product->update_meta_data('_gicapi_last_sync', $log_data);
            $product->save();
        }

        // Log to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[GICAPI Product Sync] Product %d: %s -> %s (%s)',
                $product_id,
                $gic_status,
                $target_status,
                $success ? 'SUCCESS' : 'FAILED'
            ));
        }
    }

    /**
     * Sync all products that have Gift-i-Card mappings
     * 
     * @return array Result array with sync statistics
     */
    public function sync_all_products()
    {
        $products_sync_enabled = get_option('gicapi_products_sync_enabled', 'no');
        if ($products_sync_enabled !== 'yes') {
            return array(
                'success' => false,
                'error' => 'Product synchronization is disabled'
            );
        }

        $mapped_products = $this->get_all_mapped_products();

        $results = array(
            'total_products' => count($mapped_products),
            'successful_syncs' => 0,
            'failed_syncs' => 0,
            'errors' => array()
        );

        foreach ($mapped_products as $product_id) {
            $result = $this->sync_product_status($product_id);

            if ($result['success']) {
                $results['successful_syncs']++;
            } else {
                $results['failed_syncs']++;
                $results['errors'][] = array(
                    'product_id' => $product_id,
                    'error' => $result['error']
                );
            }
        }

        return $results;
    }

    /**
     * Check if auto sync is enabled for product page loads
     * 
     * @return bool True if auto sync is enabled
     */
    public function is_auto_sync_enabled()
    {
        return get_option('gicapi_auto_sync_enabled', 'no') === 'yes';
    }

    /**
     * Get sync interval in seconds
     * 
     * @return int Sync interval in seconds
     */
    public function get_sync_interval()
    {
        return (int) get_option('gicapi_sync_interval', 300) * 60; // Convert minutes to seconds
    }

    /**
     * Simple function to sync a single product status
     * This is the main function that should be called to sync product status
     * 
     * @param int $product_id WooCommerce product ID
     * @param int $variation_id WooCommerce variation ID (optional)
     * @return bool True if sync was successful, false otherwise
     */
    public function sync_single_product($product_id, $variation_id = 0)
    {
        $result = $this->sync_product_status($product_id, $variation_id);
        return $result['success'];
    }

    /**
     * Get all WooCommerce products that have Gift-i-Card mappings
     * 
     * @return array Array of product IDs that have mappings
     */
    public function get_all_mapped_products()
    {
        global $wpdb;

        // Get products with new mapping system
        $new_mapped_products = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = %s AND meta_value != ''",
                '_gicapi_mapped_variant_skus'
            )
        );

        // Get products with old mapping system for backward compatibility
        $old_mapped_products = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = %s AND meta_value != ''",
                '_gic_variant_sku'
            )
        );

        // Merge and deduplicate
        $all_mapped_products = array_unique(array_merge($new_mapped_products, $old_mapped_products));

        // Filter out invalid product IDs
        $valid_products = array();
        foreach ($all_mapped_products as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $valid_products[] = (int) $product_id;
            }
        }

        return $valid_products;
    }
}
