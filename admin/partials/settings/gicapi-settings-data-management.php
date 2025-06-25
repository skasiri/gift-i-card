<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

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