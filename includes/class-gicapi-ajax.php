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
        add_action('wp_ajax_gicapi_create_order_manually', array($this, 'create_order_manually'));
        add_action('wp_ajax_gicapi_confirm_order_manually', array($this, 'confirm_order_manually'));
        add_action('wp_ajax_gicapi_update_status_manually', array($this, 'update_status_manually'));
    }

    public function search_products()
    {
        check_ajax_referer('gicapi_search_products', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $search = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';

        if (empty($search)) {
            wp_send_json(array());
        }

        $args = array(
            'post_type' => array('product', 'product_variation'),
            'post_status' => 'publish',
            'posts_per_page' => 15,
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
                    $product_type = $product->get_type();
                    $product_name = $product->get_name();
                    $product_sku = $product->get_sku();

                    // Add product type information for better identification
                    $type_label = '';
                    switch ($product_type) {
                        case 'simple':
                            $type_label = __('Simple Product', 'gift-i-card');
                            break;
                        case 'variable':
                            $type_label = __('Variable Product', 'gift-i-card');
                            break;
                        case 'variation':
                            $parent_product = wc_get_product($product->get_parent_id());
                            if ($parent_product) {
                                // translators: %s: parent product name
                                $type_label = sprintf(__('Variation of: %s', 'gift-i-card'), $parent_product->get_name());
                            } else {
                                $type_label = __('Product Variation', 'gift-i-card');
                            }
                            break;
                        case 'grouped':
                            $type_label = __('Grouped Product', 'gift-i-card');
                            break;
                        case 'external':
                            $type_label = __('External Product', 'gift-i-card');
                            break;
                        default:
                            $type_label = __('Product', 'gift-i-card');
                    }

                    // Create display text with product type information
                    $display_text = $product_name;
                    if ($product_sku) {
                        $display_text .= ' (SKU: ' . $product_sku . ')';
                    }
                    $display_text .= ' - ' . $type_label;

                    $products[] = array(
                        'id' => $product->get_id(),
                        'text' => $display_text,
                        'type' => $product_type,
                        'sku' => $product_sku,
                        'name' => $product_name
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

        $variant_id = isset($_POST['variant_id']) ? sanitize_text_field(wp_unslash($_POST['variant_id'])) : '';
        $product_id = isset($_POST['product_id']) ? intval(wp_unslash($_POST['product_id'])) : 0;

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



        wp_send_json_success(__('Mapping added successfully', 'gift-i-card'));
    }

    public function remove_mapping()
    {
        check_ajax_referer('gicapi_remove_mapping', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $variant_id = isset($_POST['variant_id']) ? sanitize_text_field(wp_unslash($_POST['variant_id'])) : '';
        $product_id = isset($_POST['product_id']) ? intval(wp_unslash($_POST['product_id'])) : 0;

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



        wp_send_json_success(__('Mapping removed successfully', 'gift-i-card'));
    }

    public function create_order_manually()
    {
        check_ajax_referer('gicapi_create_order_manually', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if (empty($order_id) || empty($item_id)) {
            wp_send_json_error(__('Invalid parameters', 'gift-i-card'));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(__('Order not found', 'gift-i-card'));
        }

        $item = $order->get_item($item_id);
        if (!$item) {
            wp_send_json_error(__('Order item not found', 'gift-i-card'));
        }

        // Get the public class instance to access process_order method
        global $gicapi_public;
        if (!$gicapi_public) {
            wp_send_json_error(__('GICAPI Public class not available', 'gift-i-card'));
        }

        // Check if API is available
        if (!$gicapi_public->api) {
            wp_send_json_error(__('GICAPI API not available', 'gift-i-card'));
        }

        // Check if order processing is enabled
        $enable_order_processing = get_option('gicapi_enable', 'no');
        if ($enable_order_processing !== 'yes') {
            wp_send_json_error(__('Order processing is disabled', 'gift-i-card'));
        }

        // Check if order has already been processed
        $process_order = get_post_meta($order_id, '_gicapi_process_order', true);
        if ($process_order === 'yes') {
            delete_post_meta($order_id, '_gicapi_process_order');
        }

        try {
            // Call the process_order method directly (same as handle_order_creation)
            $gicapi_public->process_order($order);

            wp_send_json_success(__('Order created successfully', 'gift-i-card'));
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to create order: ', 'gift-i-card') . $e->getMessage());
        }
    }

    public function confirm_order_manually()
    {
        check_ajax_referer('gicapi_confirm_order_manually', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if (empty($order_id)) {
            wp_send_json_error(__('Invalid parameters', 'gift-i-card'));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(__('Order not found', 'gift-i-card'));
        }

        // Get the public class instance to access confirm_order method
        global $gicapi_public;
        if (!$gicapi_public) {
            wp_send_json_error(__('GICAPI Public class not available', 'gift-i-card'));
        }

        // Check if API is available
        if (!$gicapi_public->api) {
            wp_send_json_error(__('GICAPI API not available', 'gift-i-card'));
        }

        // Check if order processing is enabled
        $enable_order_processing = get_option('gicapi_enable', 'no');
        if ($enable_order_processing !== 'yes') {
            wp_send_json_error(__('Order processing is disabled', 'gift-i-card'));
        }

        // Check if order has been processed
        $process_order = get_post_meta($order_id, '_gicapi_process_order', true);
        if ($process_order !== 'yes') {
            wp_send_json_error(__('Order has not been processed yet', 'gift-i-card'));
        }

        try {
            // Call the confirm_order method directly
            $gicapi_public->confirm_order($order_id);

            wp_send_json_success(__('Order confirmed successfully', 'gift-i-card'));
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to confirm order: ', 'gift-i-card') . $e->getMessage());
        }
    }

    public function update_status_manually()
    {
        check_ajax_referer('gicapi_update_status_manually', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if (empty($order_id)) {
            wp_send_json_error(__('Invalid parameters', 'gift-i-card'));
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(__('Order not found', 'gift-i-card'));
        }

        // Get the order class instance to access update_single_order method
        $order_handler = GICAPI_Order::get_instance();
        if (!$order_handler) {
            wp_send_json_error(__('GICAPI Order class not available', 'gift-i-card'));
        }

        // Check if order processing is enabled
        $enable_order_processing = get_option('gicapi_enable', 'no');
        if ($enable_order_processing !== 'yes') {
            wp_send_json_error(__('Order processing is disabled', 'gift-i-card'));
        }

        // Check if order has been processed
        $process_order = get_post_meta($order_id, '_gicapi_process_order', true);
        if ($process_order !== 'yes') {
            wp_send_json_error(__('Order has not been processed yet', 'gift-i-card'));
        }

        try {
            // Call the update_single_order method
            $result = $order_handler->update_single_order($order_id);

            if ($result) {
                wp_send_json_success(__('Order status updated successfully', 'gift-i-card'));
            } else {
                wp_send_json_error(__('No updates were made to the order status', 'gift-i-card'));
            }
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to update order status: ', 'gift-i-card') . $e->getMessage());
        }
    }
}

// Initialize the AJAX handler
GICAPI_Ajax::get_instance();

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
