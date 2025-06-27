<?php
if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Public
{
    private $plugin_name;
    private $version;
    private $api;
    private $gift_card_display;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $base_url = get_option('gicapi_base_url');
        $consumer_key = get_option('gicapi_consumer_key');
        $consumer_secret = get_option('gicapi_consumer_secret');
        $order_hook_priority = get_option('gicapi_hook_priority', 10);

        if ($base_url && $consumer_key && $consumer_secret) {
            $this->api = new GICAPI_API($base_url, $consumer_key, $consumer_secret);
        }

        // Initialize gift card display class
        require_once dirname(__FILE__, 2) . '/includes/class-gicapi-gift-card-display.php';
        $this->gift_card_display = new GICAPI_Gift_Card_Display();

        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), $order_hook_priority, 3);
        add_action('woocommerce_new_order', array($this, 'handle_order_creation'), $order_hook_priority, 1);

        add_action('woocommerce_email_order_details', array($this, 'add_redeem_data_to_email'), 10, 4);
        add_action('woocommerce_order_details_after_order_table', array($this, 'add_redeem_data_to_order_details'));
        add_action('woocommerce_thankyou', array($this, 'add_redeem_data_to_thank_you'));

        add_action('woocommerce_before_order_itemmeta', array($this->gift_card_display, 'display_gift_card_info_for_item'), 10, 3);
        add_action('woocommerce_order_details_after_order_table', array($this->gift_card_display, 'display_gift_card_summary'));
    }

    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/gicapi-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/gicapi-public.js', array('jquery'), $this->version, false);
    }

    /**
     * Normalize order status for comparison (handles both with and without 'wc-' prefix)
     * 
     * @param string $status The status to normalize
     * @return string The normalized status
     */
    private function normalize_status($status)
    {
        return str_replace('wc-', '', $status);
    }

    public function handle_order_status_change($order_id, $old_status, $new_status)
    {
        if (!$this->api) {
            return;
        }

        $enable_order_processing = get_option('gicapi_enable', 'no');
        if ($enable_order_processing !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $gift_i_card_create_order_status = get_option('gicapi_gift_i_card_create_order_status', 'wc-pending');
        if ($this->normalize_status($new_status) == $this->normalize_status($gift_i_card_create_order_status)) {
            $process_order = get_post_meta($order_id, '_gicapi_process_order', true);
            if ($process_order !== 'yes') {
                $this->process_order($order);
            }
        }

        $gift_i_card_confirm_order_status = get_option('gicapi_gift_i_card_confirm_order_status', 'wc-processing');
        if ($this->normalize_status($new_status) == $this->normalize_status($gift_i_card_confirm_order_status)) {
            $process_order = get_post_meta($order_id, '_gicapi_process_order', true);
            if ($process_order === 'yes') {
                $this->confirm_order($order_id);
            }
        }
    }

    public function handle_order_creation($order_id)
    {
        if (!$this->api) {
            return;
        }

        $enable_order_processing = get_option('gicapi_enable', 'no');
        if ($enable_order_processing !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $gift_i_card_create_order_status = get_option('gicapi_gift_i_card_create_order_status', 'wc-pending');
        $current_status = $order->get_status();

        if ($this->normalize_status($current_status) == $this->normalize_status($gift_i_card_create_order_status)) {
            $process_order = get_post_meta($order_id, '_gicapi_process_order', true);
            if ($process_order !== 'yes') {
                $this->process_order($order);
            }
        }

        $gift_i_card_confirm_order_status = get_option('gicapi_gift_i_card_confirm_order_status', 'wc-processing');
        if ($this->normalize_status($current_status) == $this->normalize_status($gift_i_card_confirm_order_status)) {
            $process_order = get_post_meta($order_id, '_gicapi_process_order', true);
            if ($process_order === 'yes') {
                $this->confirm_order($order_id);
            }
        }
    }

    /**
     * Get mapped variant SKU for a WooCommerce product
     * 
     * @param int $product_id The product ID
     * @param int $variation_id The variation ID (if applicable)
     * @return string|false The variant SKU or false if not mapped
     */
    private function get_mapped_variant_sku($product_id, $variation_id = 0)
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

    private function process_order($order)
    {
        // Process flag
        update_post_meta($order->get_id(), '_gicapi_process_order', 'yes');

        $orders = array();
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();

            $variant_sku = $this->get_mapped_variant_sku($product_id, $variation_id);

            if (!$variant_sku) {
                continue;
            }


            $response = $this->api->buy_product($variant_sku, $item->get_quantity());

            if (is_wp_error($response)) {
                $order->add_order_note(sprintf(
                    __('Failed to create Gift-i-Card order: %s', 'gift-i-card'),
                    $response->get_error_message()
                ));
                continue;
            }

            $order->add_order_note(sprintf(
                __('Gift-i-Card order created: %s', 'gift-i-card'),
                $response['order_id']
            ));

            $orders[] = array(
                'order_id' => $response['order_id'],
                'status' => $response['status'],
                'price' => $response['price'],
                'total' => $response['total'],
                'currency' => $response['currency'],
                'expires_at' => $response['expires_at'],
                'item_id' => $item->get_id(),
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'variant_sku' => $variant_sku,
                'quantity' => $item->get_quantity()
            );
        }

        update_post_meta($order->get_id(), '_gicapi_orders', $orders);
    }

    private function confirm_order($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $orders = get_post_meta($order_id, '_gicapi_orders', true);
        if (empty($orders)) {
            return;
        }

        foreach ($orders as $key => $order_data) {
            $response = $this->api->confirm_order($order_data['order_id']);

            if (is_wp_error($response)) {
                $order->add_order_note(sprintf(
                    __('Failed to confirm Gift-i-Card order: %s', 'gift-i-card'),
                    $response->get_error_message()
                ));
                $orders[$key]['status'] = 'failed';
                continue;
            }

            $order->add_order_note(sprintf(
                __('Gift-i-Card order confirmed: %s', 'gift-i-card'),
                $response['order_id']
            ));

            $orders[$key]['status'] = $response['status'];
        }

        update_post_meta($order_id, '_gicapi_orders', $orders);
    }

    public function add_redeem_data_to_email($order, $sent_to_admin, $plain_text, $email)
    {
        if (get_option('gicapi_add_to_email', 'yes') !== 'yes') {
            return;
        }

        $this->gift_card_display->display_redeem_data($order);
    }

    public function add_redeem_data_to_order_details($order)
    {
        if (get_option('gicapi_add_to_order_details', 'yes') !== 'yes') {
            return;
        }

        $this->gift_card_display->display_redeem_data($order);
    }

    public function add_redeem_data_to_thank_you($order_id)
    {
        if (get_option('gicapi_add_to_thank_you', 'yes') !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->gift_card_display->display_redeem_data($order);
    }
}
