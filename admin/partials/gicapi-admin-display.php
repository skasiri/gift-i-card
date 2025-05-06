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

            <tr>
                <th scope="row">
                    <?php _e('Test Connection', 'gift-i-card'); ?>
                </th>
                <td>
                    <button type="button" id="gicapi_test_connection" class="button button-secondary"><?php _e('Test Connection', 'gift-i-card'); ?></button>
                    <span id="gicapi_test_result" class="description"></span>
                </td>
            </tr>
        </table>

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

<script>
    jQuery(document).ready(function($) {
        $('#gicapi_test_connection').on('click', function() {
            var $button = $(this);
            var $result = $('#gicapi_test_result');

            $button.prop('disabled', true).text('<?php _e('Testing...', 'gift-i-card'); ?>');
            $result.removeClass('error success').text('');

            $.post(ajaxurl, {
                action: 'gicapi_test_connection',
                nonce: '<?php echo wp_create_nonce('gicapi_test_connection'); ?>',
                base_url: $('#gicapi_base_url').val(),
                consumer_key: $('#gicapi_consumer_key').val(),
                consumer_secret: $('#gicapi_consumer_secret').val()
            }, function(response) {
                if (response.success) {
                    $result.addClass('success').text('<?php _e('Connection successful!', 'gift-i-card'); ?>');
                } else {
                    $result.addClass('error').text(response.data);
                }
            }).always(function() {
                $button.prop('disabled', false).text('<?php _e('Test Connection', 'gift-i-card'); ?>');
            });
        });
    });
</script>