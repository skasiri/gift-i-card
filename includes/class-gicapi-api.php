<?php

/**
 * The API functionality of the plugin.
 *
 * @link       https://gift-i-card.com
 * @since      1.0.0
 *
 * @package    GICAPI
 * @subpackage GICAPI/includes
 */

/**
 * The API functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    GICAPI
 * @subpackage GICAPI/includes
 * @author     Gift-i-Card <info@gift-i-card.com>
 */
class GICAPI_API
{
    private static $instance = null;
    private $base_url;
    private $consumer_key;
    private $consumer_secret;
    private $jwt;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->base_url = get_option('gicapi_base_url');
        $this->consumer_key = get_option('gicapi_consumer_key');
        $this->consumer_secret = get_option('gicapi_consumer_secret');
        $this->jwt = GICAPI_JWT::get_instance();
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function get_token()
    {
        $token = get_transient('gicapi_token');

        if (!$token || !$this->jwt->validate_token($token)) {
            $response = wp_remote_post($this->base_url . '/auth/get-token', array(
                'body' => array(
                    'consumer_key' => $this->consumer_key,
                    'consumer_secret' => $this->consumer_secret
                )
            ));

            if (is_wp_error($response)) {
                return false;
            }

            $headers = wp_remote_retrieve_headers($response);
            if (!isset($headers['Authorization'])) {
                return false;
            }

            $token = $headers['Authorization'];
            set_transient('gicapi_token', $token, $this->jwt->get_token_expiry() - 300); // 5 minutes less than expiry
        }

        return $token;
    }

    public function force_refresh_token()
    {
        // حذف توکن قبلی از حافظه موقت
        delete_transient('gicapi_token');

        // تلاش برای دریافت توکن جدید
        $response = wp_remote_post($this->base_url . '/auth/get-token', array(
            'timeout' => 30, // افزایش زمان انتظار برای اطمینان از دریافت پاسخ
            'body' => array(
                'consumer_key' => $this->consumer_key,
                'consumer_secret' => $this->consumer_secret
            )
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }

        $headers = wp_remote_retrieve_headers($response);
        if (!isset($headers['Authorization'])) {
            return false;
        }

        $token = $headers['Authorization'];

        // ذخیره توکن جدید با زمان انقضای کمتر از زمان واقعی برای اطمینان
        set_transient('gicapi_token', $token, $this->jwt->get_token_expiry() - 300);

        return $token;
    }

    private function make_request($endpoint, $method = 'GET', $body = array())
    {
        $token = $this->get_token();
        if (!$token) {
            return false;
        }

        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            )
        );

        $url = $this->base_url . $endpoint;

        // Add timestamp to prevent caching
        $timestamp = time();

        if (!empty($body)) {
            if (strtoupper($method) === 'GET') {
                $body['_t'] = $timestamp; // Add timestamp parameter
                $url = add_query_arg($body, $url);
            } else {
                $body['_t'] = $timestamp; // Add timestamp to POST body
                $args['body'] = json_encode($body);
            }
        } else {
            // Add timestamp even if no body parameters
            if (strtoupper($method) === 'GET') {
                $url = add_query_arg(array('_t' => $timestamp), $url);
            } else {
                $args['body'] = json_encode(array('_t' => $timestamp));
            }
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        if (wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $response_body = wp_remote_retrieve_body($response);
        if (empty($response_body)) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function get_balance()
    {
        return $this->make_request('/auth/status');
    }

    public function get_categories()
    {
        return $this->make_request('/giftcard/category-list');
    }

    public function get_products($category_sku, $page = 1, $page_size = 10)
    {
        return $this->make_request('/giftcard/product-list', 'GET', array(
            'category_sku' => $category_sku,
            'page' => $page,
            'page_size' => $page_size
        ));
    }

    public function get_variants($product_sku)
    {
        return $this->make_request('/giftcard/variant-list', 'GET', array(
            'sku' => $product_sku
        ));
    }

    public function buy_product($sku, $quantity, $webhook_url = null)
    {
        if ($webhook_url === null) {
            $webhook_url = '';
            if (is_ssl()) {
                $webhook_url = rest_url('gicapi/v1/webhook');
            }
        }

        return $this->make_request('/giftcard/buy', 'POST', array(
            'sku' => $sku,
            'quantity' => $quantity,
            'webhook_url' => $webhook_url
        ));
    }

    public function confirm_order($order_id)
    {
        // Validate order_id before making request
        if (empty($order_id)) {
            return false;
        }

        return $this->make_request('/giftcard/confirm', 'POST', array(
            'order_id' => $order_id
        ));
    }

    public function get_order($order_id)
    {
        // Validate order_id before making request
        if (empty($order_id)) {
            return false;
        }

        return $this->make_request('/giftcard/retrieve', 'GET', array(
            'order_id' => $order_id
        ));
    }
}
