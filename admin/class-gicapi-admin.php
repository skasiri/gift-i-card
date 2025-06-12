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
        add_action('wp_ajax_gicapi_force_refresh_token', array($this, 'ajax_force_refresh_token'));
        add_action('wp_ajax_gicapi_delete_all_data', array($this, 'ajax_delete_all_data'));
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
        wp_enqueue_script(
            'gicapi-admin',
            plugin_dir_url(__FILE__) . 'js/gicapi-admin.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script('gicapi-admin', 'gicapi_admin', array(
            'nonce' => wp_create_nonce('gicapi_admin_nonce')
        ));

        // Prepare parameters for JavaScript, including nonces and localized text
        $script_params = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'force_refresh_token_nonce' => wp_create_nonce('gicapi_force_refresh_token_action'),
            'text_refreshing_token' => __('در حال دریافت توکن جدید...', 'gift-i-card'),
            'text_error_unknown' => __('An unknown error occurred.', 'gift-i-card'),
            'text_error_server_communication' => __('خطا در ارتباط با سرور: ', 'gift-i-card'),
            // Add any other existing params here if gicapi_admin_params was previously used
        );
        wp_localize_script($this->plugin_name, 'gicapi_admin_params', $script_params);
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
        // Determine connection status to pass to the view
        $api = GICAPI_API::get_instance();
        $response = $api->get_balance();
        // Use the same logic as in display_connection_status for consistency
        // Assuming 'balance' key indicates successful connection and contains balance.
        // If 'success' => true is the indicator, adjust accordingly.
        $is_connected = ($response && isset($response['balance']) && is_numeric($response['balance']));

        include_once 'partials/gicapi-admin-display.php'; // $is_connected will be available in this partial
    }

    public function display_plugin_products_page()
    {
        // Handle update actions first
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';
        $category_sku = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
        $product_id = isset($_GET['product']) ? absint($_GET['product']) : 0;

        $api = GICAPI_API::get_instance(); // Use singleton instance

        if ($action && $nonce && wp_verify_nonce($nonce, 'gicapi_update_data')) {
            $redirect_url = remove_query_arg(array('action', '_wpnonce'));

            switch ($action) {
                case 'update_categories':
                    $api_response = $api->get_categories();
                    if ($api_response && is_array($api_response) && !is_wp_error($api_response)) {
                        $this->sync_items(
                            $api_response,
                            'gic_cat',
                            '_gicapi_category_sku',
                            function ($api_cat_item) {
                                $post_args = [
                                    'post_title' => sanitize_text_field($api_cat_item['name']),
                                ];
                                $meta_input = [
                                    '_gicapi_category_sku' => sanitize_text_field($api_cat_item['sku']),
                                    '_gicapi_category_count' => absint($api_cat_item['count']),
                                    '_gicapi_category_permalink' => isset($api_cat_item['permalink']) ? esc_url_raw($api_cat_item['permalink']) : '',
                                    '_gicapi_category_thumbnail' => isset($api_cat_item['thumbnail']) ? esc_url_raw($api_cat_item['thumbnail']) : '',
                                ];
                                return [$post_args, $meta_input];
                            }
                        );
                    } else {
                        // Handle API error, maybe add an admin notice
                        // error_log('GICAPI: Error fetching categories from API.');
                    }
                    wp_safe_redirect($redirect_url);
                    exit;

                case 'update_products':
                    if ($category_sku) {
                        $api_response = $api->get_products($category_sku, 1, 999); // Fetch all products for the category
                        $api_products_list = [];
                        if ($api_response && isset($api_response['products']) && is_array($api_response['products']) && !is_wp_error($api_response)) {
                            $api_products_list = $api_response['products'];
                        } elseif (is_wp_error($api_response)) {
                            // error_log('GICAPI: Error fetching products from API: ' . $api_response->get_error_message());
                        }

                        $this->sync_items(
                            $api_products_list,
                            'gic_prod',
                            '_gicapi_product_sku',
                            function ($api_prod_item, $parent_id_val) {
                                $post_args = [
                                    'post_title' => sanitize_text_field($api_prod_item['name']),
                                ];
                                $meta_input = [
                                    '_gicapi_product_sku' => sanitize_text_field($api_prod_item['sku']),
                                    '_gicapi_product_url' => isset($api_prod_item['url']) ? esc_url_raw($api_prod_item['url']) : '',
                                    '_gicapi_product_image_url' => isset($api_prod_item['image_url']) ? esc_url_raw($api_prod_item['image_url']) : '',
                                    '_gicapi_product_variant_count' => isset($api_prod_item['variant_count']) ? absint($api_prod_item['variant_count']) : 0,
                                    '_gicapi_product_category' => $parent_id_val,
                                ];
                                return [$post_args, $meta_input];
                            },
                            '_gicapi_product_category',
                            $category_sku
                        );
                        wp_safe_redirect($redirect_url);
                        exit;
                    }
                    break;

                case 'update_variants':
                    if ($product_id) {
                        $product_sku = get_post_meta($product_id, '_gicapi_product_sku', true);
                        if ($product_sku) {
                            $api_response = $api->get_variants($product_sku, 1, 999); // Fetch all variants
                            $api_variants_list = [];

                            if ($api_response && !is_wp_error($api_response)) {
                                if (isset($api_response['variants']) && is_array($api_response['variants'])) {
                                    $api_variants_list = $api_response['variants'];
                                } elseif (is_array($api_response)) { // Fallback for direct array
                                    $api_variants_list = $api_response;
                                }
                            } elseif (is_wp_error($api_response)) {
                                // error_log('GICAPI: Error fetching variants from API: ' . $api_response->get_error_message());
                            }

                            $this->sync_items(
                                $api_variants_list,
                                'gic_var',
                                '_gicapi_variant_sku',
                                function ($api_var_item, $parent_id_val) {
                                    $post_args = [
                                        'post_title' => sanitize_text_field($api_var_item['name']),
                                    ];
                                    $meta_input = [
                                        '_gicapi_variant_sku' => sanitize_text_field($api_var_item['sku']),
                                        '_gicapi_variant_price' => isset($api_var_item['price']) ? sanitize_text_field($api_var_item['price']) : '',
                                        '_gicapi_variant_value' => isset($api_var_item['value']) ? sanitize_text_field($api_var_item['value']) : '',
                                        '_gicapi_variant_max_order' => isset($api_var_item['max_order']) ? absint($api_var_item['max_order']) : 0,
                                        '_gicapi_variant_stock_status' => isset($api_var_item['stock_status']) ? sanitize_text_field($api_var_item['stock_status']) : '',
                                        '_gicapi_variant_product' => $parent_id_val,
                                    ];
                                    return [$post_args, $meta_input];
                                },
                                '_gicapi_variant_product',
                                $product_id
                            );
                        }
                        wp_safe_redirect($redirect_url);
                        exit;
                    }
                    break;
            }
        }

        // Pass plugin name to views
        $plugin_name = $this->plugin_name;

        // Get categories if not exists (Initial Population)
        if (!$category_sku) { // On main categories page
            $existing_cats_query = new WP_Query([
                'post_type' => 'gic_cat',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'meta_query' => [['key' => '_gicapi_is_deleted', 'compare' => 'NOT EXISTS']]
            ]);
            if (!$existing_cats_query->have_posts()) {
                $response = $api->get_categories();
                if ($response && is_array($response) && !is_wp_error($response)) {
                    foreach ($response as $category) {
                        $cat_id = wp_insert_post(array(
                            'post_title' => sanitize_text_field($category['name']),
                            'post_type' => 'gic_cat',
                            'post_status' => 'publish'
                        ));
                        if ($cat_id && !is_wp_error($cat_id)) {
                            update_post_meta($cat_id, '_gicapi_category_sku', sanitize_text_field($category['sku']));
                            update_post_meta($cat_id, '_gicapi_category_count', absint($category['count']));
                            if (isset($category['permalink'])) update_post_meta($cat_id, '_gicapi_category_permalink', esc_url_raw($category['permalink']));
                            if (isset($category['thumbnail'])) update_post_meta($cat_id, '_gicapi_category_thumbnail', esc_url_raw($category['thumbnail']));
                            delete_post_meta($cat_id, '_gicapi_is_deleted'); // Ensure not marked deleted
                        }
                    }
                }
            }
            wp_reset_postdata();
        }

        // Get products if category selected (Initial Population for that category)
        if ($category_sku && !$product_id) {
            $existing_prods_query = new WP_Query([
                'post_type' => 'gic_prod',
                'posts_per_page' => 1,
                'fields' => 'ids',
                'meta_query' => [
                    'relation' => 'AND',
                    ['key' => '_gicapi_product_category', 'value' => $category_sku],
                    ['key' => '_gicapi_is_deleted', 'compare' => 'NOT EXISTS']
                ]
            ]);
            if (!$existing_prods_query->have_posts()) {
                $response = $api->get_products($category_sku, 1, 50); // Original page size
                if ($response && isset($response['products']) && is_array($response['products']) && !is_wp_error($response)) {
                    foreach ($response['products'] as $product) {
                        $post_id = wp_insert_post(array(
                            'post_title' => sanitize_text_field($product['name']),
                            'post_type' => 'gic_prod',
                            'post_status' => 'publish'
                        ));
                        if ($post_id && !is_wp_error($post_id)) {
                            update_post_meta($post_id, '_gicapi_product_sku', sanitize_text_field($product['sku']));
                            if (isset($product['url'])) update_post_meta($post_id, '_gicapi_product_url', esc_url_raw($product['url']));
                            if (isset($product['image_url'])) update_post_meta($post_id, '_gicapi_product_image_url', esc_url_raw($product['image_url']));
                            if (isset($product['variant_count'])) update_post_meta($post_id, '_gicapi_product_variant_count', absint($product['variant_count']));
                            update_post_meta($post_id, '_gicapi_product_category', $category_sku);
                            delete_post_meta($post_id, '_gicapi_is_deleted'); // Ensure not marked deleted
                        }
                    }
                }
            }
            wp_reset_postdata();
        }

        // Get variants if product selected (Initial Population for that product)
        if ($product_id) {
            $product = get_post($product_id);
            if ($product) {
                $existing_vars_query = new WP_Query([
                    'post_type' => 'gic_var',
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                    'meta_query' => [
                        'relation' => 'AND',
                        ['key' => '_gicapi_variant_product', 'value' => $product_id],
                        ['key' => '_gicapi_is_deleted', 'compare' => 'NOT EXISTS']
                    ]
                ]);
                if (!$existing_vars_query->have_posts()) {
                    $product_sku = get_post_meta($product->ID, '_gicapi_product_sku', true);
                    if ($product_sku) {
                        $response = $api->get_variants($product_sku, 1, 999); // Original page size
                        $variants_list = [];
                        if ($response && !is_wp_error($response)) {
                            if (isset($response['variants']) && is_array($response['variants'])) {
                                $variants_list = $response['variants'];
                            } elseif (is_array($response)) {
                                $variants_list = $response;
                            }
                        }
                        if (!empty($variants_list)) {
                            foreach ($variants_list as $variant) {
                                $post_id = wp_insert_post(array(
                                    'post_title' => sanitize_text_field($variant['name']),
                                    'post_type' => 'gic_var',
                                    'post_status' => 'publish'
                                ));
                                if ($post_id && !is_wp_error($post_id)) {
                                    update_post_meta($post_id, '_gicapi_variant_sku', sanitize_text_field($variant['sku']));
                                    if (isset($variant['price'])) update_post_meta($post_id, '_gicapi_variant_price', sanitize_text_field($variant['price']));
                                    if (isset($variant['value'])) update_post_meta($post_id, '_gicapi_variant_value', sanitize_text_field($variant['value']));
                                    if (isset($variant['max_order'])) update_post_meta($post_id, '_gicapi_variant_max_order', absint($variant['max_order']));
                                    if (isset($variant['stock_status'])) update_post_meta($post_id, '_gicapi_variant_stock_status', sanitize_text_field($variant['stock_status']));
                                    update_post_meta($post_id, '_gicapi_variant_product', $product_id);
                                    delete_post_meta($post_id, '_gicapi_is_deleted'); // Ensure not marked deleted
                                }
                            }
                        }
                    }
                }
                wp_reset_postdata();
            }
        }

        // Load appropriate view
        if ($product_id) {
            include_once plugin_dir_path(__FILE__) . 'partials/gicapi-variants-display.php';
        } elseif ($category_sku) {
            include_once plugin_dir_path(__FILE__) . 'partials/gicapi-products-display.php';
        } else {
            include_once plugin_dir_path(__FILE__) . 'partials/gicapi-categories-display.php';
        }
    }

    /**
     * Syncs items from an API response with local WordPress posts.
     * Updates existing posts, creates new ones, and marks missing posts as deleted.
     *
     * @param array    $api_items The array of items from the API.
     * @param string   $post_type The post type to sync.
     * @param string   $sku_meta_key The meta key storing the unique SKU.
     * @param callable $map_api_to_post_args_callback A callback function that takes an API item and an optional parent ID,
     *                                                and returns an array: [$post_args, $meta_input].
     * @param string|null $parent_meta_key Optional. The meta key on the post type that links to a parent post.
     * @param int|null    $parent_id Optional. The ID of the parent post if syncing child items.
     */
    private function sync_items($api_items, $post_type, $sku_meta_key, $map_api_to_post_args_callback, $parent_meta_key = null, $parent_id = null)
    {
        $processed_skus = [];
        $local_items_query_args = [
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => $sku_meta_key,
                    'compare' => 'EXISTS'
                ]
            ],
            'fields' => 'ids' // We only need IDs and then get meta
        ];

        if ($parent_meta_key && $parent_id) {
            $local_items_query_args['meta_query'][] = [
                'key' => $parent_meta_key,
                'value' => $parent_id,
                'compare' => '='
            ];
        }

        $local_items_query = new WP_Query($local_items_query_args);
        $local_items_by_sku = [];
        if ($local_items_query->have_posts()) {
            foreach ($local_items_query->posts as $local_post_id) {
                $sku = get_post_meta($local_post_id, $sku_meta_key, true);
                if ($sku) {
                    // Trim the SKU from local meta
                    $local_items_by_sku[trim($sku)] = $local_post_id;
                }
            }
        }
        wp_reset_postdata();

        if (!is_array($api_items)) {
            // error_log("GICAPI Sync: API items was not an array for post type {$post_type}.");
            $api_items = []; // Ensure it's an array to prevent errors
        }

        foreach ($api_items as $api_item) {
            list($post_args_base, $meta_input_base) = call_user_func($map_api_to_post_args_callback, $api_item, $parent_id);

            // Trim the SKU from API item's mapped meta
            $sku_value = isset($meta_input_base[$sku_meta_key]) ? trim($meta_input_base[$sku_meta_key]) : null;

            if (!$sku_value) { // Skip if SKU is not present or empty after trim
                // error_log("GICAPI Sync: SKU missing or empty for an API item. Post Type: {$post_type}. Item: " . print_r($api_item, true));
                continue;
            }
            $processed_skus[] = $sku_value;

            $post_args = array_merge($post_args_base, [
                'post_type'   => $post_type,
                'post_status' => 'publish',
            ]);

            $meta_input = $meta_input_base;
            // Ensure the SKU in meta_input is also trimmed for consistency if it's re-saved
            if (isset($meta_input[$sku_meta_key])) {
                $meta_input[$sku_meta_key] = $sku_value;
            }

            if (isset($local_items_by_sku[$sku_value])) {
                // Update existing
                $post_id_to_update = $local_items_by_sku[$sku_value];
                $post_args['ID'] = $post_id_to_update;
                wp_update_post(wp_slash($post_args)); // wp_slash for data from external source before DB
                foreach ($meta_input as $key => $value) {
                    update_post_meta($post_id_to_update, $key, wp_slash($value));
                }
                delete_post_meta($post_id_to_update, '_gicapi_is_deleted');
            } else {
                // Insert new
                $new_post_id = wp_insert_post(wp_slash($post_args));
                if (!is_wp_error($new_post_id)) {
                    foreach ($meta_input as $key => $value) {
                        add_post_meta($new_post_id, $key, wp_slash($value), true);
                    }
                    delete_post_meta($new_post_id, '_gicapi_is_deleted');
                } else {
                    // error_log("GICAPI Sync: Error inserting post. Post Type: {$post_type}. SKU: {$sku_value}. Error: " . $new_post_id->get_error_message());
                }
            }
        }

        // Mark local items not in API response as deleted
        foreach ($local_items_by_sku as $sku => $local_post_id_to_check) {
            if (!in_array($sku, $processed_skus)) {
                update_post_meta($local_post_id_to_check, '_gicapi_is_deleted', 'true');
            }
        }
    }

    /**
     * Helper function to delete ALL posts of a specific type.
     *
     * @param string $post_type Post type to delete.
     */
    private function delete_all_posts($post_type)
    {
        $post_ids = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'any', // Include trashed posts if any
            'fields' => 'ids', // Only get IDs for efficiency
        ));

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
    // private function get_product_ids_by_category($category_id) {
    //    // ... (code is now commented out or removed as it's not directly used in the simplified delete logic)
    // }

    /**
     * Handles the AJAX request to force refresh the API token.
     */
    public function ajax_force_refresh_token()
    {
        // Verify the nonce for security
        check_ajax_referer('gicapi_force_refresh_token_action', '_ajax_nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'gift-i-card')), 403);
            return;
        }

        $api = GICAPI_API::get_instance();
        $new_token = $api->force_refresh_token();

        if ($new_token) {
            // Optionally, you can try to verify the new token by making a quick call, e.g., get_balance
            wp_send_json_success(array('message' => __('توکن با موفقیت تازه‌سازی شد. لطفاً برای مشاهده وضعیت اتصال جدید، صفحه را رفرش کنید یا تنظیمات را ذخیره نمایید.', 'gift-i-card')));
        } else {
            wp_send_json_error(array('message' => __('خطا در تازه‌سازی توکن. لطفاً تنظیمات API را بررسی کرده و دوباره تلاش کنید.', 'gift-i-card')));
        }
    }

    public function ajax_delete_all_data()
    {
        check_ajax_referer('gicapi_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'شما دسترسی لازم برای انجام این عملیات را ندارید.'));
            return;
        }

        try {
            // حذف تمام پست‌های مرتبط با کارت هدیه
            $post_types = array('gic_cat', 'gic_prod', 'gic_var'); // اضافه کردن سایر post type های مورد نیاز

            foreach ($post_types as $post_type) {
                $this->delete_all_posts($post_type);
            }

            wp_send_json_success(array(
                'message' => 'تمام داده‌های افزونه با موفقیت حذف شدند.'
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'خطا در حذف داده‌ها: ' . $e->getMessage()
            ));
        }
    }
}
