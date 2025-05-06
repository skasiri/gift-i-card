<?php

class GICAPI_Webhook
{
    public static function handle_webhook()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            wp_send_json_error(__('Invalid data', 'gift-i-card'), 400);
        }

        if (!isset($data['order_id'])) {
            wp_send_json_error(__('Order ID is required', 'gift-i-card'), 400);
        }

        $order_id = sanitize_text_field($data['order_id']);
        $status = isset($data['status']) ? sanitize_text_field($data['status']) : '';

        $gic_order = get_posts(array(
            'post_type' => 'gic_buy',
            'meta_key' => 'order_id',
            'meta_value' => $order_id,
            'posts_per_page' => 1
        ));

        if (empty($gic_order)) {
            wp_send_json_error(__('Order not found', 'gift-i-card'), 404);
        }

        $gic_order = $gic_order[0];
        $wc_order_id = get_post_meta($gic_order->ID, 'wc_order_id', true);

        if (empty($wc_order_id)) {
            wp_send_json_error(__('WooCommerce order not found', 'gift-i-card'), 404);
        }

        $order = wc_get_order($wc_order_id);
        if (!$order) {
            wp_send_json_error(__('WooCommerce order not found', 'gift-i-card'), 404);
        }

        // Update order data
        update_post_meta($gic_order->ID, 'order_data', $data);

        // Update order status
        if ($status === 'completed' && get_option('gicapi_complete_order', false)) {
            $order->update_status('completed');
        }

        wp_send_json_success(__('Webhook processed successfully', 'gift-i-card'));
    }

    public static function register_webhook_endpoint()
    {
        register_rest_route('gicapi/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array('GICAPI_Webhook', 'handle_webhook'),
            'permission_callback' => '__return_true'
        ));
    }
}
