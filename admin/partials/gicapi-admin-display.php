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
        <a href="#products" class="nav-tab"><?php esc_html_e('Products', 'gift-i-card'); ?></a>
        <a href="#cron" class="nav-tab"><?php esc_html_e('Cron Jobs', 'gift-i-card'); ?></a>
        <a href="#display" class="nav-tab"><?php esc_html_e('Display', 'gift-i-card'); ?></a>
    </h2>

    <form method="post" action="options.php">
        <?php settings_fields('gicapi_settings'); ?>

        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-connection.php'; ?>
        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-orders.php'; ?>
        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-product.php'; ?>
        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-cron.php'; ?>
        <?php include_once plugin_dir_path(__FILE__) . 'settings/gicapi-settings-display.php'; ?>

        <?php submit_button(); ?>
    </form>
</div>