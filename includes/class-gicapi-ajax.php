<?php

class GICAPI_Ajax
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_ajax_gicapi_search_products', array($this, 'search_products'));
        add_action('wp_ajax_gicapi_add_mapping', array($this, 'add_mapping'));
        add_action('wp_ajax_gicapi_remove_mapping', array($this, 'remove_mapping'));
    }

    public function search_products()
    {
        check_ajax_referer('gicapi_search_products', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

        if (empty($search)) {
            wp_send_json(array());
        }

        $args = array(
            'post_type' => array('product', 'product_variation'),
            'post_status' => 'publish',
            'posts_per_page' => 10,
            's' => $search,
            'orderby' => 'title',
            'order' => 'ASC'
        );

        $query = new WP_Query($args);
        $products = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products[] = array(
                        'id' => $product->get_id(),
                        'text' => $product->get_name() . ' (' . $product->get_sku() . ')'
                    );
                }
            }
        }
        wp_reset_postdata();

        wp_send_json($products);
    }

    public function add_mapping()
    {
        check_ajax_referer('gicapi_add_mapping', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $variant_id = isset($_POST['variant_id']) ? sanitize_text_field($_POST['variant_id']) : '';
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (empty($variant_id) || empty($product_id)) {
            wp_send_json_error(__('Invalid parameters', 'gift-i-card'));
        }

        // Get current mappings for variant
        $mapped_product_ids = get_post_meta($variant_id, '_gicapi_mapped_wc_product_ids', true);
        $mapped_product_ids = is_array($mapped_product_ids) ? $mapped_product_ids : array();

        // Check if product is already mapped
        if (in_array($product_id, $mapped_product_ids)) {
            wp_send_json_error(__('This product is already mapped to this variant', 'gift-i-card'));
        }

        // Get current mappings for product
        $mapped_variant_skus = get_post_meta($product_id, '_gicapi_mapped_variant_skus', true);
        $mapped_variant_skus = is_array($mapped_variant_skus) ? $mapped_variant_skus : array();

        // Add variant SKU to product mappings
        if (!in_array($variant_id, $mapped_variant_skus)) {
            $mapped_variant_skus[] = $variant_id;
            $update_product = update_post_meta($product_id, '_gicapi_mapped_variant_skus', $mapped_variant_skus);

            if (!$update_product) {
                // If product update fails, rollback variant update
                delete_post_meta($variant_id, '_gicapi_mapped_wc_product_ids');
                wp_send_json_error(__('Error saving product mapping', 'gift-i-card'));
            }
        }

        // Log the mapping for debugging
        error_log(sprintf(
            'GICAPI Mapping added - Variant: %s, Product: %d, Variant Meta: %s, Product Meta: %s',
            $variant_id,
            $product_id,
            print_r($mapped_product_ids, true),
            print_r($mapped_variant_skus, true)
        ));

        wp_send_json_success(__('Mapping added successfully', 'gift-i-card'));
    }

    public function remove_mapping()
    {
        check_ajax_referer('gicapi_remove_mapping', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $variant_id = isset($_POST['variant_id']) ? sanitize_text_field($_POST['variant_id']) : '';
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (empty($variant_id) || empty($product_id)) {
            wp_send_json_error(__('Invalid parameters', 'gift-i-card'));
        }

        // Remove from product mappings
        $mapped_variant_skus = get_post_meta($product_id, '_gicapi_mapped_variant_skus', true);
        $mapped_variant_skus = is_array($mapped_variant_skus) ? $mapped_variant_skus : array();

        $key = array_search($variant_id, $mapped_variant_skus);
        if ($key !== false) {
            unset($mapped_variant_skus[$key]);
            $update_product = update_post_meta($product_id, '_gicapi_mapped_variant_skus', array_values($mapped_variant_skus));

            if (!$update_product) {
                wp_send_json_error(__('Error removing product mapping', 'gift-i-card'));
            }
        }

        // Log the removal for debugging
        error_log(sprintf(
            'GICAPI Mapping removed - Variant: %s, Product: %d, Remaining Variant Meta: %s, Remaining Product Meta: %s',
            $variant_id,
            $product_id,
            print_r($mapped_variant_skus, true)
        ));

        wp_send_json_success(__('Mapping removed successfully', 'gift-i-card'));
    }
}

// Initialize the AJAX handler
GICAPI_Ajax::get_instance();
