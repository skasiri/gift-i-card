<?php
if (!defined('ABSPATH')) {
    exit;
}

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
        add_action('wp_ajax_gicapi_map_variant', array($this, 'ajax_map_variant'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style('gicapi-admin', plugin_dir_url(__FILE__) . 'css/gicapi-admin.css', array(), $this->version, 'all');
        wp_enqueue_style('select2', plugin_dir_url(__FILE__) . 'css/select2.min.css', array(), '4.1.0');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script('gicapi-admin', plugin_dir_url(__FILE__) . 'js/gicapi-admin.js', array('jquery'), $this->version, false);
        wp_enqueue_script('select2', plugin_dir_url(__FILE__) . 'js/select2.min.js', array('jquery'), '4.1.0', true);

        // Prepare parameters for JavaScript, including nonces and localized text
        $script_params = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gicapi_admin_nonce'),
            'force_refresh_token_nonce' => wp_create_nonce('gicapi_force_refresh_token_action'),
            'text_refreshing_token' => __('Refreshing token...', 'gift-i-card'),
            'text_error_unknown' => __('An unknown error occurred.', 'gift-i-card'),
            'text_error_server_communication' => __('Error communicating with server: ', 'gift-i-card')
        );

        // انتقال پارامترها به اسکریپت gicapi-admin
        wp_localize_script('gicapi-admin', 'gicapi_admin_params', $script_params);
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
        // Only show on plugin pages
        $screen = get_current_screen();
        $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

        // Check if we're on a plugin page
        $is_plugin_page = false;
        if ($screen && strpos($screen->id, 'gift-i-card') !== false) {
            $is_plugin_page = true;
        }
        if (strpos($current_page, 'gift-i-card') !== false) {
            $is_plugin_page = true;
        }

        // If not on plugin page, don't show the notice
        if (!$is_plugin_page) {
            return;
        }

        $api = new GICAPI_API();
        $response = $api->get_balance();
        // echo 'response: ' . json_encode($response);
        $is_connected = ($response && isset($response['balance']) && $response['balance']);

        if ($is_connected) {
            $balance = number_format($response['balance'], 0, '.', ',');
            $currency = $response['currency'];
            echo '<div class="notice notice-success is-dismissible">
                <p>Gift-i-Card API: <strong style="color: green;">' . esc_html__('Connected', 'gift-i-card') . '</strong> | ' . esc_html__('Balance', 'gift-i-card') . ': <strong>' . esc_html($balance) . ' ' . esc_html($currency) . '</strong></p>
            </div>';
        } else {
            echo '<div class="notice notice-error is-dismissible">
                <p>Gift-i-Card API: <strong style="color: red;">' . esc_html__('Disconnected', 'gift-i-card') . '</strong></p>
            </div>';
        }
    }

    public function register_settings()
    {
        register_setting('gicapi_settings', 'gicapi_base_url', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_base_url')
        ));
        register_setting('gicapi_settings', 'gicapi_consumer_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_consumer_secret', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_complete_orders', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        // register_setting('gicapi_settings', 'gicapi_ignore_other_orders');
        register_setting('gicapi_settings', 'gicapi_add_to_email', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_add_to_order_details', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_add_to_thank_you', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));

        // New order processing settings
        register_setting('gicapi_settings', 'gicapi_enable', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_gift_i_card_create_order_status', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_gift_i_card_confirm_order_status', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_auto_complete_orders', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_change_failed_status', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_failed_status', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_hook_priority', array(
            'type' => 'integer',
            'default' => 10,
            'sanitize_callback' => array($this, 'sanitize_hook_priority')
        ));

        // Cron job settings
        register_setting('gicapi_settings', 'gicapi_enable_cron_updates', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_cron_interval', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_change_cancelled_status', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_cancelled_status', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));

        // Products synchronization settings
        register_setting('gicapi_settings', 'gicapi_products_sync_enabled', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_sync_interval', array(
            'type' => 'integer',
            'default' => 300,
            'sanitize_callback' => array($this, 'sanitize_sync_interval')
        ));
        register_setting('gicapi_settings', 'gicapi_instant_status', array(
            'type' => 'string',
            'default' => 'no_change',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_manual_status', array(
            'type' => 'string',
            'default' => 'no_change',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_outofstock_status', array(
            'type' => 'string',
            'default' => 'no_change',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_deleted_status', array(
            'type' => 'string',
            'default' => 'no_change',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('gicapi_settings', 'gicapi_auto_sync_enabled', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field'
        ));
    }

    public function sanitize_base_url($url)
    {
        $url = trim($url);
        return rtrim($url, '/');
    }

    public function sanitize_hook_priority($priority)
    {
        $priority = intval($priority);
        return max(1, min(100, $priority)); // Ensure priority is between 1 and 100
    }

    public function sanitize_sync_interval($interval)
    {
        $interval = intval($interval);
        return max(30, min(1440, $interval)); // Ensure interval is between 30 and 1440 minutes
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
        $category_sku = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : '';
        $product_sku = isset($_GET['product']) ? sanitize_text_field(wp_unslash($_GET['product'])) : '';

        // echo 'product_sku: ' . $product_sku . '<br>';
        // echo 'category_sku: ' . $category_sku . '<br>';

        if ($product_sku) {
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
    private function sync_items($api_items, $post_type, $sku_meta_key, $map_api_to_post_args_callback, $parent_meta_key = null, $parent_sku = null)
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

        if ($parent_meta_key && $parent_sku) {
            $local_items_query_args['meta_query'][] = [
                'key' => $parent_meta_key,
                'value' => $parent_sku,
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
            list($post_args_base, $meta_input_base) = call_user_func($map_api_to_post_args_callback, $api_item, $parent_sku);

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
            wp_send_json_success(array('message' => __('Token refreshed successfully. Please refresh the page or save the settings to see the new connection status.', 'gift-i-card')));
        } else {
            wp_send_json_error(array('message' => __('Error refreshing token. Please check the API settings and try again.', 'gift-i-card')));
        }
    }



    public function ajax_map_variant()
    {
        check_ajax_referer('gicapi_map_variant', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'gift-i-card'));
            return;
        }

        $wc_product_id = isset($_POST['wc_product_id']) ? absint(wp_unslash($_POST['wc_product_id'])) : 0;
        $gic_variant_id = isset($_POST['gic_variant_id']) ? sanitize_text_field(wp_unslash($_POST['gic_variant_id'])) : '';

        if (!$wc_product_id || !$gic_variant_id) {
            wp_send_json_error(__('Invalid product or variant ID.', 'gift-i-card'));
            return;
        }

        update_post_meta($gic_variant_id, '_gicapi_mapped_wc_product_id', $wc_product_id);
        wp_send_json_success(__('Variant mapped successfully.', 'gift-i-card'));
    }
}
