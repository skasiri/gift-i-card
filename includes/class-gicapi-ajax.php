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
        add_action('wp_ajax_gicapi_create_simple_product', array($this, 'create_simple_product'));
        add_action('wp_ajax_gicapi_check_sku_uniqueness', array($this, 'check_sku_uniqueness'));
        add_action('wp_ajax_gicapi_create_order_manually', array($this, 'create_order_manually'));
        add_action('wp_ajax_gicapi_confirm_order_manually', array($this, 'confirm_order_manually'));
        add_action('wp_ajax_gicapi_update_status_manually', array($this, 'update_status_manually'));
        add_action('wp_ajax_gicapi_manual_sync_products', array($this, 'manual_sync_products'));
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
                                /* translators: %s: parent product name */
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

        $variant_sku = isset($_POST['variant_sku']) ? sanitize_text_field(wp_unslash($_POST['variant_sku'])) : '';
        $product_id = isset($_POST['product_id']) ? intval(wp_unslash($_POST['product_id'])) : 0;
        $category_sku = isset($_POST['category_sku']) ? sanitize_text_field(wp_unslash($_POST['category_sku'])) : '';
        $product_sku = isset($_POST['product_sku']) ? sanitize_text_field(wp_unslash($_POST['product_sku'])) : '';

        if (empty($variant_sku) || empty($product_id) || empty($category_sku) || empty($product_sku)) {
            wp_send_json_error(__('Invalid parameters', 'gift-i-card'));
        }

        // Get current mappings for the product
        $mapped_category_skus = get_post_meta($product_id, '_gicapi_mapped_category_skus', true);
        $mapped_product_skus = get_post_meta($product_id, '_gicapi_mapped_product_skus', true);
        $mapped_variant_skus = get_post_meta($product_id, '_gicapi_mapped_variant_skus', true);

        // Ensure arrays
        $mapped_category_skus = is_array($mapped_category_skus) ? $mapped_category_skus : array();
        $mapped_product_skus = is_array($mapped_product_skus) ? $mapped_product_skus : array();
        $mapped_variant_skus = is_array($mapped_variant_skus) ? $mapped_variant_skus : array();

        // Check if this specific mapping already exists
        $mapping_exists = false;
        for ($i = 0; $i < count($mapped_variant_skus); $i++) {
            if (isset($mapped_category_skus[$i]) && isset($mapped_product_skus[$i]) && isset($mapped_variant_skus[$i])) {
                if (
                    $mapped_category_skus[$i] === $category_sku &&
                    $mapped_product_skus[$i] === $product_sku &&
                    $mapped_variant_skus[$i] === $variant_sku
                ) {
                    $mapping_exists = true;
                    break;
                }
            }
        }

        if ($mapping_exists) {
            wp_send_json_error(__('This mapping already exists', 'gift-i-card'));
        }

        // Add new mapping
        $mapped_category_skus[] = $category_sku;
        $mapped_product_skus[] = $product_sku;
        $mapped_variant_skus[] = $variant_sku;

        // Update all three meta fields
        $update_category = update_post_meta($product_id, '_gicapi_mapped_category_skus', $mapped_category_skus);
        $update_product = update_post_meta($product_id, '_gicapi_mapped_product_skus', $mapped_product_skus);
        $update_variant = update_post_meta($product_id, '_gicapi_mapped_variant_skus', $mapped_variant_skus);

        if (!$update_category || !$update_product || !$update_variant) {
            wp_send_json_error(__('Error saving mapping', 'gift-i-card'));
        }

        wp_send_json_success(__('Mapping added successfully', 'gift-i-card'));
    }

    public function remove_mapping()
    {
        check_ajax_referer('gicapi_remove_mapping', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $variant_sku = isset($_POST['variant_sku']) ? sanitize_text_field(wp_unslash($_POST['variant_sku'])) : '';
        $product_id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
        $category_sku = isset($_POST['category_sku']) ? sanitize_text_field(wp_unslash($_POST['category_sku'])) : '';
        $product_sku = isset($_POST['product_sku']) ? sanitize_text_field(wp_unslash($_POST['product_sku'])) : '';

        if (empty($variant_sku) || empty($product_id) || empty($category_sku) || empty($product_sku)) {
            wp_send_json_error(__('Invalid parameters', 'gift-i-card'));
        }

        // Get current mappings for the product
        $mapped_category_skus = get_post_meta($product_id, '_gicapi_mapped_category_skus', true);
        $mapped_product_skus = get_post_meta($product_id, '_gicapi_mapped_product_skus', true);
        $mapped_variant_skus = get_post_meta($product_id, '_gicapi_mapped_variant_skus', true);

        // Ensure arrays
        $mapped_category_skus = is_array($mapped_category_skus) ? $mapped_category_skus : array();
        $mapped_product_skus = is_array($mapped_product_skus) ? $mapped_product_skus : array();
        $mapped_variant_skus = is_array($mapped_variant_skus) ? $mapped_variant_skus : array();

        // Find and remove the specific mapping
        $mapping_found = false;
        for ($i = 0; $i < count($mapped_variant_skus); $i++) {
            if (isset($mapped_category_skus[$i]) && isset($mapped_product_skus[$i]) && isset($mapped_variant_skus[$i])) {
                if (
                    $mapped_category_skus[$i] === $category_sku &&
                    $mapped_product_skus[$i] === $product_sku &&
                    $mapped_variant_skus[$i] === $variant_sku
                ) {
                    // Remove this mapping
                    unset($mapped_category_skus[$i]);
                    unset($mapped_product_skus[$i]);
                    unset($mapped_variant_skus[$i]);
                    $mapping_found = true;
                    break;
                }
            }
        }

        if (!$mapping_found) {
            wp_send_json_error(__('Mapping not found', 'gift-i-card'));
        }

        // Reindex arrays
        $mapped_category_skus = array_values($mapped_category_skus);
        $mapped_product_skus = array_values($mapped_product_skus);
        $mapped_variant_skus = array_values($mapped_variant_skus);

        // Update all three meta fields
        $update_category = update_post_meta($product_id, '_gicapi_mapped_category_skus', $mapped_category_skus);
        $update_product = update_post_meta($product_id, '_gicapi_mapped_product_skus', $mapped_product_skus);
        $update_variant = update_post_meta($product_id, '_gicapi_mapped_variant_skus', $mapped_variant_skus);

        if (!$update_category || !$update_product || !$update_variant) {
            wp_send_json_error(__('Error removing mapping', 'gift-i-card'));
        }

        wp_send_json_success(__('Mapping removed successfully', 'gift-i-card'));
    }

    public function create_simple_product()
    {
        check_ajax_referer('gicapi_create_simple_product', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $variant_sku = isset($_POST['variant_sku']) ? sanitize_text_field(wp_unslash($_POST['variant_sku'])) : '';
        $variant_name = isset($_POST['variant_name']) ? sanitize_text_field(wp_unslash($_POST['variant_name'])) : '';
        $category_sku = isset($_POST['category_sku']) ? sanitize_text_field(wp_unslash($_POST['category_sku'])) : '';
        $product_sku = isset($_POST['product_sku']) ? sanitize_text_field(wp_unslash($_POST['product_sku'])) : '';
        $variant_value = isset($_POST['variant_value']) ? sanitize_text_field(wp_unslash($_POST['variant_value'])) : '';

        // New form fields
        $product_name = isset($_POST['product_name']) ? sanitize_text_field(wp_unslash($_POST['product_name'])) : '';
        $product_sku_field = isset($_POST['product_sku_field']) ? sanitize_text_field(wp_unslash($_POST['product_sku_field'])) : '';
        $price = isset($_POST['price']) ? floatval(wp_unslash($_POST['price'])) : 0;
        $product_status = isset($_POST['product_status']) ? sanitize_text_field(wp_unslash($_POST['product_status'])) : 'draft';

        if (empty($variant_sku) || empty($product_name) || empty($category_sku) || empty($product_sku)) {
            wp_send_json_error(__('Invalid parameters', 'gift-i-card'));
        }

        if ($price < 0) {
            wp_send_json_error(__('Price cannot be negative', 'gift-i-card'));
        }

        if (!in_array($product_status, array('publish', 'draft'))) {
            wp_send_json_error(__('Invalid product status', 'gift-i-card'));
        }

        // Check if product with this SKU already exists (only if SKU is provided)
        if (!empty($product_sku_field)) {
            $existing_product = get_posts(array(
                'post_type' => 'product',
                'post_status' => 'any',
                'meta_key' => '_sku',
                'meta_value' => $product_sku_field,
                'posts_per_page' => 1
            ));

            if (!empty($existing_product)) {
                wp_send_json_error(__('A product with this SKU already exists', 'gift-i-card'));
            }
        }

        // Create new WooCommerce product
        $product = new WC_Product_Simple();

        // Set product data
        $product->set_name($product_name);
        if (!empty($product_sku_field)) {
            $product->set_sku($product_sku_field);
        }
        $product->set_regular_price($price);
        $product->set_price($price);
        $product->set_virtual(true); // Make product virtual
        $product->set_catalog_visibility('visible');
        $product->set_status($product_status);

        // Save the product
        $product_id = $product->save();

        if (!$product_id) {
            wp_send_json_error(__('Failed to create product', 'gift-i-card'));
        }

        // Add mapping metadata to the new product
        $mapped_category_skus = array($category_sku);
        $mapped_product_skus = array($product_sku);
        $mapped_variant_skus = array($variant_sku);

        update_post_meta($product_id, '_gicapi_mapped_category_skus', $mapped_category_skus);
        update_post_meta($product_id, '_gicapi_mapped_product_skus', $mapped_product_skus);
        update_post_meta($product_id, '_gicapi_mapped_variant_skus', $mapped_variant_skus);

        // Add additional metadata for gift card products
        update_post_meta($product_id, '_gicapi_variant_value', $variant_value);
        update_post_meta($product_id, '_gicapi_variant_sku', $variant_sku);
        update_post_meta($product_id, '_gicapi_category_sku', $category_sku);
        update_post_meta($product_id, '_gicapi_product_sku', $product_sku);

        wp_send_json_success(__('Simple product created and mapped successfully', 'gift-i-card'));
    }

    public function check_sku_uniqueness()
    {
        check_ajax_referer('gicapi_check_sku_uniqueness', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $sku = isset($_POST['sku']) ? sanitize_text_field(wp_unslash($_POST['sku'])) : '';

        if (empty($sku)) {
            wp_send_json_error(__('SKU is required', 'gift-i-card'));
        }

        // Check if product with this SKU already exists
        $existing_product = get_posts(array(
            'post_type' => 'product',
            'post_status' => 'any',
            'meta_key' => '_sku',
            'meta_value' => $sku,
            'posts_per_page' => 1
        ));

        if (!empty($existing_product)) {
            wp_send_json_error(__('A product with this SKU already exists', 'gift-i-card'));
        }

        wp_send_json_success(__('SKU is available', 'gift-i-card'));
    }

    public function create_order_manually()
    {
        check_ajax_referer('gicapi_create_order_manually', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $order_id = isset($_POST['order_id']) ? absint(wp_unslash($_POST['order_id'])) : 0;
        $item_id = isset($_POST['item_id']) ? absint(wp_unslash($_POST['item_id'])) : 0;

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
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to create order: ', 'gift-i-card') . $e->getMessage());
        }

        try {
            $gicapi_public->confirm_order($order_id);
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to confirm order: ', 'gift-i-card') . $e->getMessage());
        }

        wp_send_json_success(__('Order created successfully', 'gift-i-card'));
    }

    public function confirm_order_manually()
    {
        check_ajax_referer('gicapi_confirm_order_manually', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        $order_id = isset($_POST['order_id']) ? absint(wp_unslash($_POST['order_id'])) : 0;

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

        $order_id = isset($_POST['order_id']) ? absint(wp_unslash($_POST['order_id'])) : 0;

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

    public function manual_sync_products()
    {
        check_ajax_referer('gicapi_manual_sync_products', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Permission denied', 'gift-i-card'));
        }

        // Get the product sync class instance
        $product_sync = GICAPI_Product_Sync::get_instance();
        if (!$product_sync) {
            wp_send_json_error(__('GICAPI Product Sync class not available', 'gift-i-card'));
        }

        try {
            // Call the sync_all_products method
            $result = $product_sync->sync_all_products();

            if (isset($result['success']) && $result['success']) {
                $message = sprintf(
                    /* translators: 1: Total number of products, 2: Number of successful syncs, 3: Number of failed syncs */
                    __('Product sync completed successfully. Total: %1$d, Successful: %2$d, Failed: %3$d', 'gift-i-card'),
                    $result['total_products'],
                    $result['successful_syncs'],
                    $result['failed_syncs']
                );
                wp_send_json_success($message);
            } else {
                /* translators: Error message when product sync fails with unknown error */
                $error_message = isset($result['error']) ? $result['error'] : __('Unknown error occurred during sync', 'gift-i-card');
                wp_send_json_error($error_message);
            }
        } catch (Exception $e) {
            wp_send_json_error(__('Failed to sync products: ', 'gift-i-card') . $e->getMessage());
        }
    }
}
