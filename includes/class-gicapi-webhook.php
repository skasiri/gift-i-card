<?php

class GICAPI_Webhook
{
    private static $order_manager = null;

    public static function handle_webhook($request)
    {
        // Check if WordPress functions are available
        if (!function_exists('wp_send_json_error') || !function_exists('__')) {
            http_response_code(500);
            exit('WordPress functions not available');
        }

        // Verify request method
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(__('Only POST method is allowed', 'gift-i-card'), 405);
        }

        // Get and validate input data
        $input = file_get_contents('php://input');
        if (empty($input)) {
            wp_send_json_error(__('Empty request body', 'gift-i-card'), 400);
        }

        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid JSON format', 'gift-i-card'), 400);
        }

        if (empty($data)) {
            wp_send_json_error(__('Invalid data', 'gift-i-card'), 400);
        }

        if (!isset($data['order_id'])) {
            wp_send_json_error(__('Order ID is required', 'gift-i-card'), 400);
        }

        // Sanitize input data
        $order_id = sanitize_text_field($data['order_id']);
        $status = isset($data['status']) ? sanitize_text_field($data['status']) : '';

        // Validate order_id format (assuming it's alphanumeric)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $order_id)) {
            wp_send_json_error(__('Invalid order ID format', 'gift-i-card'), 400);
        }

        // دریافت مقدار secret از پارامترهای ریکوئست
        $secret = $request->get_param('secret');
        if (empty($secret)) {
            wp_send_json_error(__('Webhook secret is missing', 'gift-i-card'), 400);
        }

        // Get order manager instance
        if (self::$order_manager === null) {
            self::$order_manager = GICAPI_Order_Manager::get_instance();
        }

        // Find WooCommerce orders that contain this Gift-i-Card order
        $wc_orders = self::$order_manager->find_wc_orders_by_gic_order_id($order_id);

        if (empty($wc_orders)) {
            wp_send_json_error(__('No WooCommerce orders found for this Gift-i-Card order', 'gift-i-card'), 404);
        }

        // بررسی secret برای هر سفارش پیدا شده
        $valid_secret = false;
        foreach ($wc_orders as $wc_order_id) {
            $order = wc_get_order($wc_order_id);
            if ($order && $order->get_meta('_gicapi_webhook_secret', true) === $secret) {
                $valid_secret = true;
                break;
            }
        }
        if (!$valid_secret) {
            wp_send_json_error(__('Invalid webhook secret', 'gift-i-card'), 403);
        }

        $updated_orders = array();
        $errors = array();

        foreach ($wc_orders as $wc_order_id) {
            $result = self::$order_manager->update_single_order($wc_order_id, $data, 'webhook');
            if ($result['success']) {
                $updated_orders[] = $wc_order_id;
            } else {
                $errors[] = $result['error'];
            }
        }

        // Send success response
        wp_send_json_success(array(
            'message' => __('Webhook processed successfully', 'gift-i-card'),
            'order_id' => $order_id,
            'status' => $status,
            'updated_wc_orders' => $updated_orders,
            'errors' => $errors
        ));
    }

    public static function register_webhook_endpoint()
    {
        register_rest_route('gicapi/v1', '/webhook/(?P<secret>[a-zA-Z0-9]+)', array(
            'methods' => 'POST',
            'callback' => array('GICAPI_Webhook', 'handle_webhook'),
            'permission_callback' => array('GICAPI_Webhook', 'check_webhook_permission'),
            'args' => array(
                'secret' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'order_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }

    public static function check_webhook_permission($request)
    {
        // Basic security check - you can enhance this based on your needs
        // For example, check for a secret token or IP whitelist

        // Check if the request is coming from a trusted source
        $user_agent = $request->get_header('User-Agent');
        $content_type = $request->get_header('Content-Type');

        // Log webhook access for security monitoring
        $remote_addr = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';

        // For now, allow all requests but log them
        // In production, you should implement proper authentication
        return true;
    }
}
