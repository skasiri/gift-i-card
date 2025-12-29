<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get current options following project pattern
$instant_status = get_option('gicapi_instant_status', 'no_change');
$manual_status = get_option('gicapi_manual_status', 'no_change');
$outofstock_status = get_option('gicapi_outofstock_status', 'no_change');
$deleted_status = get_option('gicapi_deleted_status', 'no_change');
$auto_sync_enabled = get_option('gicapi_auto_sync_enabled', 'no');
$batch_size = get_option('gicapi_sync_batch_size', 10);
?>

<div id="products" class="tab-content" style="display: none;">
    <h3><?php esc_html_e('Product Status Mapping Settings', 'gift-i-card'); ?></h3>
    <p><?php esc_html_e('Configure how Gift-i-Card product statuses should be synchronized with WooCommerce stock status.', 'gift-i-card'); ?></p>

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
                <p class="description"><?php esc_html_e('Automatically sync product status when product page is loaded.', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_sync_batch_size"><?php esc_html_e('Sync Batch Size', 'gift-i-card'); ?></label>
            </th>
            <td>
                <input type="number" id="gicapi_sync_batch_size" name="gicapi_sync_batch_size" value="<?php echo esc_attr($batch_size); ?>" min="1" max="50" step="1" />
                <p class="description"><?php esc_html_e('Number of products to process in each batch during cron job synchronization. Recommended: 10-20 products per batch.', 'gift-i-card'); ?></p>
            </td>
        </tr>
    </table>

    <h4><?php esc_html_e('Stock Synchronization Settings', 'gift-i-card'); ?></h4>
    <p><?php esc_html_e('Configure how product stock status should be synchronized from Gift-i-Card API.', 'gift-i-card'); ?></p>

    <?php
    $stock_sync_enabled = get_option('gicapi_stock_sync_enabled', 'yes');
    ?>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="gicapi_stock_sync_enabled"><?php esc_html_e('Enable Stock Sync (Default)', 'gift-i-card'); ?></label>
            </th>
            <td>
                <input type="checkbox" id="gicapi_stock_sync_enabled" name="gicapi_stock_sync_enabled" value="yes" <?php checked($stock_sync_enabled, 'yes'); ?> />
                <p class="description"><?php esc_html_e('Enable automatic stock status synchronization from Gift-i-Card API by default for all products. Individual products can override this setting.', 'gift-i-card'); ?></p>
            </td>
        </tr>
    </table>

    <h4><?php esc_html_e('Price Synchronization Settings', 'gift-i-card'); ?></h4>
    <p><?php esc_html_e('Configure how product prices should be synchronized from Gift-i-Card API.', 'gift-i-card'); ?></p>

    <?php
    $price_sync_enabled = get_option('gicapi_price_sync_enabled', 'no');
    $default_profit_margin = get_option('gicapi_default_profit_margin', 0);
    $profit_margin_type = get_option('gicapi_profit_margin_type', 'percentage');
    ?>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="gicapi_price_sync_enabled"><?php esc_html_e('Enable Price Sync', 'gift-i-card'); ?></label>
            </th>
            <td>
                <input type="checkbox" id="gicapi_price_sync_enabled" name="gicapi_price_sync_enabled" value="yes" <?php checked($price_sync_enabled, 'yes'); ?> />
                <p class="description"><?php esc_html_e('Enable automatic price synchronization from Gift-i-Card API. Prices will be updated based on variant prices plus profit margin.', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_profit_margin_type"><?php esc_html_e('Profit Margin Type', 'gift-i-card'); ?></label>
            </th>
            <td>
                <select id="gicapi_profit_margin_type" name="gicapi_profit_margin_type">
                    <option value="percentage" <?php selected($profit_margin_type, 'percentage'); ?>><?php esc_html_e('Percentage (%)', 'gift-i-card'); ?></option>
                    <option value="fixed" <?php selected($profit_margin_type, 'fixed'); ?>><?php esc_html_e('Fixed Amount', 'gift-i-card'); ?></option>
                </select>
                <p class="description"><?php esc_html_e('Choose how profit margin should be calculated: as a percentage of the variant price or as a fixed amount added to the price.', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_default_profit_margin"><?php esc_html_e('Default Profit Margin', 'gift-i-card'); ?></label>
            </th>
            <td>
                <input type="number" id="gicapi_default_profit_margin" name="gicapi_default_profit_margin" value="<?php echo esc_attr($default_profit_margin); ?>" step="0.01" min="0" />
                <p class="description">
                    <?php
                    if ($profit_margin_type === 'percentage') {
                        esc_html_e('Default profit margin as percentage (e.g., 10 for 10%). This will be added to the variant price from API.', 'gift-i-card');
                    } else {
                        esc_html_e('Default profit margin as fixed amount (e.g., 5.00). This will be added to the variant price from API.', 'gift-i-card');
                    }
                    ?>
                </p>
            </td>
        </tr>
    </table>
</div>