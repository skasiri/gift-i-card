<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gift Card Display Class
 * Handles the display of gift card information in WooCommerce orders
 */
class GICAPI_Gift_Card_Display
{
    /**
     * Display gift card information for a specific order item
     * 
     * @param int $item_id The order item ID
     * @param WC_Order_Item $item The order item object
     * @param WC_Product $product The product object
     */
    public function display_gift_card_info_for_item($item_id, $item, $product)
    {
        // Get the order from the item
        $order = $item->get_order();
        if (!$order) {
            return;
        }

        // Check if this item is mapped to a gift card variant
        $gicapi_order = GICAPI_Order::get_instance();
        $variant_sku = $gicapi_order->get_mapped_variant_sku($item->get_product_id(), $item->get_variation_id());

        if (!$variant_sku) {
            // Item is not mapped, don't display anything
            return;
        }

        // Get gift card orders from order meta
        $gicapi_orders = get_post_meta($order->get_id(), '_gicapi_orders', true);

        // Find gift card orders for this specific item
        $item_gift_orders = array();
        if (!empty($gicapi_orders) && is_array($gicapi_orders)) {
            foreach ($gicapi_orders as $gic_order) {
                if (isset($gic_order['item_id']) && $gic_order['item_id'] == $item_id) {
                    $item_gift_orders[] = $gic_order;
                }
            }
        }

        // Display gift card information for this item
        echo '<div class="gicapi-item-gift-card-info">';
        echo '<h4>' . __('Gift Card Information', 'gift-i-card') . '</h4>';

        if (empty($item_gift_orders)) {
            // No gift card order created yet for this mapped item
            echo '<div class="gicapi-gift-card-details">';
            echo '<p><strong>' . __('Status:', 'gift-i-card') . '</strong> <span class="gicapi-status gicapi-status-pending-create">' . __('Pending Order Creation', 'gift-i-card') . '</span></p>';
            echo '<p><strong>' . __('Mapped Variant SKU:', 'gift-i-card') . '</strong> ' . esc_html($variant_sku) . '</p>';

            // Add manual order creation button
            $order_id = $order->get_id();
            $failed_items = get_post_meta($order_id, '_gicapi_created_failed_items', true);
            if (!empty($failed_items)) {
                if (in_array($item_id, $failed_items)) {
                    $nonce = wp_create_nonce('gicapi_create_order_manually');
                    echo '<div class="gicapi-manual-order-actions">';
                    echo '<button type="button" class="button gicapi-create-order-manually" data-order-id="' . esc_attr($order_id) . '" data-item-id="' . esc_attr($item_id) . '" data-nonce="' . esc_attr($nonce) . '">';
                    echo __('Create Order Manually', 'gift-i-card');
                    echo '</button>';
                    echo '<span class="gicapi-loading" style="display: none;">' . __('Creating order...', 'gift-i-card') . '</span>';
                    echo '</div>';
                }
            }

            echo '</div>';
        } else {
            // Display existing gift card orders
            foreach ($item_gift_orders as $gic_order) {
                $item_status = $gic_order['status'];
                switch (strtolower($item_status)) {
                    case 'pending':
                        $item_status = __('Pending', 'gift-i-card');
                        break;
                    case 'processing':
                        $item_status = __('Processing', 'gift-i-card');
                        break;
                    case 'completed':
                        $item_status = __('Completed', 'gift-i-card');
                        break;
                    case 'failed':
                        $item_status = __('Failed', 'gift-i-card');
                        break;
                    default:
                        $item_status = $item_status;
                        break;
                }

                $currency = $gic_order['currency'];
                switch (strtolower($currency)) {
                    case 'eur':
                        $currency = __('€', 'gift-i-card');
                        break;
                    case 'usd':
                        $currency = __('$', 'gift-i-card');
                        break;
                    case 'irt':
                        $currency = __('IRT', 'gift-i-card');
                        break;
                    default:
                        $currency = $currency;
                        break;
                }

                $price = $gic_order['price'];
                $decimals = get_option('woocommerce_price_num_decimals', 2);
                $decimal_separator = get_option('woocommerce_price_decimal_sep');
                $thousand_separator = get_option('woocommerce_price_thousand_sep');
                $price = number_format($price, $decimals, $decimal_separator, $thousand_separator);

                echo '<div class="gicapi-gift-card-details">';
                echo '<p><strong>' . __('Gift-i-Card Order ID:', 'gift-i-card') . '</strong> ' . esc_html($gic_order['order_id']) . '</p>';
                echo '<p><strong>' . __('Status:', 'gift-i-card') . '</strong> <span class="gicapi-status gicapi-status-' . esc_attr($gic_order['status']) . '">' . esc_html($item_status) . '</span></p>';
                echo '<p><strong>' . __('Price:', 'gift-i-card') . '</strong> ' . esc_html($price) . ' ' . esc_html($currency) . '</p>';
                echo '<p><strong>' . __('Mapped Variant SKU:', 'gift-i-card') . '</strong> ' . esc_html($variant_sku) . '</p>';

                // Add confirm order button for pending status
                if (strtolower($gic_order['status']) === 'pending') {
                    $order_id = $order->get_id();
                    $failed_items = get_post_meta($order_id, '_gicapi_confirmed_failed_items', true);
                    if (!empty($failed_items)) {
                        if (in_array($item_id, $failed_items)) {
                            $nonce = wp_create_nonce('gicapi_confirm_order_manually');
                            echo '<div class="gicapi-manual-order-actions">';
                            echo '<button type="button" class="button gicapi-confirm-order-manually" data-order-id="' . esc_attr($order_id) . '" data-nonce="' . esc_attr($nonce) . '">';
                            echo __('Confirm Order Manually', 'gift-i-card');
                            echo '</button>';
                            echo '<span class="gicapi-loading" style="display: none;">' . __('Confirming order...', 'gift-i-card') . '</span>';
                            echo '</div>';
                        }
                    }
                }


                if (!empty($gic_order['expires_at'])) {
                    echo '<p><strong>' . __('Expires At:', 'gift-i-card') . '</strong> ' . esc_html($gic_order['expires_at']) . '</p>';
                }

                // Display redemption data if available
                if (isset($gic_order['redeem_data']) && is_array($gic_order['redeem_data']) && !empty($gic_order['redeem_data'])) {
                    echo '<div class="gicapi-redemption-details">';
                    echo '<h5>' . __('Redemption Details', 'gift-i-card') . '</h5>';
                    echo '<table class="gicapi-redemption-table">';
                    echo '<thead><tr>';
                    echo '<th>' . __('License Key', 'gift-i-card') . '</th>';
                    echo '<th>' . __('Serial Number', 'gift-i-card') . '</th>';
                    echo '<th>' . __('Card Code', 'gift-i-card') . '</th>';
                    echo '<th>' . __('Redeem Link', 'gift-i-card') . '</th>';
                    echo '<th>' . __('Expires At', 'gift-i-card') . '</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';

                    foreach ($gic_order['redeem_data'] as $redeem_item) {
                        echo '<tr>';
                        echo '<td>' . esc_html($redeem_item['license_key'] ?? '') . '</td>';
                        echo '<td>' . esc_html($redeem_item['redeem_serial_number'] ?? '') . '</td>';
                        echo '<td>' . esc_html($redeem_item['redeem_card_code'] ?? '') . '</td>';
                        echo '<td>';
                        if (!empty($redeem_item['redeem_link'])) {
                            echo '<a href="' . esc_url($redeem_item['redeem_link']) . '" target="_blank" class="button button-small gicapi-redeem-link">' . __('Redeem', 'gift-i-card') . '</a>';
                        }
                        echo '</td>';
                        echo '<td>' . esc_html($redeem_item['expiration_date'] ?? '') . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                    echo '</div>';
                }


                // Add update status button for all statuses
                if (in_array(strtolower($gic_order['status']), array('pending', 'processing', 'completed', 'failed'))) {
                    $order_id = $order->get_id();
                    $nonce = wp_create_nonce('gicapi_update_status_manually');
                    echo '<div class="gicapi-manual-order-actions">';
                    echo '<button type="button" class="button gicapi-update-status-manually" data-order-id="' . esc_attr($order_id) . '" data-nonce="' . esc_attr($nonce) . '">';
                    echo __('Update Status', 'gift-i-card');
                    echo '</button>';
                    echo '<span class="gicapi-loading" style="display: none;">' . __('Updating status...', 'gift-i-card') . '</span>';
                    echo '</div>';
                }

                echo '</div>';
            }
        }

        echo '</div>';
    }

    /**
     * Display gift card summary for the entire order
     * 
     * @param WC_Order $order The order object
     */
    public function display_gift_card_summary($order)
    {
        // Get gift card orders from order meta
        $gicapi_orders = get_post_meta($order->get_id(), '_gicapi_orders', true);
        if (empty($gicapi_orders) || !is_array($gicapi_orders)) {
            return;
        }

        // Check if there are any gift card orders with redemption data
        $has_redemption_data = false;
        foreach ($gicapi_orders as $gic_order) {
            if (isset($gic_order['redeem_data']) && is_array($gic_order['redeem_data']) && !empty($gic_order['redeem_data'])) {
                $has_redemption_data = true;
                break;
            }
        }

        if (!$has_redemption_data) {
            return;
        }

        echo '<div class="gicapi-gift-card-summary">';
        echo '<h2>' . __('Gift Card Information', 'gift-i-card') . '</h2>';

        // Add action buttons
        echo '<div class="gicapi-summary-actions">';
        echo '<button class="gicapi-print-info button">' . __('Print Information', 'gift-i-card') . '</button>';
        echo '</div>';

        // Group gift card orders by item
        $item_groups = array();
        foreach ($gicapi_orders as $gic_order) {
            if (isset($gic_order['item_id'])) {
                $item_id = $gic_order['item_id'];
                if (!isset($item_groups[$item_id])) {
                    $item_groups[$item_id] = array();
                }
                $item_groups[$item_id][] = $gic_order;
            }
        }

        foreach ($item_groups as $item_id => $item_orders) {
            // Get the order item
            $item = $order->get_item($item_id);
            if (!$item) {
                continue;
            }

            echo '<div class="gicapi-item-summary">';
            echo '<h3>' . esc_html($item->get_name()) . '</h3>';

            foreach ($item_orders as $gic_order) {
                if (isset($gic_order['redeem_data']) && is_array($gic_order['redeem_data']) && !empty($gic_order['redeem_data'])) {
                    echo '<div class="gicapi-order-summary">';

                    echo '<div class="gicapi-redemption-summary">';
                    echo '<h4>' . __('Redemption Codes', 'gift-i-card') . '</h4>';
                    echo '<table class="gicapi-summary-table">';
                    echo '<thead><tr>';
                    echo '<th>' . __('License Key', 'gift-i-card') . '</th>';
                    echo '<th>' . __('Serial Number', 'gift-i-card') . '</th>';
                    echo '<th>' . __('Card Code', 'gift-i-card') . '</th>';
                    echo '<th>' . __('Redeem Link', 'gift-i-card') . '</th>';
                    echo '<th>' . __('Expires At', 'gift-i-card') . '</th>';
                    echo '</tr></thead>';
                    echo '<tbody>';

                    foreach ($gic_order['redeem_data'] as $redeem_item) {
                        echo '<tr>';
                        echo '<td>' . esc_html($redeem_item['license_key'] ?? '') . '</td>';
                        echo '<td>' . esc_html($redeem_item['redeem_serial_number'] ?? '') . '</td>';
                        echo '<td>' . esc_html($redeem_item['redeem_card_code'] ?? '') . '</td>';
                        echo '<td>';
                        if (!empty($redeem_item['redeem_link'])) {
                            echo '<a href="' . esc_url($redeem_item['redeem_link']) . '" target="_blank" class="button button-small gicapi-redeem-link">' . __('Click to Redeem', 'gift-i-card') . '</a>';
                        }
                        echo '</td>';
                        echo '<td>' . esc_html($redeem_item['expiration_date'] ?? '') . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table>';
                    echo '</div>';
                    echo '</div>';
                }
            }

            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Display redeem data in emails and order details
     * 
     * @param WC_Order $order The order object
     */
    public function display_redeem_data($order)
    {
        $gicapi_orders = get_post_meta($order->get_id(), '_gicapi_orders', true);
        if (empty($gicapi_orders) || !is_array($gicapi_orders)) {
            return;
        }

        $has_redeem_data = false;
        foreach ($gicapi_orders as $gic_order) {
            if (isset($gic_order['redeem_data']) && !empty($gic_order['redeem_data'])) {
                $has_redeem_data = true;
                break;
            }
        }

        if (!$has_redeem_data) {
            return;
        }

?>
        <div class="gicapi-redeem-data">
            <h2><?php _e('Gift Card Redemption Details', 'gift-i-card'); ?></h2>
            <table class="shop_table">
                <thead>
                    <tr>
                        <th><?php _e('Product', 'gift-i-card'); ?></th>
                        <th><?php _e('License Key', 'gift-i-card'); ?></th>
                        <th><?php _e('Serial Number', 'gift-i-card'); ?></th>
                        <th><?php _e('Card Code', 'gift-i-card'); ?></th>
                        <th><?php _e('Redeem Link', 'gift-i-card'); ?></th>
                        <th><?php _e('Expires At', 'gift-i-card'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gicapi_orders as $gic_order): ?>
                        <?php if (isset($gic_order['redeem_data']) && is_array($gic_order['redeem_data'])): ?>
                            <?php foreach ($gic_order['redeem_data'] as $redeem_item): ?>
                                <tr>
                                    <td><?php echo esc_html($redeem_item['variant'] ?? ''); ?></td>
                                    <td><?php echo esc_html($redeem_item['license_key'] ?? ''); ?></td>
                                    <td><?php echo esc_html($redeem_item['redeem_serial_number'] ?? ''); ?></td>
                                    <td><?php echo esc_html($redeem_item['redeem_card_code'] ?? ''); ?></td>
                                    <td>
                                        <?php if (!empty($redeem_item['redeem_link'])): ?>
                                            <a href="<?php echo esc_url($redeem_item['redeem_link']); ?>" target="_blank" class="button button-small">
                                                <?php _e('Redeem', 'gift-i-card'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($redeem_item['expiration_date'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
<?php
    }
}
