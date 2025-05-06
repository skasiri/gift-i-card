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
        include_once 'partials/gicapi-products-display.php';
    }
}
