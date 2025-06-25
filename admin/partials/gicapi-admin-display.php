<?php
if (!defined('ABSPATH')) {
    exit;
}

$base_url = get_option('gicapi_base_url');
$consumer_key = get_option('gicapi_consumer_key');
$consumer_secret = get_option('gicapi_consumer_secret');
$complete_orders = get_option('gicapi_complete_orders', 'yes');
$add_to_email = get_option('gicapi_add_to_email', 'yes');
$add_to_order_details = get_option('gicapi_add_to_order_details', 'yes');
$add_to_thank_you = get_option('gicapi_add_to_thank_you', 'yes');

// New order processing options
$enable_order_processing = get_option('gicapi_enable', 'no');
$gift_i_card_create_order_status = get_option('gicapi_gift_i_card_create_order_status', 'wc-processing');
$auto_complete_orders = get_option('gicapi_auto_complete_orders', 'none');
$change_failed_status = get_option('gicapi_change_failed_status', 'none');
$failed_status = get_option('gicapi_failed_status', 'failed');
$hook_priority = get_option('gicapi_hook_priority', '10');
$complete_status = get_option('gicapi_complete_status', 'wc-completed');

// Get WooCommerce order statuses
$wc_order_statuses = array();
if (class_exists('WooCommerce')) {
    $wc_order_statuses = wc_get_order_statuses();
}
?>

<div class="wrap">
    <h1><?php _e('Gift-i-Card Settings', 'gift-i-card'); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="#connection" class="nav-tab nav-tab-active"><?php _e('Connection', 'gift-i-card'); ?></a>
        <a href="#orders" class="nav-tab"><?php _e('Orders', 'gift-i-card'); ?></a>
        <a href="#display" class="nav-tab"><?php _e('Display', 'gift-i-card'); ?></a>
        <a href="#data-management" class="nav-tab"><?php _e('Data Management', 'gift-i-card'); ?></a>
    </h2>

    <form method="post" action="options.php">
        <?php settings_fields('gicapi_settings'); ?>

        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-connection.php'; ?>
        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-orders.php'; ?>
        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-display.php'; ?>
        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-data-management.php'; ?>

        <?php submit_button(); ?>
    </form>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Tab functionality
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();

            // Remove active class from all tabs
            $('.nav-tab').removeClass('nav-tab-active');

            // Add active class to clicked tab
            $(this).addClass('nav-tab-active');

            // Hide all tab content
            $('.tab-content').hide();

            // Show the selected tab content
            $($(this).attr('href')).show();
        });

        // Show first tab by default
        $('.nav-tab:first').trigger('click');
    });
</script>

<style>
    .tab-content {
        margin-top: 20px;
    }

    .nav-tab-wrapper {
        margin-bottom: 20px;
    }
</style>