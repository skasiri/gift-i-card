<?php
if (!defined('ABSPATH')) {
    exit;
}

class GICAPI_Activator
{
    public static function activate()
    {
        self::create_post_types();
        self::create_options();
        flush_rewrite_rules();
    }

    private static function create_post_types()
    {
        // Register category post type
        register_post_type('gic_cat', array(
            'labels' => array(
                'name' => __('Gift Card Categories', 'gift-i-card'),
                'singular_name' => __('Gift Card Category', 'gift-i-card')
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => array('title')
        ));

        // Register product post type
        register_post_type('gic_prod', array(
            'labels' => array(
                'name' => __('Gift Card Products', 'gift-i-card'),
                'singular_name' => __('Gift Card Product', 'gift-i-card')
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => array('title')
        ));

        // Register variant post type
        register_post_type('gic_var', array(
            'labels' => array(
                'name' => __('Gift Card Variants', 'gift-i-card'),
                'singular_name' => __('Gift Card Variant', 'gift-i-card')
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => array('title')
        ));
    }

    private static function create_options()
    {
        add_option('gicapi_base_url', '');
        add_option('gicapi_consumer_key', '');
        add_option('gicapi_consumer_secret', '');
        add_option('gicapi_complete_orders', 'yes');
        add_option('gicapi_ignore_other_orders', 'yes');
        add_option('gicapi_add_to_email', 'yes');
        add_option('gicapi_add_to_order_details', 'yes');
        add_option('gicapi_add_to_thank_you', 'yes');
    }
}
