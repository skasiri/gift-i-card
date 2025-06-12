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

        if ($base_url && $consumer_key && $consumer_secret) {
            $this->api = new GICAPI_API($base_url, $consumer_key, $consumer_secret);
        }

        add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 3);
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

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $ignore_other_orders = get_option('gicapi_ignore_other_orders', 'yes');
        $has_gift_card = false;

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variant_sku = get_post_meta($product_id, '_gic_variant_sku', true);

            if ($variant_sku) {
                $has_gift_card = true;
                break;
            }
        }

        if (!$has_gift_card && $ignore_other_orders === 'yes') {
            return;
        }

        if ($new_status === 'processing') {
            $this->process_order($order);
        }
    }

    private function process_order($order)
    {
        $items = array();

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $variant_sku = get_post_meta($product_id, '_gic_variant_sku', true);

            if (!$variant_sku) {
                continue;
            }

            $items[] = array(
                'variant_sku' => $variant_sku,
                'quantity' => $item->get_quantity()
            );
        }

        if (empty($items)) {
            return;
        }

        $response = $this->api->create_order(array(
            'order_id' => $order->get_id(),
            'customer_email' => $order->get_billing_email(),
            'customer_name' => $order->get_formatted_billing_full_name(),
            'items' => $items
        ));

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

        update_post_meta($order->get_id(), '_gic_order_id', $response['order_id']);

        $complete_orders = get_option('gicapi_complete_orders', 'yes');
        if ($complete_orders === 'yes') {
            $order->update_status('completed');
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
