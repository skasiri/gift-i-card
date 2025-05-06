<?php

/**
 * Plugin Name: Gift-i-Card
 * Plugin URI: https://example.com/gift-card
 * Description: A plugin to integrate WooCommerce with a gift card service
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: gift-i-card
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
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
            $this->set_locale();
            $this->define_admin_hooks();
            $this->define_public_hooks();
        }

        private function load_dependencies()
        {
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-activator.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-deactivator.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-api.php';
            require_once GICAPI_PLUGIN_DIR . 'admin/class-gicapi-admin.php';
            require_once GICAPI_PLUGIN_DIR . 'public/class-gicapi-public.php';
            require_once GICAPI_PLUGIN_DIR . 'includes/class-gicapi-jwt.php';
        }

        private function set_locale()
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
        }

        private function define_public_hooks()
        {
            $plugin_public = new GICAPI_Public($this->plugin_name, $this->version);

            add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
            add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));
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

run_gicapi();
