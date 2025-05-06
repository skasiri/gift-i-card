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

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!isset($body['token'])) {
                return false;
            }

            $token = $body['token'];
            set_transient('gicapi_token', $token, $this->jwt->get_token_expiry() - 300); // 5 minutes less than expiry
        }

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
                'Content-Type' => 'application/json'
            )
        );

        if (!empty($body)) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($this->base_url . $endpoint, $args);

        if (is_wp_error($response)) {
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

    public function buy_product($sku, $quantity)
    {
        $webhook_url = '';
        if (is_ssl()) {
            $webhook_url = rest_url('gicapi/v1/webhook');
        }

        return $this->make_request('/giftcard/buy', 'POST', array(
            'sku' => $sku,
            'quantity' => $quantity,
            'webhook_url' => $webhook_url
        ));
    }

    public function confirm_order($order_id)
    {
        return $this->make_request('/giftcard/confirm', 'POST', array(
            'order_id' => $order_id
        ));
    }

    public function get_order($order_id)
    {
        return $this->make_request('/giftcard/retrieve', 'GET', array(
            'order_id' => $order_id
        ));
    }
}
