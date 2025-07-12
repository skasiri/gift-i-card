<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get current options following project pattern
$products_sync_enabled = get_option('gicapi_products_sync_enabled', 'no');
$sync_interval = get_option('gicapi_sync_interval', 300);
$instant_status = get_option('gicapi_instant_status', 'no_change');
$manual_status = get_option('gicapi_manual_status', 'no_change');
$outofstock_status = get_option('gicapi_outofstock_status', 'no_change');
$deleted_status = get_option('gicapi_deleted_status', 'no_change');
$auto_sync_enabled = get_option('gicapi_auto_sync_enabled', 'no');
?>

<div id="products" class="tab-content" style="display: none;">
    <h3><?php esc_html_e('Products Synchronization Settings', 'gift-i-card'); ?></h3>
    <p><?php esc_html_e('Configure how Gift-i-Card product statuses should be synchronized with WooCommerce stock status.', 'gift-i-card'); ?></p>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="gicapi_products_sync_enabled"><?php esc_html_e('Enable Products Synchronization', 'gift-i-card'); ?></label>
            </th>
            <td>
                <input type="checkbox" id="gicapi_products_sync_enabled" name="gicapi_products_sync_enabled" value="yes" <?php checked($products_sync_enabled, 'yes'); ?> />
                <p class="description"><?php esc_html_e('Enable automatic synchronization of Gift-i-Card product statuses with WooCommerce', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_sync_interval"><?php esc_html_e('Sync Interval (minutes)', 'gift-i-card'); ?></label>
            </th>
            <td>
                <input type="number" id="gicapi_sync_interval" name="gicapi_sync_interval" value="<?php echo esc_attr($sync_interval); ?>" min="30" max="1440" />
                <p class="description"><?php esc_html_e('How often to check for product status updates (minimum 30 minutes)', 'gift-i-card'); ?></p>
            </td>
        </tr>
    </table>

    <h4><?php esc_html_e('Gift-i-Card Status Mapping', 'gift-i-card'); ?></h4>
    <p><?php esc_html_e('Configure how each Gift-i-Card delivery status should affect WooCommerce stock status:', 'gift-i-card'); ?></p>

    <table class="form-table">
        <!-- Instant Delivery Status -->
        <tr>
            <th scope="row">
                <label for="gicapi_instant_status"><?php esc_html_e('Instant Delivery', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select id="gicapi_instant_status" name="gicapi_instant_status">
                    <option value="no_change" <?php selected($instant_status, 'no_change'); ?>><?php esc_html_e('No Change', 'gift-i-card'); ?></option>
                    <option value="instock" <?php selected($instant_status, 'instock'); ?>><?php esc_html_e('Change to In Stock', 'gift-i-card'); ?></option>
                    <option value="onbackorder" <?php selected($instant_status, 'onbackorder'); ?>><?php esc_html_e('Change to On Back Order', 'gift-i-card'); ?></option>
                    <option value="outofstock" <?php selected($instant_status, 'outofstock'); ?>><?php esc_html_e('Change to Out of Stock', 'gift-i-card'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Action when gift card has instant/automatic delivery', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <!-- Manual Delivery Status -->
        <tr>
            <th scope="row">
                <label for="gicapi_manual_status"><?php esc_html_e('Manual Delivery', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select id="gicapi_manual_status" name="gicapi_manual_status">
                    <option value="no_change" <?php selected($manual_status, 'no_change'); ?>><?php esc_html_e('No Change', 'gift-i-card'); ?></option>
                    <option value="instock" <?php selected($manual_status, 'instock'); ?>><?php esc_html_e('Change to In Stock', 'gift-i-card'); ?></option>
                    <option value="onbackorder" <?php selected($manual_status, 'onbackorder'); ?>><?php esc_html_e('Change to On Back Order', 'gift-i-card'); ?></option>
                    <option value="outofstock" <?php selected($manual_status, 'outofstock'); ?>><?php esc_html_e('Change to Out of Stock', 'gift-i-card'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Action when gift card requires operator intervention', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <!-- Out of Stock Status -->
        <tr>
            <th scope="row">
                <label for="gicapi_outofstock_status"><?php esc_html_e('Out of Stock', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select id="gicapi_outofstock_status" name="gicapi_outofstock_status">
                    <option value="no_change" <?php selected($outofstock_status, 'no_change'); ?>><?php esc_html_e('No Change', 'gift-i-card'); ?></option>
                    <option value="instock" <?php selected($outofstock_status, 'instock'); ?>><?php esc_html_e('Change to In Stock', 'gift-i-card'); ?></option>
                    <option value="onbackorder" <?php selected($outofstock_status, 'onbackorder'); ?>><?php esc_html_e('Change to On Back Order', 'gift-i-card'); ?></option>
                    <option value="outofstock" <?php selected($outofstock_status, 'outofstock'); ?>><?php esc_html_e('Change to Out of Stock', 'gift-i-card'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Action when gift card is out of stock', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <!-- Deleted/Not Available Status -->
        <tr>
            <th scope="row">
                <label for="gicapi_deleted_status"><?php esc_html_e('Deleted/Not Available', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select id="gicapi_deleted_status" name="gicapi_deleted_status">
                    <option value="no_change" <?php selected($deleted_status, 'no_change'); ?>><?php esc_html_e('No Change', 'gift-i-card'); ?></option>
                    <option value="instock" <?php selected($deleted_status, 'instock'); ?>><?php esc_html_e('Change to In Stock', 'gift-i-card'); ?></option>
                    <option value="onbackorder" <?php selected($deleted_status, 'onbackorder'); ?>><?php esc_html_e('Change to On Back Order', 'gift-i-card'); ?></option>
                    <option value="outofstock" <?php selected($deleted_status, 'outofstock'); ?>><?php esc_html_e('Change to Out of Stock', 'gift-i-card'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Action when gift card is deleted or not available', 'gift-i-card'); ?></p>
            </td>
        </tr>
    </table>

    <h4><?php esc_html_e('Advanced Settings', 'gift-i-card'); ?></h4>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="gicapi_auto_sync_enabled"><?php esc_html_e('Auto Sync on Product Load', 'gift-i-card'); ?></label>
            </th>
            <td>
                <input type="checkbox" id="gicapi_auto_sync_enabled" name="gicapi_auto_sync_enabled" value="yes" <?php checked($auto_sync_enabled, 'yes'); ?> />
                <p class="description"><?php esc_html_e('Automatically sync product status when product page is loaded. This may slow down the product page loading slightly.', 'gift-i-card'); ?></p>
            </td>
        </tr>
    </table>
</div>