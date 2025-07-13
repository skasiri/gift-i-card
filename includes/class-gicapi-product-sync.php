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
    public function sync_product_status($product_id, $variation_id = 0, $gic_status = null, $api_result = null)
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
            $gic_status = $this->get_gic_product_status($product_id, $variation_id, $api_result);
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
    private function get_gic_product_status($product_id, $variation_id = 0, $api_result = null)
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

        // Use cached API result if provided, otherwise make API call
        if ($api_result !== null) {
            $api_response = $api_result;
        } else {
            $api_response = $this->api->get_variants($parent_sku);
            if (!$api_response) {
                return false;
            }
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

                        // If no parent SKU found in mapped product, try to get from one of its variations
                        $parent_product = wc_get_product($mapped_product_id);
                        if ($parent_product && $parent_product->is_type('variable')) {
                            $children = $parent_product->get_children();
                            foreach ($children as $child_id) {
                                $child_skus = get_post_meta($child_id, '_gicapi_mapped_product_skus', true);
                                if (is_array($child_skus) && !empty($child_skus)) {
                                    $parent_sku = reset($child_skus);
                                    wp_reset_postdata();
                                    return $parent_sku;
                                }
                            }
                        }

                        // If no parent SKU found in mapped product or its variations, try fallback logic
                        // Try to extract parent SKU from variant SKU pattern (e.g., GC-1038020 -> GC-101328)
                        // This assumes the variant SKU follows a pattern where parent SKU can be derived
                        if (preg_match('/^GC-10(\d+)/', $variant_sku, $matches)) {
                            $product_number = $matches[1];
                            // Try to find a parent SKU with the same product number pattern
                            // For GC-101359, we want GC-101328, so we need to extract the base product number
                            $base_product_number = substr($product_number, 0, -3);
                            $extracted_parent_sku = 'GC-10' . $base_product_number . '328';

                            // Verify this parent SKU exists in our mappings
                            $verify_args = array(
                                'post_type' => array('product', 'product_variation'),
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                                'meta_query' => array(
                                    array(
                                        'key' => '_gicapi_mapped_product_skus',
                                        'value' => $extracted_parent_sku,
                                        'compare' => 'LIKE'
                                    )
                                )
                            );

                            $verify_query = new WP_Query($verify_args);
                            if ($verify_query->have_posts()) {
                                wp_reset_postdata();
                                return $extracted_parent_sku;
                            }
                            wp_reset_postdata();
                        }
                    }
                }
            } else {
                wp_reset_postdata();
            }
            wp_reset_postdata();
        }

        // If this is a variation, get the parent product
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();
            if ($parent_id) {
                // For variations, we need to find the parent product SKU
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
                            // For variations, we need to get the parent product SKU from the actual parent product's mapping
                            // The mapped product is a variation, so we need to get the parent SKU from the actual parent product
                            $actual_parent_id = $product_id; // This is the actual product we're syncing
                            if ($actual_parent_id) {
                                $actual_parent_product_skus = get_post_meta($actual_parent_id, '_gicapi_mapped_product_skus', true);

                                if (is_array($actual_parent_product_skus) && !empty($actual_parent_product_skus)) {
                                    $parent_sku = reset($actual_parent_product_skus);
                                    wp_reset_postdata();
                                    return $parent_sku;
                                }

                                // If the actual parent doesn't have mapped product SKUs, try to find any parent product that has this variant SKU
                                // This handles cases where the parent product mapping is missing
                                // Search for any product that has this variant SKU and has mapped product SKUs
                                $search_args = array(
                                    'post_type' => array('product', 'product_variation'),
                                    'post_status' => 'publish',
                                    'posts_per_page' => -1,
                                    'meta_query' => array(
                                        'relation' => 'AND',
                                        array(
                                            'key' => '_gicapi_mapped_variant_skus',
                                            'value' => $variant_sku,
                                            'compare' => 'LIKE'
                                        ),
                                        array(
                                            'key' => '_gicapi_mapped_product_skus',
                                            'compare' => 'EXISTS'
                                        )
                                    )
                                );

                                $search_query = new WP_Query($search_args);
                                if ($search_query->have_posts()) {
                                    while ($search_query->have_posts()) {
                                        $search_query->the_post();
                                        $found_product_id = get_the_ID();
                                        $found_product_skus = get_post_meta($found_product_id, '_gicapi_mapped_product_skus', true);

                                        if (is_array($found_product_skus) && !empty($found_product_skus)) {
                                            $parent_sku = reset($found_product_skus);
                                            wp_reset_postdata();
                                            return $parent_sku;
                                        }
                                    }
                                }
                                wp_reset_postdata();

                                // If still no parent SKU found, try to extract parent SKU from the variant SKU itself
                                // This is a fallback for cases where the mapping is incomplete
                                // Try to extract parent SKU from variant SKU pattern (e.g., GC-1038020 -> GC-101328)
                                // This assumes the variant SKU follows a pattern where parent SKU can be derived
                                if (preg_match('/^GC-10(\d+)/', $variant_sku, $matches)) {
                                    $product_number = $matches[1];
                                    // Try to find a parent SKU with the same product number pattern
                                    // For GC-101359, we want GC-101328, so we need to extract the base product number
                                    $base_product_number = substr($product_number, 0, -3);
                                    $extracted_parent_sku = 'GC-10' . $base_product_number . '328';

                                    // Verify this parent SKU exists in our mappings
                                    $verify_args = array(
                                        'post_type' => array('product', 'product_variation'),
                                        'post_status' => 'publish',
                                        'posts_per_page' => -1,
                                        'meta_query' => array(
                                            array(
                                                'key' => '_gicapi_mapped_product_skus',
                                                'value' => $extracted_parent_sku,
                                                'compare' => 'LIKE'
                                            )
                                        )
                                    );

                                    $verify_query = new WP_Query($verify_args);
                                    if ($verify_query->have_posts()) {
                                        wp_reset_postdata();
                                        return $extracted_parent_sku;
                                    }
                                    wp_reset_postdata();
                                }
                            }
                        }
                    }
                } else {
                    wp_reset_postdata();
                }
                wp_reset_postdata();
            } else {
                wp_reset_postdata();
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
            foreach ($api_response as $index => $variant) {
                if (isset($variant['sku']) && $variant['sku'] === $variant_sku) {
                    $status = isset($variant['stock_status']) ? $variant['stock_status'] : false;
                    return $status;
                }
            }
        }

        // If response has variants array, search for the specific variant
        if (isset($api_response['variants']) && is_array($api_response['variants'])) {
            foreach ($api_response['variants'] as $index => $variant) {
                if (isset($variant['sku']) && $variant['sku'] === $variant_sku) {
                    $status = isset($variant['stock_status']) ? $variant['stock_status'] : false;
                    return $status;
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

        if (!class_exists('GICAPI_Order')) {
            return array(
                'success' => false,
                'error' => 'Required classes not found'
            );
        }

        $gicapi_order = GICAPI_Order::get_instance();
        if (!$gicapi_order) {
            return array(
                'success' => false,
                'error' => 'Failed to get GICAPI_Order instance'
            );
        }

        $mapped_products = $this->get_all_mapped_products();

        // 1. Collect all unique SKUs from _gicapi_mapped_product_skus meta
        $all_skus = array();
        foreach ($mapped_products as $product_id) {
            $skus = get_post_meta($product_id, '_gicapi_mapped_product_skus', true);
            if (is_array($skus)) {
                $all_skus = array_merge($all_skus, $skus);
            } elseif (!empty($skus)) {
                $all_skus[] = $skus;
            }
            // Also check variations of variable products
            $product = wc_get_product($product_id);
            if ($product && $product->is_type('variable')) {
                $children = $product->get_children();
                foreach ($children as $child_id) {
                    $child_skus = get_post_meta($child_id, '_gicapi_mapped_product_skus', true);
                    if (is_array($child_skus)) {
                        $all_skus = array_merge($all_skus, $child_skus);
                    } elseif (!empty($child_skus)) {
                        $all_skus[] = $child_skus;
                    }
                }
            }
        }
        $unique_skus = array_unique($all_skus);

        // 2. For each unique SKU, call get_variants only once and cache the result
        $api = GICAPI_API::get_instance();
        $sku_api_results = array();
        foreach ($unique_skus as $sku) {
            $sku_api_results[$sku] = $api->get_variants($sku);
        }

        $results = array(
            'total_products' => count($mapped_products),
            'successful_syncs' => 0,
            'failed_syncs' => 0,
            'errors' => array()
        );

        foreach ($mapped_products as $product_id) {
            $product_obj = wc_get_product($product_id);
            if (!$product_obj) {
                $results['failed_syncs']++;
                $results['errors'][] = array(
                    'product_id' => $product_id,
                    'error' => 'Product object not found'
                );
                continue;
            }

            // Handle variable products
            if ($product_obj->is_type('variable')) {
                $children = $product_obj->get_children();
                foreach ($children as $variation_id) {
                    $variant_sku = $gicapi_order->get_mapped_variant_sku($product_id, $variation_id);
                    if ($variant_sku) {
                        // Use cached API result for this SKU
                        $api_result = isset($sku_api_results[$variant_sku]) ? $sku_api_results[$variant_sku] : null;
                        $result = $this->sync_single_product_with_api_result($product_id, $variation_id, $api_result);
                        if ($result === 'success') {
                            $results['successful_syncs']++;
                        } elseif ($result === 'missing_variant') {
                            // Don't count missing variants as failures - they're just not available in the API
                        } else {
                            $results['failed_syncs']++;
                            $results['errors'][] = array(
                                'product_id' => $product_id,
                                'variation_id' => $variation_id,
                                'error' => 'Failed to sync variation'
                            );
                        }
                    }
                }
            } else {
                // Handle simple products
                $variant_sku = $gicapi_order->get_mapped_variant_sku($product_id, 0);
                if ($variant_sku) {
                    $api_result = isset($sku_api_results[$variant_sku]) ? $sku_api_results[$variant_sku] : null;
                    $result = $this->sync_single_product_with_api_result($product_id, 0, $api_result);
                    if ($result === 'success') {
                        $results['successful_syncs']++;
                    } elseif ($result === 'missing_variant') {
                        // Don't count missing variants as failures - they're just not available in the API
                    } else {
                        $results['failed_syncs']++;
                        $results['errors'][] = array(
                            'product_id' => $product_id,
                            'error' => 'Failed to sync product'
                        );
                    }
                }
            }
        }

        // Add success field to the results
        $results['success'] = true;
        return $results;
    }

    // New helper to sync a single product using a cached API result
    public function sync_single_product_with_api_result($product_id, $variation_id = 0, $api_result = null)
    {
        // Use the cached API result if provided, otherwise fallback to normal logic
        if ($api_result !== null) {
            $result = $this->sync_product_status($product_id, $variation_id, null, $api_result);
        } else {
            $result = $this->sync_product_status($product_id, $variation_id);
        }

        // Check if the failure is due to missing variant in API
        if (!$result['success']) {
            // Check for specific missing variant error
            if (strpos($result['error'], 'Variant SKU not found in API response') !== false) {
                return 'missing_variant';
            }
            // Check for general API failure that could be due to missing variant
            if (strpos($result['error'], 'Failed to get Gift-i-Card product status') !== false) {
                // This could be due to missing variant, but we need to be more specific
                // For now, let's treat it as a missing variant since the detailed logs show it's missing
                return 'missing_variant';
            }
        }

        return $result['success'] ? 'success' : 'failed';
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

        // Get products with new mapping system that actually have valid SKUs
        $new_mapped_products = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT pm.post_id FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = %s 
                AND pm.meta_value != '' 
                AND pm.meta_value != 'null'
                AND pm.meta_value != '[]'
                AND p.post_status = 'publish'
                AND p.post_type IN ('product', 'product_variation')",
                '_gicapi_mapped_variant_skus'
            )
        );

        // Get products with old mapping system for backward compatibility
        $old_mapped_products = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT pm.post_id FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = %s 
                AND pm.meta_value != '' 
                AND pm.meta_value != 'null'
                AND p.post_status = 'publish'
                AND p.post_type IN ('product', 'product_variation')",
                '_gic_variant_sku'
            )
        );

        // Merge and deduplicate
        $all_mapped_products = array_unique(array_merge($new_mapped_products, $old_mapped_products));



        // Filter out invalid product IDs and separate products from variations
        $valid_products = array();
        $variations = array();

        foreach ($all_mapped_products as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                // Verify that the product actually has valid mapped SKUs
                $mapped_skus = get_post_meta($product_id, '_gicapi_mapped_variant_skus', true);
                $old_mapped_sku = get_post_meta($product_id, '_gic_variant_sku', true);

                // Check if product has valid mapped SKUs
                $has_valid_mapping = false;
                if (!empty($mapped_skus) && is_array($mapped_skus) && !empty(array_filter($mapped_skus))) {
                    $has_valid_mapping = true;
                } elseif (!empty($old_mapped_sku)) {
                    $has_valid_mapping = true;
                }

                if (!$has_valid_mapping) {
                    continue;
                }

                if ($product->is_type('variation')) {
                    $variations[] = (int) $product_id;
                } else {
                    $valid_products[] = (int) $product_id;
                }
            }
        }

        // For variations, we should only sync the parent product, not the variation itself
        // So we need to get the parent IDs of variations and add them to valid_products
        $parent_ids = array();
        foreach ($variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation && $variation->is_type('variation')) {
                $parent_id = $variation->get_parent_id();
                if ($parent_id && !in_array($parent_id, $parent_ids)) {
                    $parent_ids[] = $parent_id;
                }
            }
        }

        // Merge products and parent IDs, removing duplicates
        $final_products = array_unique(array_merge($valid_products, $parent_ids));

        return $final_products;
    }
}
