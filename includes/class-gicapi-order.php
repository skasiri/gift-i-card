<?php

if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Order
{
    private static $instance = null;
    private $api;
    private $order_manager;

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
     * Update a single order's Gift-i-Card orders (legacy method)
     * @deprecated Use GICAPI_Order_Manager::update_single_order() instead
     */
    public function update_single_order($wc_order_id)
    {
        return $this->order_manager->update_single_order($wc_order_id, null, 'cron');
    }

    /**
     * Get all WooCommerce orders that have Gift-i-Card orders in pending or processing status (legacy method)
     * @deprecated Use GICAPI_Order_Manager::get_processing_orders() instead
     */
    public function get_processing_orders()
    {
        return $this->order_manager->get_processing_orders();
    }

    /**
     * Update multiple orders (legacy method)
     * @deprecated Use GICAPI_Order_Manager::update_processing_orders() instead
     */
    public function update_processing_orders()
    {
        return $this->order_manager->update_processing_orders();
    }
}
