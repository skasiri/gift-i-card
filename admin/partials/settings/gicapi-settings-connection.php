<?php
if (!defined('ABSPATH')) {
    exit;
}

$base_url = get_option('gicapi_base_url');
$consumer_key = get_option('gicapi_consumer_key');
$consumer_secret = get_option('gicapi_consumer_secret');
?>

<div id="connection" class="tab-content">
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="gicapi_base_url"><?php esc_html_e('Base URL', 'gift-i-card'); ?></label>
            </th>
            <td>
                <input type="url" name="gicapi_base_url" id="gicapi_base_url" value="<?php echo esc_attr($base_url); ?>" class="regular-text" required>
                <p class="description"><?php esc_html_e('The base URL of the gift-i-card service API', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_consumer_key"><?php esc_html_e('Consumer Key', 'gift-i-card'); ?></label>
            </th>
            <td>
                <input type="text" name="gicapi_consumer_key" id="gicapi_consumer_key" value="<?php echo esc_attr($consumer_key); ?>" class="regular-text" required>
                <p class="description"><?php esc_html_e('The consumer key for API authentication', 'gift-i-card'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="gicapi_consumer_secret"><?php esc_html_e('Consumer Secret', 'gift-i-card'); ?></label>
            </th>
            <td>
                <input type="password" name="gicapi_consumer_secret" id="gicapi_consumer_secret" value="<?php echo esc_attr($consumer_secret); ?>" class="regular-text" required>
                <p class="description"><?php esc_html_e('The consumer secret for API authentication', 'gift-i-card'); ?></p>
            </td>
        </tr>
    </table>

    <?php if (isset($base_url) && isset($consumer_key) && isset($consumer_secret)) : ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Refresh Token', 'gift-i-card'); ?></th>
                <td>
                    <button type="button" id="gicapi-force-refresh-token-button" class="button button-secondary">
                        <?php esc_html_e('Get New Token', 'gift-i-card'); ?>
                    </button>
                    <p class="description"><?php esc_html_e('Click this button to discard the current token and get a new one from the API.', 'gift-i-card'); ?></p>
                    <div id="gicapi-refresh-token-message" style="margin-top: 10px;"></div>
                </td>
            </tr>
        </table>
    <?php endif; ?>
</div>