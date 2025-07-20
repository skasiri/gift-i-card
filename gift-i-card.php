<?php

/**
 * Plugin Name: Gift-i-Card
 * Plugin URI: http://github.com/skasiri/gift-i-card
 * Description: A plugin to integrate WooCommerce with a gift card service
 * Version: 1.1.6
 * Author: Saeid Kasiri
 * Author URI: https://gifticard.ir
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gift-i-card
 * Domain Path: /languages
 * Requires at least: 5.2
 * Requires PHP: 7.0
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Woo: 12345:342928dfsfhsf2349842374wdf4234sfd
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('GICAPI_VERSION')) {
    define('GICAPI_VERSION', '1.1.6');
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
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-webhook.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-order-manager.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-product-sync.php';
            require_once GICAPI_PLUGIN_DIR . 'admin/class-gicapi-admin.php';
            require_once GICAPI_PLUGIN_DIR . 'public/class-gicapi-public.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-ajax.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-order.php';
        }

        public function set_locale()
        {
            // WordPress automatically loads translations for plugins hosted on WordPress.org
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

            // Initialize AJAX handler
            GICAPI_Ajax::get_instance();
        }

        private function define_public_hooks()
        {
            $plugin_public = new GICAPI_Public($this->plugin_name, $this->version);

            // Enqueue public styles and scripts only when needed
            $enable_order_processing = get_option('gicapi_enable', 'no');
            if ($enable_order_processing === 'yes') {
                add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
                add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));
            }

            // Register webhook endpoint
            add_action('rest_api_init', array('GICAPI_Webhook', 'register_webhook_endpoint'));
        }

        public function run()
        {
            // Nothing to do here
        }
    }
}

function gicapi_activate()
{
    GICAPI_Activator::activate();
}

function gicapi_deactivate()
{
    GICAPI_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'gicapi_activate');
register_deactivation_hook(__FILE__, 'gicapi_deactivate');

function gicapi_run()
{
    $plugin = GICAPI::get_instance();
    $plugin->run();
}

// Initialize the plugin
gicapi_run();

// Make sure WordPress functions are available
if (!function_exists('add_action')) {
    exit;
}

// Enqueue copy script with proper WordPress functions
function gicapi_enqueue_copy_script()
{
    $copy_script_path = GICAPI_PLUGIN_DIR . 'public/js/gicapi-copy.js';

    if (file_exists($copy_script_path)) {
        wp_enqueue_script(
            'gicapi-copy',
            GICAPI_PLUGIN_URL . 'public/js/gicapi-copy.js',
            array('jquery'),
            filemtime($copy_script_path),
            true
        );
    }
}

add_action('wp_enqueue_scripts', 'gicapi_enqueue_copy_script');
add_action('admin_enqueue_scripts', 'gicapi_enqueue_copy_script');

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
