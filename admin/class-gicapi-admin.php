<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://gift-i-card.com
 * @since      1.0.0
 *
 * @package    GICAPI
 * @subpackage GICAPI/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    GICAPI
 * @subpackage GICAPI/admin
 * @author     Gift-i-Card <info@gift-i-card.com>
 */
class GICAPI_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'display_connection_status'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/gicapi-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/gicapi-admin.js', array('jquery'), $this->version, false);
    }

    public function add_plugin_admin_menu()
    {
        $api = new GICAPI_API();
        $response = $api->get_balance();
        $is_connected = ($response && isset($response['success']) && $response['success']);

        $menu_title = $is_connected ?
            'Gift-i-Card <span class="update-plugins count-1"><span class="update-count">LIVE</span></span>' :
            'Gift-i-Card';

        add_menu_page(
            'Gift-i-Card Settings',
            $menu_title,
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            'dashicons-tickets-alt',
            56
        );

        add_submenu_page(
            $this->plugin_name,
            'Products',
            'Products',
            'manage_options',
            $this->plugin_name . '-products',
            array($this, 'display_plugin_products_page')
        );
    }

    public function display_connection_status()
    {
        $api = new GICAPI_API();
        $response = $api->get_balance();
        // echo 'response: ' . json_encode($response);
        $is_connected = ($response && isset($response['balance']) && $response['balance']);

        if ($is_connected) {
            $balance = number_format($response['balance'], 0, '.', ',');
            $currency = $response['currency'];
            echo '<div class="notice notice-success is-dismissible">
                <p>Gift-i-Card API: <strong style="color: green;">Connected</strong> | موجودی: <strong>' . $balance . ' ' . $currency . '</strong></p>
            </div>';
        } else {
            echo '<div class="notice notice-error is-dismissible">
                <p>Gift-i-Card API: <strong style="color: red;">Disconnected</strong></p>
            </div>';
        }
    }

    public function register_settings()
    {
        register_setting('gicapi_settings', 'gicapi_base_url', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_base_url')
        ));
        register_setting('gicapi_settings', 'gicapi_consumer_key');
        register_setting('gicapi_settings', 'gicapi_consumer_secret');
        register_setting('gicapi_settings', 'gicapi_complete_orders');
        register_setting('gicapi_settings', 'gicapi_ignore_other_orders');
        register_setting('gicapi_settings', 'gicapi_add_to_email');
        register_setting('gicapi_settings', 'gicapi_add_to_order_details');
        register_setting('gicapi_settings', 'gicapi_add_to_thank_you');
    }

    public function sanitize_base_url($url)
    {
        $url = trim($url);
        return rtrim($url, '/');
    }

    public function display_plugin_setup_page()
    {
        include_once 'partials/gicapi-admin-display.php';
    }

    public function display_plugin_products_page()
    {
        // Handle update actions first
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
        $category_id = isset($_GET['category']) ? absint($_GET['category']) : 0;
        $product_id = isset($_GET['product']) ? absint($_GET['product']) : 0;

        $api = new GICAPI_API();

        if ($action && $nonce && wp_verify_nonce($nonce, 'gicapi_update_data')) {
            $redirect_url = remove_query_arg(array('action', '_wpnonce'));

            switch ($action) {
                case 'update_categories':
                    $this->delete_custom_posts('gic_var');
                    $this->delete_custom_posts('gic_prod');
                    $this->delete_custom_posts('gic_cat');
                    // Fetch and insert logic will run below
                    wp_safe_redirect($redirect_url);
                    exit;

                case 'update_products':
                    if ($category_id) {
                        $this->delete_custom_posts('gic_var', '_gicapi_variant_product', $this->get_product_ids_by_category($category_id));
                        $this->delete_custom_posts('gic_prod', '_gicapi_product_category', $category_id);
                        // Fetch and insert logic will run below
                        wp_safe_redirect($redirect_url);
                        exit;
                    }
                    break;

                case 'update_variants':
                    if ($product_id) {
                        $this->delete_custom_posts('gic_var', '_gicapi_variant_product', $product_id);
                        // Fetch and insert logic will run below
                        wp_safe_redirect($redirect_url);
                        exit;
                    }
                    break;
            }
        }

        // Get current view (moved down)
        // $category_id = isset($_GET['category']) ? absint($_GET['category']) : 0;
        // $product_id = isset($_GET['product']) ? absint($_GET['product']) : 0;

        // Pass plugin name to views
        $plugin_name = $this->plugin_name;

        // Initialize API (moved up)
        // $api = new GICAPI_API();

        // Get categories if not exists OR update action was triggered
        if (!$category_id) {
            $categories = get_posts(array(
                'post_type' => 'gic_cat',
                'posts_per_page' => -1
            ));

            if (empty($categories)) {
                $response = $api->get_categories();
                if ($response && is_array($response)) {
                    foreach ($response as $category) {
                        $cat_id = wp_insert_post(array(
                            'post_title' => $category['name'],
                            'post_type' => 'gic_cat',
                            'post_status' => 'publish'
                        ));

                        if ($cat_id) {
                            update_post_meta($cat_id, '_gicapi_category_sku', $category['sku']);
                            update_post_meta($cat_id, '_gicapi_category_count', $category['count']);
                            update_post_meta($cat_id, '_gicapi_category_permalink', $category['permalink']);
                            update_post_meta($cat_id, '_gicapi_category_thumbnail', $category['thumbnail']);
                        }
                    }
                }
            }
        }

        // Get products if category selected OR update action was triggered
        if ($category_id && !$product_id) {
            $category = get_post($category_id);
            if ($category) {
                $category_sku = get_post_meta($category->ID, '_gicapi_category_sku', true);
                $products = get_posts(array(
                    'post_type' => 'gic_prod',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_gicapi_product_category',
                            'value' => $category_id
                        )
                    )
                ));

                if (empty($products)) {
                    $response = $api->get_products($category_sku);
                    if ($response && is_array($response)) {
                        foreach ($response as $product) {
                            $post_id = wp_insert_post(array(
                                'post_title' => $product['name'],
                                'post_type' => 'gic_prod',
                                'post_status' => 'publish'
                            ));

                            if ($post_id) {
                                update_post_meta($post_id, '_gicapi_product_sku', $product['sku']);
                                update_post_meta($post_id, '_gicapi_product_url', $product['url']);
                                update_post_meta($post_id, '_gicapi_product_image_url', $product['image_url']);
                                update_post_meta($post_id, '_gicapi_product_variant_count', $product['variant_count']);
                                update_post_meta($post_id, '_gicapi_product_category', $category_id);
                            }
                        }
                    }
                }
            }
        }

        // Get variants if product selected OR update action was triggered
        if ($product_id) {
            $product = get_post($product_id);
            if ($product) {
                $product_sku = get_post_meta($product->ID, '_gicapi_product_sku', true);
                $variants = get_posts(array(
                    'post_type' => 'gic_var',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_gicapi_variant_product',
                            'value' => $product_id
                        )
                    )
                ));

                if (empty($variants)) {
                    $response = $api->get_variants($product_sku);
                    if ($response && is_array($response)) {
                        foreach ($response as $variant) {
                            $post_id = wp_insert_post(array(
                                'post_title' => $variant['name'],
                                'post_type' => 'gic_var',
                                'post_status' => 'publish'
                            ));

                            if ($post_id) {
                                update_post_meta($post_id, '_gicapi_variant_sku', $variant['sku']);
                                update_post_meta($post_id, '_gicapi_variant_price', $variant['price']);
                                update_post_meta($post_id, '_gicapi_variant_value', $variant['value']);
                                update_post_meta($post_id, '_gicapi_variant_max_order', $variant['max_order']);
                                update_post_meta($post_id, '_gicapi_variant_stock_status', $variant['stock_status']);
                                update_post_meta($post_id, '_gicapi_variant_product', $product_id);
                            }
                        }
                    }
                }
            }
        }

        // Load appropriate view
        if ($product_id) {
            include_once plugin_dir_path(__FILE__) . 'partials/gicapi-variants-display.php';
        } elseif ($category_id) {
            include_once plugin_dir_path(__FILE__) . 'partials/gicapi-products-display.php';
        } else {
            include_once plugin_dir_path(__FILE__) . 'partials/gicapi-categories-display.php';
        }
    }

    /**
     * Helper function to delete custom posts based on post type and optional meta query.
     *
     * @param string $post_type Post type to delete.
     * @param string|null $meta_key Meta key for filtering (optional).
     * @param mixed|null $meta_value Meta value or array of values for filtering (optional).
     */
    private function delete_custom_posts($post_type, $meta_key = null, $meta_value = null)
    {
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any', // Include trashed posts if any
            'fields' => 'ids', // Only get IDs for efficiency
        );

        if ($meta_key && $meta_value !== null) {
            $args['meta_query'] = array(
                array(
                    'key' => $meta_key,
                    'value' => $meta_value,
                    'compare' => is_array($meta_value) ? 'IN' : '=',
                )
            );
        }

        $post_ids = get_posts($args);

        if (!empty($post_ids)) {
            foreach ($post_ids as $post_id) {
                wp_delete_post($post_id, true); // Force delete
            }
        }
    }

    /**
     * Helper function to get product IDs based on category ID.
     *
     * @param int $category_id The category post ID.
     * @return array Array of product post IDs.
     */
    private function get_product_ids_by_category($category_id)
    {
        $args = array(
            'post_type' => 'gic_prod',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_gicapi_product_category',
                    'value' => $category_id,
                    'compare' => '=',
                )
            )
        );
        return get_posts($args);
    }
}
