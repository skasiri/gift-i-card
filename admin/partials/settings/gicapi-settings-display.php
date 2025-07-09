<?php
if (!defined('ABSPATH')) {
    exit;
}

$add_to_email = get_option('gicapi_add_to_email', 'no');
$add_to_order_details = get_option('gicapi_add_to_order_details', 'no');
$add_to_thank_you = get_option('gicapi_add_to_thank_you', 'no');
?>

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