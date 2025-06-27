<?php
if (!defined('ABSPATH')) {
    exit;
}

// Cron job settings
$enable_cron_updates = get_option('gicapi_enable_cron_updates', 'yes');
$cron_interval = get_option('gicapi_cron_interval', 'gicapi_five_minutes');

// Get cron status
$cron_status = array();
if (class_exists('GICAPI_Cron')) {
    $cron = GICAPI_Cron::get_instance();
    $cron_status = $cron->get_cron_status();
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
            <th scope="row">
                <?php _e('Enable Automatic Order Updates', 'gift-i-card'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="gicapi_enable_cron_updates" value="yes" <?php checked($enable_cron_updates, 'yes'); ?>>
                    <?php _e('Enable automatic updates of pending and processing orders', 'gift-i-card'); ?>
                </label>
                <p class="description">
                    <?php _e('When enabled, the system will automatically check and update the status of Gift-i-Card orders that are in pending or processing status.', 'gift-i-card'); ?>
                </p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php _e('Update Interval', 'gift-i-card'); ?>
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
                    <?php _e('How often the system should check for order updates. Recommended: Every 5 minutes for fastest updates.', 'gift-i-card'); ?>
                </p>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <hr>
            </td>
        </tr>

        <tr>
            <th colspan="2">
                <h3><?php _e('🕒 Cron Job Status', 'gift-i-card'); ?></h3>
            </th>
        </tr>

        <tr>
            <th scope="row">
                <?php _e('Status', 'gift-i-card'); ?>
            </th>
            <td>
                <?php if (!empty($cron_status)): ?>
                    <div class="gicapi-cron-status">
                        <p>
                            <strong><?php _e('Enabled:', 'gift-i-card'); ?></strong>
                            <span class="<?php echo $cron_status['enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                                <?php echo $cron_status['enabled'] ? __('Yes', 'gift-i-card') : __('No', 'gift-i-card'); ?>
                            </span>
                        </p>

                        <?php if ($cron_status['enabled'] && $cron_status['next_run']): ?>
                            <p>
                                <strong><?php _e('Next Run:', 'gift-i-card'); ?></strong>
                                <?php echo esc_html($cron_status['next_run']); ?>
                            </p>
                        <?php endif; ?>

                        <p>
                            <strong><?php _e('Interval:', 'gift-i-card'); ?></strong>
                            <?php echo esc_html($available_intervals[$cron_status['interval']] ?? $cron_status['interval']); ?>
                        </p>
                    </div>
                <?php else: ?>
                    <p class="description"><?php _e('Cron status information not available.', 'gift-i-card'); ?></p>
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php _e('Manual Update', 'gift-i-card'); ?>
            </th>
            <td>
                <button type="button" id="gicapi-manual-update" class="button button-secondary">
                    <?php _e('Update Pending/Processing Orders Now', 'gift-i-card'); ?>
                </button>
                <p class="description">
                    <?php _e('Click this button to manually trigger an update of all pending and processing orders. This is useful for testing or immediate updates.', 'gift-i-card'); ?>
                </p>
                <div id="gicapi-manual-update-result" style="margin-top: 10px;"></div>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <?php _e('Troubleshooting', 'gift-i-card'); ?>
            </th>
            <td>
                <button type="button" id="gicapi-reschedule-cron" class="button button-secondary">
                    <?php _e('Reschedule Cron Job', 'gift-i-card'); ?>
                </button>
                <p class="description">
                    <?php _e('If the cron job is not running, click this button to clear and recreate the cron job.', 'gift-i-card'); ?>
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
                <h3><?php _e('📋 How It Works', 'gift-i-card'); ?></h3>
            </th>
        </tr>

        <tr>
            <td colspan="2">
                <div class="gicapi-cron-info">
                    <ol>
                        <li><?php _e('The cron job runs automatically at the specified interval (recommended: every 5 minutes).', 'gift-i-card'); ?></li>
                        <li><?php _e('It finds all WooCommerce orders that have Gift-i-Card orders in "pending" or "processing" status.', 'gift-i-card'); ?></li>
                        <li><?php _e('For each active order, it calls the Gift-i-Card API to get the latest status.', 'gift-i-card'); ?></li>
                        <li><?php _e('If the status has changed, it updates the order and adds a note.', 'gift-i-card'); ?></li>
                        <li><?php _e('If all Gift-i-Card orders in a WooCommerce order are completed, it can automatically complete the WooCommerce order (if enabled).', 'gift-i-card'); ?></li>
                        <li><?php _e('If any Gift-i-Card orders fail, it can automatically change the WooCommerce order status (if enabled).', 'gift-i-card'); ?></li>
                    </ol>

                    <div class="notice notice-info">
                        <p>
                            <strong><?php _e('Note:', 'gift-i-card'); ?></strong>
                            <?php _e('This feature requires WordPress cron to be working properly. If you\'re using a real cron job, make sure to disable WordPress cron and set up a real cron job to call wp-cron.php.', 'gift-i-card'); ?>
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

            button.prop('disabled', true).text('<?php _e('Updating...', 'gift-i-card'); ?>');
            resultDiv.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gicapi_manual_update_orders',
                    nonce: '<?php echo wp_create_nonce('gicapi_manual_update_orders'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    } else {
                        resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    }
                },
                error: function() {
                    resultDiv.html('<div class="notice notice-error"><p><?php _e('An error occurred while updating orders.', 'gift-i-card'); ?></p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('<?php _e('Update Pending/Processing Orders Now', 'gift-i-card'); ?>');
                }
            });
        });

        $('#gicapi-reschedule-cron').on('click', function() {
            var button = $(this);
            var resultDiv = $('#gicapi-debug-result');

            button.prop('disabled', true).text('<?php _e('Rescheduling...', 'gift-i-card'); ?>');
            resultDiv.html('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gicapi_reschedule_cron',
                    nonce: '<?php echo wp_create_nonce('gicapi_reschedule_cron'); ?>'
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
                    resultDiv.html('<div class="notice notice-error"><p><?php _e('An error occurred while rescheduling cron job.', 'gift-i-card'); ?></p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('<?php _e('Reschedule Cron Job', 'gift-i-card'); ?>');
                }
            });
        });
    });
</script>