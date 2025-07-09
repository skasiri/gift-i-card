<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Gift-i-Card Settings', 'gift-i-card'); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="#connection" class="nav-tab nav-tab-active"><?php esc_html_e('Connection', 'gift-i-card'); ?></a>
        <a href="#orders" class="nav-tab"><?php esc_html_e('Orders', 'gift-i-card'); ?></a>
        <a href="#cron" class="nav-tab"><?php esc_html_e('Cron Jobs', 'gift-i-card'); ?></a>
        <a href="#display" class="nav-tab"><?php esc_html_e('Display', 'gift-i-card'); ?></a>
    </h2>

    <form method="post" action="options.php">
        <?php settings_fields('gicapi_settings'); ?>

        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-connection.php'; ?>
        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-orders.php'; ?>
        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-cron.php'; ?>
        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-display.php'; ?>

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