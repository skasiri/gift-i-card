<?php
if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Admin
{
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_gicapi_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_gicapi_sync_categories', array($this, 'sync_categories'));
        add_action('wp_ajax_gicapi_sync_products', array($this, 'sync_products'));
        add_action('wp_ajax_gicapi_map_product', array($this, 'map_product'));
        add_action('wp_ajax_gicapi_unmap_product', array($this, 'unmap_product'));
    }

    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/gicapi-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/gicapi-admin.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'gicapi_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gicapi_nonce')
        ));
    }

    public function add_plugin_admin_menu()
    {
        add_menu_page(
            __('Gift-i-Card Settings', 'gift-i-card'),
            __('Gift-i-Card', 'gift-i-card'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_setup_page'),
            'dashicons-tickets-alt',
            56
        );

        add_submenu_page(
            $this->plugin_name,
            __('Products', 'gift-i-card'),
            __('Products', 'gift-i-card'),
            'manage_options',
            $this->plugin_name . '-products',
            array($this, 'display_plugin_products_page')
        );
    }

    public function register_settings()
    {
        register_setting('gicapi_settings', 'gicapi_base_url');
        register_setting('gicapi_settings', 'gicapi_consumer_key');
        register_setting('gicapi_settings', 'gicapi_consumer_secret');
        register_setting('gicapi_settings', 'gicapi_complete_orders');
        register_setting('gicapi_settings', 'gicapi_ignore_other_orders');
        register_setting('gicapi_settings', 'gicapi_add_to_email');
        register_setting('gicapi_settings', 'gicapi_add_to_order_details');
        register_setting('gicapi_settings', 'gicapi_add_to_thank_you');
    }

    public function display_plugin_setup_page()
    {
        include_once 'partials/gicapi-admin-display.php';
    }

    public function display_plugin_products_page()
    {
        include_once 'partials/gicapi-products-display.php';
    }

    public function test_connection()
    {
        check_ajax_referer('gicapi_test_connection', 'nonce');

        $base_url = isset($_POST['base_url']) ? sanitize_text_field($_POST['base_url']) : '';
        $consumer_key = isset($_POST['consumer_key']) ? sanitize_text_field($_POST['consumer_key']) : '';
        $consumer_secret = isset($_POST['consumer_secret']) ? sanitize_text_field($_POST['consumer_secret']) : '';

        if (!$base_url || !$consumer_key || !$consumer_secret) {
            wp_send_json_error(__('Missing required parameters', 'gift-i-card'));
        }

        $api = new GICAPI_API($base_url, $consumer_key, $consumer_secret);
        $response = $api->test_connection();

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        wp_send_json_success(__('Connection successful!', 'gift-i-card'));
    }

    public function sync_categories()
    {
        check_ajax_referer('gicapi_sync_categories', 'nonce');

        $base_url = get_option('gicapi_base_url');
        $consumer_key = get_option('gicapi_consumer_key');
        $consumer_secret = get_option('gicapi_consumer_secret');

        if (!$base_url || !$consumer_key || !$consumer_secret) {
            wp_send_json_error(__('API credentials not configured', 'gift-i-card'));
        }

        $api = new GICAPI_API($base_url, $consumer_key, $consumer_secret);
        $response = $api->get_categories();

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        foreach ($response as $category) {
            $existing = get_posts(array(
                'post_type' => 'gic_cat',
                'meta_key' => 'sku',
                'meta_value' => $category['sku'],
                'posts_per_page' => 1
            ));

            if (empty($existing)) {
                $post_id = wp_insert_post(array(
                    'post_type' => 'gic_cat',
                    'post_title' => $category['name'],
                    'post_status' => 'publish'
                ));

                if (!is_wp_error($post_id)) {
                    update_post_meta($post_id, 'sku', $category['sku']);
                }
            } else {
                $post_id = $existing[0]->ID;
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_title' => $category['name']
                ));
            }
        }

        wp_send_json_success(__('Categories synced successfully', 'gift-i-card'));
    }

    public function sync_products()
    {
        check_ajax_referer('gicapi_sync_products', 'nonce');

        $base_url = get_option('gicapi_base_url');
        $consumer_key = get_option('gicapi_consumer_key');
        $consumer_secret = get_option('gicapi_consumer_secret');

        if (!$base_url || !$consumer_key || !$consumer_secret) {
            wp_send_json_error(__('API credentials not configured', 'gift-i-card'));
        }

        $api = new GICAPI_API($base_url, $consumer_key, $consumer_secret);
        $response = $api->get_products();

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        foreach ($response as $product) {
            $existing = get_posts(array(
                'post_type' => 'gic_prod',
                'meta_key' => 'sku',
                'meta_value' => $product['sku'],
                'posts_per_page' => 1
            ));

            if (empty($existing)) {
                $post_id = wp_insert_post(array(
                    'post_type' => 'gic_prod',
                    'post_title' => $product['name'],
                    'post_status' => 'publish'
                ));

                if (!is_wp_error($post_id)) {
                    update_post_meta($post_id, 'sku', $product['sku']);
                    update_post_meta($post_id, 'category_sku', $product['category_sku']);
                }
            } else {
                $post_id = $existing[0]->ID;
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_title' => $product['name']
                ));
                update_post_meta($post_id, 'category_sku', $product['category_sku']);
            }

            foreach ($product['variants'] as $variant) {
                $existing = get_posts(array(
                    'post_type' => 'gic_var',
                    'meta_key' => 'sku',
                    'meta_value' => $variant['sku'],
                    'posts_per_page' => 1
                ));

                if (empty($existing)) {
                    $variant_id = wp_insert_post(array(
                        'post_type' => 'gic_var',
                        'post_title' => $variant['name'],
                        'post_status' => 'publish'
                    ));

                    if (!is_wp_error($variant_id)) {
                        update_post_meta($variant_id, 'sku', $variant['sku']);
                        update_post_meta($variant_id, 'product_sku', $product['sku']);
                        update_post_meta($variant_id, 'price', $variant['price']);
                        update_post_meta($variant_id, 'price_currency', $variant['price_currency']);
                        update_post_meta($variant_id, 'value', $variant['value']);
                        update_post_meta($variant_id, 'max_order_per_item', $variant['max_order_per_item']);
                        update_post_meta($variant_id, 'stock_status', $variant['stock_status']);
                    }
                } else {
                    $variant_id = $existing[0]->ID;
                    wp_update_post(array(
                        'ID' => $variant_id,
                        'post_title' => $variant['name']
                    ));
                    update_post_meta($variant_id, 'price', $variant['price']);
                    update_post_meta($variant_id, 'price_currency', $variant['price_currency']);
                    update_post_meta($variant_id, 'value', $variant['value']);
                    update_post_meta($variant_id, 'max_order_per_item', $variant['max_order_per_item']);
                    update_post_meta($variant_id, 'stock_status', $variant['stock_status']);
                }
            }
        }

        wp_send_json_success(__('Products synced successfully', 'gift-i-card'));
    }

    public function map_product()
    {
        check_ajax_referer('gicapi_map_product', 'nonce');

        $wc_product_id = isset($_POST['wc_product_id']) ? intval($_POST['wc_product_id']) : 0;
        $gic_variant_sku = isset($_POST['gic_variant_sku']) ? sanitize_text_field($_POST['gic_variant_sku']) : '';

        if (!$wc_product_id || !$gic_variant_sku) {
            wp_send_json_error(__('Invalid parameters', 'gift-i-card'));
        }

        // Get variant data
        $variant = get_posts(array(
            'post_type' => 'gic_var',
            'meta_key' => 'sku',
            'meta_value' => $gic_variant_sku,
            'posts_per_page' => 1
        ));

        if (empty($variant)) {
            wp_send_json_error(__('Variant not found', 'gift-i-card'));
        }

        $variant = $variant[0];
        $product_sku = get_post_meta($variant->ID, 'product_sku', true);
        $product = get_posts(array(
            'post_type' => 'gic_prod',
            'meta_key' => 'sku',
            'meta_value' => $product_sku,
            'posts_per_page' => 1
        ));

        if (empty($product)) {
            wp_send_json_error(__('Product not found', 'gift-i-card'));
        }

        $product = $product[0];
        $category_sku = get_post_meta($product->ID, 'category_sku', true);
        $category = get_posts(array(
            'post_type' => 'gic_cat',
            'meta_key' => 'sku',
            'meta_value' => $category_sku,
            'posts_per_page' => 1
        ));

        if (empty($category)) {
            wp_send_json_error(__('Category not found', 'gift-i-card'));
        }

        // Save mapping data to product meta
        update_post_meta($wc_product_id, '_gic_variant_sku', $gic_variant_sku);
        update_post_meta($wc_product_id, '_gic_variant_name', $variant->post_title);
        update_post_meta($wc_product_id, '_gic_product_sku', $product_sku);
        update_post_meta($wc_product_id, '_gic_product_name', $product->post_title);
        update_post_meta($wc_product_id, '_gic_category_sku', $category_sku);
        update_post_meta($wc_product_id, '_gic_category_name', $category[0]->post_title);
        update_post_meta($wc_product_id, '_gic_price', get_post_meta($variant->ID, 'price', true));
        update_post_meta($wc_product_id, '_gic_price_currency', get_post_meta($variant->ID, 'price_currency', true));
        update_post_meta($wc_product_id, '_gic_value', get_post_meta($variant->ID, 'value', true));
        update_post_meta($wc_product_id, '_gic_max_order_per_item', get_post_meta($variant->ID, 'max_order_per_item', true));
        update_post_meta($wc_product_id, '_gic_stock_status', get_post_meta($variant->ID, 'stock_status', true));

        wp_send_json_success(__('Product mapped successfully', 'gift-i-card'));
    }

    public function unmap_product()
    {
        check_ajax_referer('gicapi_unmap_product', 'nonce');

        $wc_product_id = isset($_POST['wc_product_id']) ? intval($_POST['wc_product_id']) : 0;

        if (!$wc_product_id) {
            wp_send_json_error(__('Invalid product ID', 'gift-i-card'));
        }

        // Remove all gift card meta data
        delete_post_meta($wc_product_id, '_gic_variant_sku');
        delete_post_meta($wc_product_id, '_gic_variant_name');
        delete_post_meta($wc_product_id, '_gic_product_sku');
        delete_post_meta($wc_product_id, '_gic_product_name');
        delete_post_meta($wc_product_id, '_gic_category_sku');
        delete_post_meta($wc_product_id, '_gic_category_name');
        delete_post_meta($wc_product_id, '_gic_price');
        delete_post_meta($wc_product_id, '_gic_price_currency');
        delete_post_meta($wc_product_id, '_gic_value');
        delete_post_meta($wc_product_id, '_gic_max_order_per_item');
        delete_post_meta($wc_product_id, '_gic_stock_status');

        wp_send_json_success(__('Product unmapped successfully', 'gift-i-card'));
    }
}
