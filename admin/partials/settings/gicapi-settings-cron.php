<?php
if (!defined('ABSPATH')) {
    exit;
}

// Cron job settings
$enable_cron_updates = get_option('gicapi_enable_cron_updates', 'yes');
$cron_interval = get_option('gicapi_cron_interval', 'gicapi_five_minutes');

// Product sync settings
$products_sync_enabled = get_option('gicapi_products_sync_enabled', 'no');
$products_sync_interval = get_option('gicapi_products_sync_interval', 'twicedaily');

// Get cron status
$cron_status = array();
$product_sync_cron_status = array();
if (class_exists('GICAPI_Cron')) {
    $cron = GICAPI_Cron::get_instance();

    // Check and repair cron job if needed
    $cron->check_and_repair_cron();

    $cron_status = $cron->get_cron_status();
    $product_sync_cron_status = $cron->get_product_sync_cron_status();
}

// Get available cron intervals
$cron_intervals = wp_get_schedules();
$available_intervals = array();
foreach ($cron_intervals as $interval => $schedule) {
    $available_intervals[$interval] = $schedule['display'];
}
?>

<div id="cron" class="tab-content" style="display: none;">
    <table class="form-table">
        <tr>
            <th colspan="2">
                <h3><?php esc_html_e('🔄 Product Synchronization Cron Job', 'gift-i-card'); ?></h3>
            </th>
        </tr>

        <tr>
            <th scope="row">
                <?php esc_html_e('Product Sync Cron Job', 'gift-i-card'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="gicapi_products_sync_enabled" value="yes" <?php checked($products_sync_enabled, 'yes'); ?>>
                    <?php esc_html_e('Enable Cron Job for Product Status Synchronization', 'gift-i-card'); ?>
                </label>
                <p class="description">
                    <?php esc_html_e('Automatically sync product statuses from Gift-i-Card to WooCommerce at regular intervals. This ensures your product stock statuses are always up to date.', 'gift-i-card'); ?>
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php esc_html_e('Product Sync Interval', 'gift-i-card'); ?>
            </th>
            <td>
                <select name="gicapi_products_sync_interval">
                    <?php foreach ($available_intervals as $interval => $display): ?>
                        <option value="<?php echo esc_attr($interval); ?>" <?php selected($products_sync_interval, $interval); ?>>
                            <?php echo esc_html($display); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php esc_html_e('How often the system should sync product statuses. Recommended: Every 5 minutes for regular updates.', 'gift-i-card'); ?>
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php esc_html_e('Manual Product Sync', 'gift-i-card'); ?>
            </th>
            <td>
                <button type="button" id="gicapi-manual-product-sync" class="button button-secondary">
                    <?php esc_html_e('Sync All Products Now', 'gift-i-card'); ?>
                </button>
                <p class="description">
                    <?php esc_html_e('Click this button to manually trigger synchronization of all mapped products. This is useful for testing or immediate updates.', 'gift-i-card'); ?>
                </p>
                <div id="gicapi-manual-product-sync-result" style="margin-top: 10px;"></div>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <hr>
            </td>
        </tr>

        <tr>
            <th colspan="2">
                <h3><?php esc_html_e('📦 Order Updating Cron Job', 'gift-i-card'); ?></h3>
            </th>
        </tr>

        <tr>
            <th scope="row">
                <?php esc_html_e('Order Updating through Cron Job', 'gift-i-card'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="gicapi_enable_cron_updates" value="yes" <?php checked($enable_cron_updates, 'yes'); ?>>
                    <?php esc_html_e('Enable Cron Job for Order Updating (not recommended)', 'gift-i-card'); ?>
                </label>
                <p class="description">
                    <?php esc_html_e('If your WooCommerce store is running on a real domain with HTTPS, orders are automatically updated via webhooks and you do not need to enable cron jobs. Cron jobs are only recommended for development/testing environments or when webhooks are not available.', 'gift-i-card'); ?>
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php esc_html_e('Update Interval', 'gift-i-card'); ?>
            </th>
            <td>
                <select name="gicapi_cron_interval">
                    <?php foreach ($available_intervals as $interval => $display): ?>
                        <option value="<?php echo esc_attr($interval); ?>" <?php selected($cron_interval, $interval); ?>>
                            <?php echo esc_html($display); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php esc_html_e('How often the system should check for order updates. Recommended: Every 5 minutes for fastest updates.', 'gift-i-card'); ?>
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php esc_html_e('Manual Update', 'gift-i-card'); ?>
            </th>
            <td>
                <button type="button" id="gicapi-manual-update" class="button button-secondary">
                    <?php esc_html_e('Update Pending/Processing Orders Now', 'gift-i-card'); ?>
                </button>
                <p class="description">
                    <?php esc_html_e('Click this button to manually trigger an update of all pending and processing orders. This is useful for testing or immediate updates.', 'gift-i-card'); ?>
                </p>
                <div id="gicapi-manual-update-result" style="margin-top: 10px;"></div>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <hr>
            </td>
        </tr>

        <tr>
            <th colspan="2">
                <h3><?php esc_html_e('🕒 Cron Job Status', 'gift-i-card'); ?></h3>
            </th>
        </tr>

        <tr>
            <th scope="row">
                <?php esc_html_e('Product Sync Status', 'gift-i-card'); ?>
            </th>
            <td>
                <?php if (!empty($product_sync_cron_status)): ?>
                    <div class="gicapi-cron-status">
                        <p>
                            <strong><?php esc_html_e('Enabled:', 'gift-i-card'); ?></strong>
                            <span class="<?php echo esc_attr($product_sync_cron_status['enabled'] ? 'status-enabled' : 'status-disabled'); ?>">
                                <?php echo esc_html($product_sync_cron_status['enabled'] ? __('Yes', 'gift-i-card') : __('No', 'gift-i-card')); ?>
                            </span>
                        </p>

                        <?php if ($product_sync_cron_status['enabled'] && $product_sync_cron_status['next_run']): ?>
                            <p>
                                <strong><?php esc_html_e('Next Execution:', 'gift-i-card'); ?></strong>
                                <?php echo esc_html($product_sync_cron_status['next_run']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($product_sync_cron_status['enabled']): ?>
                            <p>
                                <strong><?php esc_html_e('Interval:', 'gift-i-card'); ?></strong>
                                <?php echo esc_html($available_intervals[$product_sync_cron_status['interval']] ?? $product_sync_cron_status['interval']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="description"><?php esc_html_e('Product sync status information not available.', 'gift-i-card'); ?></p>
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php esc_html_e('Order Update Status', 'gift-i-card'); ?>
            </th>
            <td>
                <?php if (!empty($cron_status)): ?>
                    <div class="gicapi-cron-status">
                        <p>
                            <strong><?php esc_html_e('Enabled:', 'gift-i-card'); ?></strong>
                            <span class="<?php echo esc_attr($cron_status['enabled'] ? 'status-enabled' : 'status-disabled'); ?>">
                                <?php echo esc_html($cron_status['enabled'] ? __('Yes', 'gift-i-card') : __('No', 'gift-i-card')); ?>
                            </span>
                        </p>

                        <?php if ($cron_status['enabled'] && $cron_status['next_run']): ?>
                            <p>
                                <strong><?php esc_html_e('Next Run:', 'gift-i-card'); ?></strong>
                                <?php echo esc_html($cron_status['next_run']); ?>
                            </p>
                        <?php endif; ?>

                        <p>
                            <strong><?php esc_html_e('Interval:', 'gift-i-card'); ?></strong>
                            <?php echo esc_html($available_intervals[$cron_status['interval']] ?? $cron_status['interval']); ?>
                        </p>
                    </div>
                <?php else: ?>
                    <p class="description"><?php esc_html_e('Order update status information not available.', 'gift-i-card'); ?></p>
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php esc_html_e('Troubleshooting', 'gift-i-card'); ?>
            </th>
            <td>
                <button type="button" id="gicapi-reschedule-cron" class="button button-secondary">
                    <?php esc_html_e('Reschedule Cron Job', 'gift-i-card'); ?>
                </button>
                <button type="button" id="gicapi-check-repair-cron" class="button button-secondary">
                    <?php esc_html_e('Check & Repair Cron Job', 'gift-i-card'); ?>
                </button>
                <p class="description">
                    <?php esc_html_e('If the cron job is not running, click these buttons to troubleshoot. "Reschedule" will clear and recreate the cron job. "Check & Repair" will automatically detect and fix issues.', 'gift-i-card'); ?>
                </p>
                <div id="gicapi-debug-result" style="margin-top: 10px;"></div>
            </td>
        </tr>



        <tr>
            <td colspan="2">
                <hr>
            </td>
        </tr>

        <tr>
            <th colspan="2">
                <h3><?php esc_html_e('📋 How It Works', 'gift-i-card'); ?></h3>
            </th>
        </tr>

        <tr>
            <td colspan="2">
                <div class="gicapi-cron-info">
                    <h4><?php esc_html_e('🔄 Product Synchronization Cron Job', 'gift-i-card'); ?></h4>
                    <ol>
                        <li><?php esc_html_e('The product sync cron job runs automatically at the specified interval (recommended: every 5 minutes).', 'gift-i-card'); ?></li>
                        <li><?php esc_html_e('It finds all WooCommerce products that are mapped to Gift-i-Card variants.', 'gift-i-card'); ?></li>
                        <li><?php esc_html_e('For each mapped product, it calls the Gift-i-Card API to get the latest stock status.', 'gift-i-card'); ?></li>
                        <li><?php esc_html_e('It updates the WooCommerce product stock status based on the Gift-i-Card status and your configured mapping rules.', 'gift-i-card'); ?></li>
                        <li><?php esc_html_e('This ensures your product availability is always synchronized with Gift-i-Card inventory.', 'gift-i-card'); ?></li>
                    </ol>

                    <h4><?php esc_html_e('📦 Order Updating Cron Job', 'gift-i-card'); ?></h4>
                    <ol>
                        <li><?php esc_html_e('The order update cron job runs automatically at the specified interval (recommended: every 5 minutes).', 'gift-i-card'); ?></li>
                        <li><?php esc_html_e('It finds all WooCommerce orders that have Gift-i-Card orders in "pending" or "processing" status.', 'gift-i-card'); ?></li>
                        <li><?php esc_html_e('For each active order, it calls the Gift-i-Card API to get the latest status.', 'gift-i-card'); ?></li>
                        <li><?php esc_html_e('If the status has changed, it updates the order and adds a note.', 'gift-i-card'); ?></li>
                        <li><?php esc_html_e('If all Gift-i-Card orders in a WooCommerce order are completed, it can automatically complete the WooCommerce order (if enabled).', 'gift-i-card'); ?></li>
                        <li><?php esc_html_e('If any Gift-i-Card orders fail, it can automatically change the WooCommerce order status (if enabled).', 'gift-i-card'); ?></li>
                    </ol>

                    <div class="notice notice-info">
                        <p>
                            <strong><?php esc_html_e('Note:', 'gift-i-card'); ?></strong>
                            <?php esc_html_e('These features require WordPress cron to be working properly. If you\'re using a real cron job, make sure to disable WordPress cron and set up a real cron job to call wp-cron.php.', 'gift-i-card'); ?>
                        </p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<style>
    .gicapi-cron-status .status-enabled {
        color: #46b450;
        font-weight: bold;
    }

    .gicapi-cron-status .status-disabled {
        color: #dc3232;
        font-weight: bold;
    }

    .gicapi-cron-info ol {
        margin-left: 20px;
    }

    .gicapi-cron-info li {
        margin-bottom: 8px;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        $('#gicapi-manual-update').on('click', function() {
            var button = $(this);
            var resultDiv = $('#gicapi-manual-update-result');

            button.prop('disabled', true).text('<?php echo esc_js(__('Updating...', 'gift-i-card')); ?>');
            resultDiv.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gicapi_manual_update_orders',
                    nonce: '<?php echo esc_js(wp_create_nonce('gicapi_manual_update_orders')); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    } else {
                        resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                },
                error: function() {
                    resultDiv.html('<div class="notice notice-error"><p><?php echo esc_js(__('An error occurred while updating orders.', 'gift-i-card')); ?></p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('<?php echo esc_js(__('Update Pending/Processing Orders Now', 'gift-i-card')); ?>');
                }
            });
        });

        $('#gicapi-manual-product-sync').on('click', function() {
            var button = $(this);
            var resultDiv = $('#gicapi-manual-product-sync-result');

            button.prop('disabled', true).text('<?php echo esc_js(__('Syncing...', 'gift-i-card')); ?>');
            resultDiv.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gicapi_manual_sync_products',
                    nonce: '<?php echo esc_js(wp_create_nonce('gicapi_manual_sync_products')); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    } else {
                        resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                },
                error: function() {
                    resultDiv.html('<div class="notice notice-error"><p><?php echo esc_js(__('An error occurred while syncing products.', 'gift-i-card')); ?></p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('<?php echo esc_js(__('Sync All Products Now', 'gift-i-card')); ?>');
                }
            });
        });

        $('#gicapi-reschedule-cron').on('click', function() {
            var button = $(this);
            var resultDiv = $('#gicapi-debug-result');

            button.prop('disabled', true).text('<?php echo esc_js(__('Rescheduling...', 'gift-i-card')); ?>');
            resultDiv.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gicapi_reschedule_cron',
                    nonce: '<?php echo esc_js(wp_create_nonce('gicapi_reschedule_cron')); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                        // Reload page after 2 seconds to show updated status
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                },
                error: function() {
                    resultDiv.html('<div class="notice notice-error"><p><?php echo esc_js(__('An error occurred while rescheduling cron job.', 'gift-i-card')); ?></p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('<?php echo esc_js(__('Reschedule Cron Job', 'gift-i-card')); ?>');
                }
            });
        });

        $('#gicapi-check-repair-cron').on('click', function() {
            var button = $(this);
            var resultDiv = $('#gicapi-debug-result');

            button.prop('disabled', true).text('<?php echo esc_js(__('Checking...', 'gift-i-card')); ?>');
            resultDiv.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gicapi_check_repair_cron',
                    nonce: '<?php echo esc_js(wp_create_nonce('gicapi_check_repair_cron')); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                        // Reload page after 2 seconds to show updated status
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                },
                error: function() {
                    resultDiv.html('<div class="notice notice-error"><p><?php echo esc_js(__('An error occurred while checking cron job.', 'gift-i-card')); ?></p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('<?php echo esc_js(__('Check & Repair Cron Job', 'gift-i-card')); ?>');
                }
            });
        });
    });
</script>