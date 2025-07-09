<?php

/**
 * Plugin Name: Gift-i-Card
 * Plugin URI: https://gifticard.ir
 * Description: A plugin to integrate WooCommerce with a gift card service
 * Version: 1.0.0
 * Author: Saeid Kasiri
 * Author URI: https://gifticard.ir
 * Text Domain: gift-i-card
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('GICAPI_VERSION')) {
    define('GICAPI_VERSION', '1.0.0');
}

if (!defined('GICAPI_PLUGIN_DIR')) {
    define('GICAPI_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('GICAPI_PLUGIN_URL')) {
    define('GICAPI_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!class_exists('GICAPI')) {
    class GICAPI
    {
        private static $instance = null;
        private $plugin_name;
        private $version;

        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct()
        {
            $this->plugin_name = 'gift-i-card';
            $this->version = GICAPI_VERSION;

            $this->load_dependencies();
            add_action('plugins_loaded', array($this, 'set_locale'));
            $this->define_admin_hooks();
            $this->define_public_hooks();
        }

        private function load_dependencies()
        {
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-activator.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-deactivator.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-api.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-jwt.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-cron.php';
            require_once GICAPI_PLUGIN_DIR . 'admin/class-gicapi-admin.php';
            require_once GICAPI_PLUGIN_DIR . 'public/class-gicapi-public.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-ajax.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-order.php';
        }

        public function set_locale()
        {
            load_plugin_textdomain(
                'gift-i-card',
                false,
                dirname(plugin_basename(__FILE__)) . '/languages/'
            );
        }

        private function define_admin_hooks()
        {
            $plugin_admin = new GICAPI_Admin($this->plugin_name, $this->version);

            add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
            add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));
            add_action('admin_menu', array($plugin_admin, 'add_plugin_admin_menu'));
            add_action('admin_init', array($plugin_admin, 'register_settings'));

            // Initialize cron job
            GICAPI_Cron::get_instance();
        }

        private function define_public_hooks()
        {
            $plugin_public = new GICAPI_Public($this->plugin_name, $this->version);

            // Always enqueue styles and scripts for admin pages
            add_action('admin_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
            add_action('admin_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));

            // Also enqueue for frontend if order processing is enabled
            $enable_order_processing = get_option('gicapi_enable', 'no');
            if ($enable_order_processing === 'yes') {
                add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
                add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));
            }
        }

        public function run()
        {
            // Nothing to do here
        }
    }
}

function activate_gicapi()
{
    GICAPI_Activator::activate();
}

function deactivate_gicapi()
{
    GICAPI_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_gicapi');
register_deactivation_hook(__FILE__, 'deactivate_gicapi');

function run_gicapi()
{
    $plugin = GICAPI::get_instance();
    $plugin->run();
}

// Initialize the plugin
run_gicapi();

// Make sure WordPress functions are available
if (!function_exists('add_action')) {
    exit;
}

function gicapi_enqueue_copy_script()
{
    wp_enqueue_script(
        'gicapi-copy',
        plugins_url('public/js/gicapi-copy.js', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'public/js/gicapi-copy.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'gicapi_enqueue_copy_script');
add_action('admin_enqueue_scripts', 'gicapi_enqueue_copy_script');
