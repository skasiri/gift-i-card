<?php
if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Public
{
    private $plugin_name;
    private $version;
    public $api;
    private $gift_card_display;
    private $order;

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

        $this->order = GICAPI_Order::get_instance();

        // Make this instance available globally for AJAX access
        global $gicapi_public;
        $gicapi_public = $this;

        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), $order_hook_priority, 3);
        add_action('woocommerce_new_order', array($this, 'handle_order_creation'), $order_hook_priority, 1);

        add_action('woocommerce_email_order_details', array($this, 'add_redeem_data_to_email'), 10, 4);
        add_action('woocommerce_order_details_after_order_table', array($this, 'add_redeem_data_to_order_details'));
        add_action('woocommerce_thankyou', array($this, 'add_redeem_data_to_thank_you'));

        add_action('woocommerce_before_order_itemmeta', array($this->gift_card_display, 'display_gift_card_info_for_item'), 10, 3);
    }

    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/gicapi-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/gicapi-public.js', array('jquery'), $this->version, false);

        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'gicapi_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gicapi_create_order_manually')
        ));
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

    public function process_order($order)
    {
        // Process flag
        update_post_meta($order->get_id(), '_gicapi_process_order', 'yes');

        $orders = array();
        $failed_items = array();
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();

            $variant_sku = $this->order->get_mapped_variant_sku($product_id, $variation_id);

            if (!$variant_sku) {
                continue;
            }


            $response = $this->api->buy_product($variant_sku, $item->get_quantity());

            if (!$response) {
                $order->add_order_note(
                    __('Failed to create Gift-i-Card order', 'gift-i-card'),
                );
                $failed_items[] = $item->get_id();
                continue;
            }

            // Validate that the response contains a valid order_id
            if (empty($response['order_id'])) {
                $order->add_order_note(
                    __('Gift-i-Card API returned empty order_id', 'gift-i-card'),
                );
                $failed_items[] = $item->get_id();
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
        update_post_meta($order->get_id(), '_gicapi_created_failed_items', $failed_items);
    }

    public function confirm_order($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $orders = get_post_meta($order_id, '_gicapi_orders', true);
        if (empty($orders)) {
            return;
        }

        $failed_items = array();
        foreach ($orders as $key => $order_data) {
            $response = $this->api->confirm_order($order_data['order_id']);

            if (!$response) {
                $order->add_order_note(
                    __('Failed to confirm Gift-i-Card order', 'gift-i-card'),
                );
                $orders[$key]['status'] = 'failed';
                $failed_items[] = $order_data['item_id'];
                continue;
            }

            $order->add_order_note(sprintf(
                __('Gift-i-Card order confirmed: %s', 'gift-i-card'),
                $response['order_id']
            ));

            $orders[$key]['status'] = $response['status'];
        }

        update_post_meta($order_id, '_gicapi_orders', $orders);
        update_post_meta($order_id, '_gicapi_confirmed_failed_items', $failed_items);
    }

    public function add_redeem_data_to_email($order, $sent_to_admin, $plain_text, $email)
    {
        if (get_option('gicapi_add_to_email', 'no') !== 'yes') {
            return;
        }

        $this->gift_card_display->display_redeem_data_email($order);
    }

    public function add_redeem_data_to_order_details($order)
    {
        if (get_option('gicapi_add_to_order_details', 'no') !== 'yes') {
            return;
        }

        $this->gift_card_display->display_gift_card_summary($order);
    }

    public function add_redeem_data_to_thank_you($order_id)
    {
        if (get_option('gicapi_add_to_thank_you', 'no') !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->gift_card_display->display_redeem_data($order);
    }
}
