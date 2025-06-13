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
    <h1><?php _e('Gift-i-Card Settings', 'gift-i-card'); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="#connection" class="nav-tab nav-tab-active"><?php _e('اتصال', 'gift-i-card'); ?></a>
        <a href="#orders" class="nav-tab"><?php _e('سفارشات', 'gift-i-card'); ?></a>
        <a href="#display" class="nav-tab"><?php _e('نمایش', 'gift-i-card'); ?></a>
        <a href="#data-management" class="nav-tab"><?php _e('داده ها', 'gift-i-card'); ?></a>
    </h2>

    <form method="post" action="options.php">
        <?php settings_fields('gicapi_settings'); ?>

        <div id="connection" class="tab-content">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="gicapi_base_url"><?php _e('Base URL', 'gift-i-card'); ?></label>
                    </th>
                    <td>
                        <input type="url" name="gicapi_base_url" id="gicapi_base_url" value="<?php echo esc_attr($base_url); ?>" class="regular-text" required>
                        <p class="description"><?php _e('The base URL of the gift-i-card service API', 'gift-i-card'); ?></p>
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

            <?php if (isset($base_url) && isset($consumer_key) && isset($consumer_secret)) : ?>
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
            <?php endif; ?>
        </div>

        <div id="orders" class="tab-content" style="display: none;">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Complete Orders', 'gift-i-card'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="gicapi_complete_orders" value="yes" <?php checked($complete_orders, 'yes'); ?>>
                            <?php _e('Change WooCommerce order status to completed (after completing Gift-i-Card order)', 'gift-i-card'); ?>
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
                            <?php _e('Ignore orders that do not contain Gift-i-Card related products', 'gift-i-card'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div id="display" class="tab-content" style="display: none;">
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
        </div>

        <div id="data-management" class="tab-content" style="display: none;">
            <h3><?php _e('Data Management', 'gift-i-card'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Delete All Data', 'gift-i-card'); ?></th>
                    <td>
                        <button type="button" id="gicapi-delete-all-data" class="button button-danger" style="background-color: #dc3545; color: white;">
                            <?php _e('Delete All Plugin Data', 'gift-i-card'); ?>
                        </button>
                        <p class="description" style="color: #dc3545;">
                            <?php _e('Warning: This will permanently delete all plugin data. This action cannot be undone!', 'gift-i-card'); ?>
                        </p>
                        <div id="gicapi-delete-data-message" style="margin-top: 10px;"></div>
                    </td>
                </tr>
            </table>
        </div>

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