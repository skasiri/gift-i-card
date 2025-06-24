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
$enable_order_processing = get_option('gicapi_enable_order_processing', 'no');
$gift_card_order_status = get_option('gicapi_gift_card_order_status', 'wc-processing');
$auto_complete_orders = get_option('gicapi_auto_complete_orders', 'none');
$change_failed_status = get_option('gicapi_change_failed_status', 'none');
$failed_status = get_option('gicapi_failed_status', 'failed');
$hook_priority = get_option('gicapi_hook_priority', '10');

// Get WooCommerce order statuses
$wc_order_statuses = array();
if (class_exists('WooCommerce')) {
    $wc_order_statuses = wc_get_order_statuses();
}
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
                        <?php _e('Enable Order Processing', 'gift-i-card'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="gicapi_enable_order_processing" value="yes" <?php checked($enable_order_processing, 'yes'); ?>>
                            <?php _e('Enable order processing functionality', 'gift-i-card'); ?>
                        </label>
                        <p class="description"><?php _e('By disabling this, orders will not be processed automatically.', 'gift-i-card'); ?></p>
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <hr>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="gicapi_gift_card_order_status"><?php _e('Create Gift-i-Card Order at Status', 'gift-i-card'); ?></label>
                    </th>
                    <td>
                        <select name="gicapi_gift_card_order_status" id="gicapi_gift_card_order_status">
                            <?php foreach ($wc_order_statuses as $status_key => $status_label) : ?>
                                <option value="<?php echo esc_attr($status_key); ?>" <?php selected($gift_card_order_status, $status_key); ?>>
                                    <?php echo esc_html($status_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Status to set when creating Gift-i-Card orders', 'gift-i-card'); ?></p>
                    </td>
                </tr>


                <tr>
                    <th scope="row">
                        <label for="gicapi_hook_priority"><?php _e('Hook Priority', 'gift-i-card'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="gicapi_hook_priority" id="gicapi_hook_priority" value="<?php echo esc_attr($hook_priority); ?>" class="small-text" min="1" max="100">
                        <p class="description"><?php _e('Priority for hook execution (default: 10)', 'gift-i-card'); ?></p>
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <hr>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="gicapi_auto_complete_orders"><?php _e('Auto Complete Orders', 'gift-i-card'); ?></label>
                    </th>
                    <td>
                        <select name="gicapi_auto_complete_orders" id="gicapi_auto_complete_orders">
                            <option value="none" <?php selected($auto_complete_orders, 'none'); ?>><?php _e('None', 'gift-i-card'); ?></option>
                            <option value="all" <?php selected($auto_complete_orders, 'all'); ?>><?php _e('All', 'gift-i-card'); ?></option>
                            <option value="mapped" <?php selected($auto_complete_orders, 'mapped'); ?>><?php _e('Orders with mapped items', 'gift-i-card'); ?></option>
                        </select>
                        <p class="description"><?php _e('Automatically complete orders based on selection', 'gift-i-card'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="gicapi_complete_status"><?php _e('Complete Status', 'gift-i-card'); ?></label>
                    </th>
                    <td>
                        <select name="gicapi_complete_status" id="gicapi_complete_status">
                            <?php foreach ($wc_order_statuses as $status_key => $status_label) : ?>
                                <option value="<?php echo esc_attr($status_key); ?>" <?php selected($complete_status, $status_key); ?>>
                                    <?php echo esc_html($status_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Status to set when completing orders', 'gift-i-card'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="gicapi_change_failed_status"><?php _e('Change Failed Status', 'gift-i-card'); ?></label>
                    </th>
                    <td>
                        <select name="gicapi_change_failed_status" id="gicapi_change_failed_status">
                            <option value="none" <?php selected($change_failed_status, 'none'); ?>><?php _e('None', 'gift-i-card'); ?></option>
                            <option value="all" <?php selected($change_failed_status, 'all'); ?>><?php _e('All', 'gift-i-card'); ?></option>
                            <option value="mapped" <?php selected($change_failed_status, 'mapped'); ?>><?php _e('Mapped', 'gift-i-card'); ?></option>
                        </select>
                        <p class="description"><?php _e('Orders to change status when failed', 'gift-i-card'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="gicapi_failed_status"><?php _e('Failed Status', 'gift-i-card'); ?></label>
                    </th>
                    <td>
                        <select name="gicapi_failed_status" id="gicapi_failed_status">
                            <?php foreach ($wc_order_statuses as $status_key => $status_label) : ?>
                                <option value="<?php echo esc_attr($status_key); ?>" <?php selected($failed_status, $status_key); ?>>
                                    <?php echo esc_html($status_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Status to set when orders fail', 'gift-i-card'); ?></p>
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