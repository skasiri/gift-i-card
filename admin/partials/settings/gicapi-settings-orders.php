<?php
if (!defined('ABSPATH')) {
    exit;
}

// New order processing options
$enable_order_processing = get_option('gicapi_enable', 'no');
$gift_i_card_create_order_status = get_option('gicapi_gift_i_card_create_order_status', 'wc-pending');
$gift_i_card_confirm_order_status = get_option('gicapi_gift_i_card_confirm_order_status', 'wc-processing');
$auto_complete_orders = get_option('gicapi_auto_complete_orders', 'none');
$change_failed_status = get_option('gicapi_change_failed_status', 'none');
$failed_status = get_option('gicapi_failed_status', 'wc-failed');
$hook_priority = get_option('gicapi_hook_priority', '10');
$complete_status = get_option('gicapi_complete_status', 'wc-completed');

// Get WooCommerce order statuses
$wc_order_statuses = array();
if (class_exists('WooCommerce')) {
    $wc_order_statuses = wc_get_order_statuses();
}
?>

<div id="orders" class="tab-content" style="display: none;">
    <table class="form-table">
        <tr>
            <th scope="row">
                <?php _e('Enable Order Processing', 'gift-i-card'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="gicapi_enable" value="yes" <?php checked($enable_order_processing, 'yes'); ?>>
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
            <th colspan="2">
                <h3><?php _e('🔄 Gift-i-Card Order Processing', 'gift-i-card'); ?></h3>
            </th>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_gift_i_card_create_order_status"><?php _e('Create Order at Status', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select name="gicapi_gift_i_card_create_order_status" id="gicapi_gift_i_card_create_order_status">
                    <?php foreach ($wc_order_statuses as $status_key => $status_label) : ?>
                        <option value="<?php echo esc_attr($status_key); ?>" <?php selected($gift_i_card_create_order_status, $status_key); ?>>
                            <?php echo esc_html($status_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Status to set when creating Gift-i-Card pending order. This status will be used to create the order in the Gift-i-Card system.', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_gift_i_card_confirm_order_status"><?php _e('Confirm Gift-i-Card Order at Status', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select name="gicapi_gift_i_card_confirm_order_status" id="gicapi_gift_i_card_confirm_order_status">
                    <?php foreach ($wc_order_statuses as $status_key => $status_label) : ?>
                        <option value="<?php echo esc_attr($status_key); ?>" <?php selected($gift_i_card_confirm_order_status, $status_key); ?>>
                            <?php echo esc_html($status_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Status to set when confirming Gift-i-Card order. This status will be used to confirm the order in the Gift-i-Card system. After this status, the order will be processed.', 'gift-i-card'); ?></p>
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
            <th colspan="2">
                <h3><?php _e('✅ Successfully Processed Orders', 'gift-i-card'); ?></h3>
            </th>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_auto_complete_orders"><?php _e('Change Order Status', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select name="gicapi_auto_complete_orders" id="gicapi_auto_complete_orders">
                    <option value="none" <?php selected($auto_complete_orders, 'none'); ?>><?php _e('None', 'gift-i-card'); ?></option>
                    <option value="all" <?php selected($auto_complete_orders, 'all'); ?>><?php _e('All', 'gift-i-card'); ?></option>
                    <option value="mapped" <?php selected($auto_complete_orders, 'mapped'); ?>><?php _e('Orders with mapped items', 'gift-i-card'); ?></option>
                </select>
                <p class="description"><?php _e('Change Order Status After Successfully Processing', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_complete_status"><?php _e('Change to Status', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select name="gicapi_complete_status" id="gicapi_complete_status">
                    <?php foreach ($wc_order_statuses as $status_key => $status_label) : ?>
                        <option value="<?php echo esc_attr($status_key); ?>" <?php selected($complete_status, $status_key); ?>>
                            <?php echo esc_html($status_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Status to set when successfully processed orders', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <hr>
            </td>
        </tr>

        <tr>
            <th colspan="2">
                <h3><?php _e('❌ Failed Orders', 'gift-i-card'); ?></h3>
            </th>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_change_failed_status"><?php _e('Change Order Status', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select name="gicapi_change_failed_status" id="gicapi_change_failed_status">
                    <option value="none" <?php selected($change_failed_status, 'none'); ?>><?php _e('None', 'gift-i-card'); ?></option>
                    <option value="all" <?php selected($change_failed_status, 'all'); ?>><?php _e('All', 'gift-i-card'); ?></option>
                    <option value="mapped" <?php selected($change_failed_status, 'mapped'); ?>><?php _e('Orders with mapped items', 'gift-i-card'); ?></option>
                </select>
                <p class="description"><?php _e('Change Order Status When Failed', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_failed_status"><?php _e('Change to Status', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select name="gicapi_failed_status" id="gicapi_failed_status">
                    <?php foreach ($wc_order_statuses as $status_key => $status_label) : ?>
                        <option value="<?php echo esc_attr($status_key); ?>" <?php selected($failed_status, $status_key); ?>>
                            <?php echo esc_html($status_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Status to set when failed orders', 'gift-i-card'); ?></p>
            </td>
        </tr>
    </table>
</div>