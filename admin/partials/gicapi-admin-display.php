<?php
if (!defined('ABSPATH')) {
    exit;
}

$base_url = get_option('gicapi_base_url');
$consumer_key = get_option('gicapi_consumer_key');
$consumer_secret = get_option('gicapi_consumer_secret');
$complete_orders = get_option('gicapi_complete_orders', 'yes');
$ignore_other_orders = get_option('gicapi_ignore_other_orders', 'yes');
$add_to_email = get_option('gicapi_add_to_email', 'yes');
$add_to_order_details = get_option('gicapi_add_to_order_details', 'yes');
$add_to_thank_you = get_option('gicapi_add_to_thank_you', 'yes');
?>

<div class="wrap">
    <h1><?php _e('Gift Card Settings', 'gift-i-card'); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('gicapi_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="gicapi_base_url"><?php _e('Base URL', 'gift-i-card'); ?></label>
                </th>
                <td>
                    <input type="url" name="gicapi_base_url" id="gicapi_base_url" value="<?php echo esc_attr($base_url); ?>" class="regular-text" required>
                    <p class="description"><?php _e('The base URL of the gift card service API', 'gift-i-card'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="gicapi_consumer_key"><?php _e('Consumer Key', 'gift-i-card'); ?></label>
                </th>
                <td>
                    <input type="text" name="gicapi_consumer_key" id="gicapi_consumer_key" value="<?php echo esc_attr($consumer_key); ?>" class="regular-text" required>
                    <p class="description"><?php _e('The consumer key for API authentication', 'gift-i-card'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="gicapi_consumer_secret"><?php _e('Consumer Secret', 'gift-i-card'); ?></label>
                </th>
                <td>
                    <input type="password" name="gicapi_consumer_secret" id="gicapi_consumer_secret" value="<?php echo esc_attr($consumer_secret); ?>" class="regular-text" required>
                    <p class="description"><?php _e('The consumer secret for API authentication', 'gift-i-card'); ?></p>
                </td>
            </tr>

        </table>

        <?php
        // Check if $is_connected is set and true by the GICAPI_Admin class
        if (isset($is_connected) && $is_connected) :
        ?>
            <h2><?php _e('Token Management', 'gift-i-card'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Refresh Token', 'gift-i-card'); ?></th>
                    <td>
                        <button type="button" id="gicapi-force-refresh-token-button" class="button button-secondary">
                            <?php _e('Get New Token', 'gift-i-card'); ?>
                        </button>
                        <p class="description"><?php _e('Click this button to discard the current token and get a new one from the API.', 'gift-i-card'); ?></p>
                        <div id="gicapi-refresh-token-message" style="margin-top: 10px;"></div>
                    </td>
                </tr>
            </table>
        <?php
        endif;
        ?>

        <h2><?php _e('Order Settings', 'gift-i-card'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php _e('Complete Orders', 'gift-i-card'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="gicapi_complete_orders" value="yes" <?php checked($complete_orders, 'yes'); ?>>
                        <?php _e('Automatically complete WooCommerce orders after successful gift card redemption', 'gift-i-card'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('Ignore Other Orders', 'gift-i-card'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="gicapi_ignore_other_orders" value="yes" <?php checked($ignore_other_orders, 'yes'); ?>>
                        <?php _e('Ignore orders that do not contain gift card products', 'gift-i-card'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h2><?php _e('Display Settings', 'gift-i-card'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php _e('Add to Email', 'gift-i-card'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="gicapi_add_to_email" value="yes" <?php checked($add_to_email, 'yes'); ?>>
                        <?php _e('Add redeem data to order emails', 'gift-i-card'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('Add to Order Details', 'gift-i-card'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="gicapi_add_to_order_details" value="yes" <?php checked($add_to_order_details, 'yes'); ?>>
                        <?php _e('Add redeem data to order details page', 'gift-i-card'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php _e('Add to Thank You', 'gift-i-card'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="gicapi_add_to_thank_you" value="yes" <?php checked($add_to_thank_you, 'yes'); ?>>
                        <?php _e('Add redeem data to thank you page', 'gift-i-card'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>