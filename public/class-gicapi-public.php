<?php
if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Public
{
    private $plugin_name;
    private $version;
    private $api;

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

        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), $order_hook_priority, 3);
        add_action('woocommerce_email_order_details', array($this, 'add_redeem_data_to_email'), 10, 4);
        add_action('woocommerce_order_details_after_order_table', array($this, 'add_redeem_data_to_order_details'));
        add_action('woocommerce_thankyou', array($this, 'add_redeem_data_to_thank_you'));
    }

    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/gicapi-public.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/gicapi-public.js', array('jquery'), $this->version, false);
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

        $gift_i_card_create_order_status = get_option('gicapi_gift_i_card_create_order_status', 'wc-pending');
        if ($new_status == $gift_i_card_create_order_status) {
            $process_order = get_post_meta($order_id, '_gicapi_process_order', true);
            if ($process_order !== 'yes') {
                $this->process_order($order_id);
            }
        }

        $gift_i_card_confirm_order_status = get_option('gicapi_gift_i_card_confirm_order_status', 'wc-processing');
        if ($new_status == $gift_i_card_confirm_order_status) {
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
                return;
            }

            $order->add_order_note(sprintf(
                __('Gift-i-Card order created: %s', 'gift-i-card'),
                $response['order_id']
            ));

            update_post_meta($order->get_id(), '_gic_order_id', $response['id']);
        }

        $complete_orders = get_option('gicapi_complete_orders', 'yes');
        if ($complete_orders === 'yes') {
            $order->update_status('completed');
        }
    }

    private function confirm_order($order_id)
    {
        $response = $this->api->confirm_order($order_id);
        if (is_wp_error($response)) {
            return;
        }
    }

    public function add_redeem_data_to_email($order, $sent_to_admin, $plain_text, $email)
    {
        if (get_option('gicapi_add_to_email', 'yes') !== 'yes') {
            return;
        }

        $this->display_redeem_data($order);
    }

    public function add_redeem_data_to_order_details($order)
    {
        if (get_option('gicapi_add_to_order_details', 'yes') !== 'yes') {
            return;
        }

        $this->display_redeem_data($order);
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

        $this->display_redeem_data($order);
    }

    private function display_redeem_data($order)
    {
        $gic_order_id = get_post_meta($order->get_id(), '_gic_order_id', true);
        if (!$gic_order_id) {
            return;
        }

        $response = $this->api->get_order($gic_order_id);
        if (is_wp_error($response)) {
            return;
        }

?>
        <div class="gicapi-redeem-data">
            <h2><?php _e('Gift Card Redemption Details', 'gift-i-card'); ?></h2>
            <table class="shop_table">
                <thead>
                    <tr>
                        <th><?php _e('Product', 'gift-i-card'); ?></th>
                        <th><?php _e('Code', 'gift-i-card'); ?></th>
                        <th><?php _e('Value', 'gift-i-card'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($response['items'] as $item): ?>
                        <tr>
                            <td><?php echo esc_html($item['product_name']); ?></td>
                            <td><?php echo esc_html($item['code']); ?></td>
                            <td><?php echo esc_html($item['value'] . ' ' . $item['currency']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php
    }
}
