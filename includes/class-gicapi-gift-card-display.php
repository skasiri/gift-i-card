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
        $gicapi_orders = $order->get_meta('_gicapi_orders', true);

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
        echo '<h4>' . esc_html__('Gift Card Information', 'gift-i-card') . '</h4>';

        if (empty($item_gift_orders)) {
            // No gift card order created yet for this mapped item
            echo '<div class="gicapi-gift-card-details">';
            echo '<p><strong>' . esc_html__('Status:', 'gift-i-card') . '</strong> <span class="gicapi-status gicapi-status-pending-create">' . esc_html__('Pending Order Creation', 'gift-i-card') . '</span></p>';
            echo '<p><strong>' . esc_html__('Mapped Variant SKU:', 'gift-i-card') . '</strong> ' . esc_html($variant_sku) . '</p>';

            // Add manual order creation button
            $order_id = $order->get_id();
            $failed_items = $order->get_meta('_gicapi_created_failed_items', true);
            if (!empty($failed_items)) {
                if (in_array($item_id, $failed_items)) {
                    $nonce = wp_create_nonce('gicapi_create_order_manually');
                    echo '<div class="gicapi-manual-order-actions">';
                    echo '<button type="button" class="button gicapi-create-order-manually" data-order-id="' . esc_attr($order_id) . '" data-item-id="' . esc_attr($item_id) . '" data-nonce="' . esc_attr($nonce) . '">';
                    echo esc_html__('Create Order Manually', 'gift-i-card');
                    echo '</button>';
                    echo '<span class="gicapi-loading" style="display: none;">' . esc_html__('Creating order...', 'gift-i-card') . '</span>';
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
                        $item_status = esc_html__('Pending', 'gift-i-card');
                        break;
                    case 'processing':
                        $item_status = esc_html__('Processing', 'gift-i-card');
                        break;
                    case 'completed':
                        $item_status = esc_html__('Completed', 'gift-i-card');
                        break;
                    case 'failed':
                        $item_status = esc_html__('Failed', 'gift-i-card');
                        break;
                    default:
                        $item_status = $item_status;
                        break;
                }

                $currency = $gic_order['currency'];
                switch (strtolower($currency)) {
                    case 'eur':
                        $currency = esc_html__('€', 'gift-i-card');
                        break;
                    case 'usd':
                        $currency = esc_html__('$', 'gift-i-card');
                        break;
                    case 'irt':
                        $currency = esc_html__('IRT', 'gift-i-card');
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
                echo '<p><strong>' . esc_html__('Gift-i-Card Order ID:', 'gift-i-card') . '</strong> ' . esc_html($gic_order['order_id']) . '</p>';
                echo '<p><strong>' . esc_html__('Status:', 'gift-i-card') . '</strong> <span class="gicapi-status gicapi-status-' . esc_attr($gic_order['status']) . '">' . esc_html($item_status) . '</span></p>';
                echo '<p><strong>' . esc_html__('Price:', 'gift-i-card') . '</strong> ' . esc_html($price) . ' ' . esc_html($currency) . '</p>';
                echo '<p><strong>' . esc_html__('Mapped Variant SKU:', 'gift-i-card') . '</strong> ' . esc_html($variant_sku) . '</p>';

                // Add confirm order button for pending status
                if (strtolower($gic_order['status']) === 'pending') {
                    $order_id = $order->get_id();
                    $failed_items = $order->get_meta('_gicapi_confirmed_failed_items', true);
                    if (!empty($failed_items)) {
                        if (in_array($item_id, $failed_items)) {
                            $nonce = wp_create_nonce('gicapi_confirm_order_manually');
                            echo '<div class="gicapi-manual-order-actions">';
                            echo '<button type="button" class="button gicapi-confirm-order-manually" data-order-id="' . esc_attr($order_id) . '" data-nonce="' . esc_attr($nonce) . '">';
                            echo esc_html__('Confirm Order Manually', 'gift-i-card');
                            echo '</button>';
                            echo '<span class="gicapi-loading" style="display: none;">' . esc_html__('Confirming order...', 'gift-i-card') . '</span>';
                            echo '</div>';
                        }
                    }
                }


                if (!empty($gic_order['expires_at'])) {
                    echo '<p><strong>' . esc_html__('Expires At:', 'gift-i-card') . '</strong> ' . esc_html($gic_order['expires_at']) . '</p>';
                }

                // Display redemption data if available
                if (isset($gic_order['redeem_data']) && is_array($gic_order['redeem_data']) && !empty($gic_order['redeem_data'])) {
                    // Dynamic columns logic
                    $columns = [
                        'license_key' => esc_html__('License Key', 'gift-i-card'),
                        'redeem_serial_number' => esc_html__('Serial Number', 'gift-i-card'),
                        'redeem_card_code' => esc_html__('Card Code', 'gift-i-card'),
                        'redeem_link' => esc_html__('Redeem Link', 'gift-i-card'),
                        'expiration_date' => esc_html__('Expires At', 'gift-i-card'),
                    ];
                    $active_columns = [];
                    foreach ($columns as $key => $label) {
                        foreach ($gic_order['redeem_data'] as $redeem_item) {
                            if (!empty($redeem_item[$key])) {
                                $active_columns[$key] = $label;
                                break;
                            }
                        }
                    }
                    if (!empty($active_columns)) {
                        echo '<div class="gicapi-redemption-details">';
                        echo '<h5>' . esc_html__('Redemption Details', 'gift-i-card') . '</h5>';
                        echo '<table class="gicapi-redemption-table">';
                        echo '<thead><tr>';
                        foreach ($active_columns as $label) {
                            echo '<th>' . esc_html($label) . '</th>';
                        }
                        echo '</tr></thead>';
                        echo '<tbody>';
                        foreach ($gic_order['redeem_data'] as $redeem_item) {
                            echo '<tr>';
                            foreach ($active_columns as $key => $label) {
                                echo '<td>';
                                if (in_array($key, ['license_key', 'redeem_serial_number', 'redeem_card_code'])) {
                                    $raw_value = $redeem_item[$key] ?? '';
                                    echo esc_html($raw_value);
                                    if (!empty($raw_value)) {
                                        echo ' <button type="button" class="gicapi-copy-btn" data-copy="' . esc_attr($raw_value) . '" style="background:transparent;border:none;padding:0;margin:0 0 0 4px;vertical-align:middle;cursor:pointer;" title="کپی">'
                                            . '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="none" style="display:inline;vertical-align:middle;"><rect x="6" y="6" width="9" height="12" rx="2" stroke="#888" stroke-width="1.5"/><rect x="3" y="2" width="9" height="12" rx="2" fill="#fff" stroke="#888" stroke-width="1.5"/></svg>'
                                            . '</button>';
                                    }
                                } elseif ($key === 'redeem_link' && !empty($redeem_item[$key])) {
                                    echo '<a href="' . esc_url($redeem_item[$key]) . '" target="_blank" class="button button-small gicapi-redeem-link">' . esc_html__('Redeem', 'gift-i-card') . '</a>';
                                } else {
                                    echo esc_html($redeem_item[$key] ?? '');
                                }
                                echo '</td>';
                            }
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                    }
                }


                // Add update status button for all statuses
                if (in_array(strtolower($gic_order['status']), array('pending', 'processing', 'completed', 'failed'))) {
                    $order_id = $order->get_id();
                    $nonce = wp_create_nonce('gicapi_update_status_manually');
                    echo '<div class="gicapi-manual-order-actions">';
                    echo '<button type="button" class="button gicapi-update-status-manually" data-order-id="' . esc_attr($order_id) . '" data-nonce="' . esc_attr($nonce) . '">';
                    echo esc_html__('Update Status', 'gift-i-card');
                    echo '</button>';
                    echo '<span class="gicapi-loading" style="display: none;">' . esc_html__('Updating status...', 'gift-i-card') . '</span>';
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
        echo '<h2>' . esc_html__('Gift Card Information', 'gift-i-card') . '</h2>';

        // Add action buttons
        echo '<div class="gicapi-summary-actions">';
        echo '<button class="gicapi-print-info button">' . esc_html__('Print Information', 'gift-i-card') . '</button>';
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
                    // Dynamic columns logic
                    $columns = [
                        'license_key' => esc_html__('License Key', 'gift-i-card'),
                        'redeem_serial_number' => esc_html__('Serial Number', 'gift-i-card'),
                        'redeem_card_code' => esc_html__('Card Code', 'gift-i-card'),
                        'redeem_link' => esc_html__('Redeem Link', 'gift-i-card'),
                        'expiration_date' => esc_html__('Expires At', 'gift-i-card'),
                    ];
                    $active_columns = [];
                    foreach ($columns as $key => $label) {
                        foreach ($gic_order['redeem_data'] as $redeem_item) {
                            if (!empty($redeem_item[$key])) {
                                $active_columns[$key] = $label;
                                break;
                            }
                        }
                    }
                    if (!empty($active_columns)) {
                        echo '<div class="gicapi-redemption-summary">';
                        echo '<h4>' . esc_html__('Redemption Codes', 'gift-i-card') . '</h4>';
                        echo '<table class="gicapi-summary-table">';
                        echo '<thead><tr>';
                        foreach ($active_columns as $label) {
                            echo '<th>' . esc_html($label) . '</th>';
                        }
                        echo '</tr></thead>';
                        echo '<tbody>';
                        foreach ($gic_order['redeem_data'] as $redeem_item) {
                            echo '<tr>';
                            foreach ($active_columns as $key => $label) {
                                echo '<td>';
                                if (in_array($key, ['license_key', 'redeem_serial_number', 'redeem_card_code'])) {
                                    $raw_value = $redeem_item[$key] ?? '';
                                    echo esc_html($raw_value);
                                    if (!empty($raw_value)) {
                                        echo ' <button type="button" class="gicapi-copy-btn" data-copy="' . esc_attr($raw_value) . '" style="background:transparent;border:none;padding:0;margin:0 0 0 4px;vertical-align:middle;cursor:pointer;" title="کپی">'
                                            . '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="none" style="display:inline;vertical-align:middle;"><rect x="6" y="6" width="9" height="12" rx="2" stroke="#888" stroke-width="1.5"/><rect x="3" y="2" width="9" height="12" rx="2" fill="#fff" stroke="#888" stroke-width="1.5"/></svg>'
                                            . '</button>';
                                    }
                                } elseif ($key === 'redeem_link' && !empty($redeem_item[$key])) {
                                    echo '<a href="' . esc_url($redeem_item[$key]) . '" target="_blank" class="button button-small gicapi-redeem-link">' . esc_html__('Click to Redeem', 'gift-i-card') . '</a>';
                                } else {
                                    echo esc_html($redeem_item[$key] ?? '');
                                }
                                echo '</td>';
                            }
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                    }
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

        // Dynamic columns logic
        $columns = [
            'variant' => esc_html__('Product', 'gift-i-card'),
            'license_key' => esc_html__('License Key', 'gift-i-card'),
            'redeem_serial_number' => esc_html__('Serial Number', 'gift-i-card'),
            'redeem_card_code' => esc_html__('Card Code', 'gift-i-card'),
            'redeem_link' => esc_html__('Redeem Link', 'gift-i-card'),
            'expiration_date' => esc_html__('Expires At', 'gift-i-card'),
        ];
        $active_columns = [];
        foreach ($columns as $key => $label) {
            foreach ($gicapi_orders as $gic_order) {
                if (isset($gic_order['redeem_data']) && is_array($gic_order['redeem_data'])) {
                    foreach ($gic_order['redeem_data'] as $redeem_item) {
                        if (!empty($redeem_item[$key])) {
                            $active_columns[$key] = $label;
                            break 2;
                        }
                    }
                }
            }
        }
        if (empty($active_columns)) {
            return;
        }
?>
        <div class="gicapi-redeem-data">
            <h2><?php esc_html_e('Gift Card Redemption Details', 'gift-i-card'); ?></h2>
            <table class="shop_table">
                <thead>
                    <tr>
                        <?php foreach ($active_columns as $label): ?>
                            <th><?php echo esc_html($label); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gicapi_orders as $gic_order): ?>
                        <?php if (isset($gic_order['redeem_data']) && is_array($gic_order['redeem_data'])): ?>
                            <?php foreach ($gic_order['redeem_data'] as $redeem_item): ?>
                                <tr>
                                    <?php foreach ($active_columns as $key => $label): ?>
                                        <td>
                                            <?php
                                            if (in_array($key, ['license_key', 'redeem_serial_number', 'redeem_card_code'])) {
                                                $raw_value = $redeem_item[$key] ?? '';
                                                echo esc_html($raw_value);
                                                if (!empty($raw_value)) {
                                                    echo ' <button type="button" class="gicapi-copy-btn" data-copy="' . esc_attr($raw_value) . '" style="background:transparent;border:none;padding:0;margin:0 0 0 4px;vertical-align:middle;cursor:pointer;" title="کپی">'
                                                        . '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="none" style="display:inline;vertical-align:middle;"><rect x="6" y="6" width="9" height="12" rx="2" stroke="#888" stroke-width="1.5"/><rect x="3" y="2" width="9" height="12" rx="2" fill="#fff" stroke="#888" stroke-width="1.5"/></svg>'
                                                        . '</button>';
                                                }
                                            } elseif ($key === 'redeem_link' && !empty($redeem_item[$key])) {
                                                echo '<a href="' . esc_url($redeem_item[$key]) . '" target="_blank" class="button button-small">' . esc_html__('Redeem', 'gift-i-card') . '</a>';
                                            } else {
                                                echo esc_html($redeem_item[$key] ?? '');
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.gicapi-copy-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var text = this.getAttribute('data-copy');
                        if (navigator.clipboard) {
                            navigator.clipboard.writeText(text);
                        } else {
                            var textarea = document.createElement('textarea');
                            textarea.value = text;
                            document.body.appendChild(textarea);
                            textarea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textarea);
                        }
                        var old = this.innerHTML;
                        this.innerHTML = '<span style="font-size:11px;">✔</span>';
                        setTimeout(() => {
                            this.innerHTML = old;
                        }, 1200);
                    });
                });
            });
        </script>
    <?php
    }

    /**
     * Display redeem data in emails (inline styles, no copy button)
     *
     * @param WC_Order $order The order object
     */
    public function display_redeem_data_email($order)
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

        // Dynamic columns logic
        $columns = [
            'variant' => esc_html__('Product', 'gift-i-card'),
            'license_key' => esc_html__('License Key', 'gift-i-card'),
            'redeem_serial_number' => esc_html__('Serial Number', 'gift-i-card'),
            'redeem_card_code' => esc_html__('Card Code', 'gift-i-card'),
            'redeem_link' => esc_html__('Redeem Link', 'gift-i-card'),
            'expiration_date' => esc_html__('Expires At', 'gift-i-card'),
        ];
        $active_columns = [];
        foreach ($columns as $key => $label) {
            foreach ($gicapi_orders as $gic_order) {
                if (isset($gic_order['redeem_data']) && is_array($gic_order['redeem_data'])) {
                    foreach ($gic_order['redeem_data'] as $redeem_item) {
                        if (!empty($redeem_item[$key])) {
                            $active_columns[$key] = $label;
                            break 2;
                        }
                    }
                }
            }
        }
        if (empty($active_columns)) {
            return;
        }
    ?>
        <div style="margin: 24px 0;">
            <h2 style="font-size: 18px; margin-bottom: 12px; color: #333; font-family: Arial, sans-serif;">
                <?php esc_html_e('Gift Card Redemption Details', 'gift-i-card'); ?>
            </h2>
            <table style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px; background: #fff;">
                <thead>
                    <tr>
                        <?php foreach ($active_columns as $label): ?>
                            <th style="border: 1px solid #ddd; background: #f7f7f7; padding: 8px; color: #222; text-align: left;">
                                <?php echo esc_html($label); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gicapi_orders as $gic_order): ?>
                        <?php if (isset($gic_order['redeem_data']) && is_array($gic_order['redeem_data'])): ?>
                            <?php foreach ($gic_order['redeem_data'] as $redeem_item): ?>
                                <tr>
                                    <?php foreach ($active_columns as $key => $label): ?>
                                        <td style="border: 1px solid #ddd; padding: 8px; color: #333; background: #fafafa;">
                                            <?php
                                            if ($key === 'redeem_link' && !empty($redeem_item[$key])) {
                                                echo '<a href="' . esc_url($redeem_item[$key]) . '" style="color: #21759b; text-decoration: underline;" target="_blank">' . esc_html__('Redeem', 'gift-i-card') . '</a>';
                                            } else {
                                                echo esc_html($redeem_item[$key] ?? '');
                                            }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
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
