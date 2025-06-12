<?php

/**
 * Displays the variants table for a selected product.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$category_sku = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$product_sku = isset($_GET['product']) ? sanitize_text_field($_GET['product']) : '';

// Get product info from API
$api = GICAPI_API::get_instance();
$response = $api->get_products($category_sku, 1, 999);
$product_name = __('Unknown Product', 'gift-i-card');

if (!is_wp_error($response) && isset($response['products'])) {
    foreach ($response['products'] as $prod) {
        if ($prod['sku'] === $product_sku) {
            $product_name = $prod['name'];
            break;
        }
    }
}

// Get category info from API
$categories = $api->get_categories();
$category_name = __('Unknown Category', 'gift-i-card');

if (!is_wp_error($categories)) {
    foreach ($categories as $cat) {
        if ($cat['sku'] === $category_sku) {
            $category_name = $cat['name'];
            break;
        }
    }
}

$products_page_url = add_query_arg('category', $category_sku, menu_page_url($plugin_name . '-products', false));
$categories_page_url = menu_page_url($plugin_name . '-products', false);

// Get variants from API
$variants_response = $api->get_variants($product_sku);
$variants = array();

if (!is_wp_error($variants_response)) {
    if (isset($variants_response['variants']) && is_array($variants_response['variants'])) {
        $variants = $variants_response['variants'];
    } elseif (is_array($variants_response)) {
        $variants = $variants_response;
    }
}

if (empty($variants)) {
    echo '<div class="wrap gicapi-admin-page">';
    echo '<h1>' . esc_html__('No variants found for this product.', 'gift-i-card') . '</h1>';
    echo '</div>';
    return;
}
?>

<div class="wrap gicapi-admin-page">
    <h1>
        <a href="<?php echo esc_url($categories_page_url); ?>"><?php echo esc_html(get_admin_page_title()); ?></a> &raquo;
        <a href="<?php echo esc_url($products_page_url); ?>"><?php echo esc_html($category_name); ?></a> &raquo;
        <?php echo esc_html($product_name); ?> - <?php _e('Variants', 'gift-i-card'); ?>
    </h1>

    <div class="gicapi-toolbar">
        <!-- Add search form here later -->
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Name', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('SKU', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Price', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Value', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Max Order', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Stock Status', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Mapped WC Product', 'gift-i-card'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php foreach ($variants as $variant) :
                $variant_name = $variant['name'];
                $variant_sku = $variant['sku'];
                $variant_price = isset($variant['price']) ? $variant['price'] : '';
                $variant_value = isset($variant['value']) ? $variant['value'] : '';
                $variant_max_order = isset($variant['max_order']) ? $variant['max_order'] : 0;
                $variant_stock_status = isset($variant['stock_status']) ? $variant['stock_status'] : '';
                $mapped_product_id = get_post_meta($variant_sku, '_gicapi_mapped_wc_product_id', true);
                $mapped_product = $mapped_product_id ? wc_get_product($mapped_product_id) : null;
            ?>
                <tr>
                    <td class="column-title column-primary">
                        <strong><?php echo esc_html($variant_name); ?></strong>
                    </td>
                    <td class="column-sku"><?php echo esc_html($variant_sku); ?></td>
                    <td class="column-price"><?php echo esc_html($variant_price); ?></td>
                    <td class="column-value"><?php echo esc_html($variant_value); ?></td>
                    <td class="column-max-order"><?php echo esc_html($variant_max_order); ?></td>
                    <td class="column-stock-status"><?php echo esc_html($variant_stock_status); ?></td>
                    <td class="column-mapped-product">
                        <?php if ($mapped_product) : ?>
                            <a href="<?php echo esc_url(get_edit_post_link($mapped_product_id)); ?>" target="_blank">
                                <?php echo esc_html($mapped_product->get_name()); ?>
                            </a>
                        <?php else : ?>
                            <button type="button" class="button map-variant" data-variant-id="<?php echo esc_attr($variant_sku); ?>">
                                <?php _e('Map to WooCommerce', 'gift-i-card'); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Name', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('SKU', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Price', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Value', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Max Order', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Stock Status', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Mapped WC Product', 'gift-i-card'); ?></th>
            </tr>
        </tfoot>
    </table>
    <!-- Add pagination controls here later -->

    <!-- Modal for mapping -->
    <div id="gicapi-mapping-modal" style="display:none;">
        <div id="gicapi-mapping-modal-content">
            <h2><?php _e('Map Gift-i-Card Variant to WooCommerce Product', 'gift-i-card'); ?></h2>
            <p><?php _e('Mapping Variant:', 'gift-i-card'); ?> <strong id="modal-variant-name"></strong></p>
            <input type="hidden" id="modal-variant-id">
            <div>
                <label for="wc-product-search"><?php _e('Search WooCommerce Products:', 'gift-i-card'); ?></label>
                <select id="wc-product-search" style="width: 100%;" data-placeholder="<?php _e('Search for a product...', 'gift-i-card'); ?>"></select>
            </div>
            <div style="margin-top: 15px;">
                <button id="save-mapping" class="button button-primary"><?php _e('Save Mapping', 'gift-i-card'); ?></button>
                <button id="unmap-product" class="button button-secondary" style="display: none;"><?php _e('Unmap Product', 'gift-i-card'); ?></button>
                <button id="close-modal" class="button button-secondary"><?php _e('Cancel', 'gift-i-card'); ?></button>
                <span class="spinner"></span>
            </div>
        </div>
    </div>
    <div id="gicapi-mapping-modal-overlay" style="display:none;"></div>

</div>

<div id="map-variant-dialog" style="display: none;">
    <form id="map-variant-form">
        <input type="hidden" id="variant-id" value="">
        <p>
            <label for="wc-product"><?php _e('Select WooCommerce Product:', 'gift-i-card'); ?></label>
            <select id="wc-product" style="width: 100%;">
                <option value=""><?php _e('Select a product...', 'gift-i-card'); ?></option>
                <?php
                $products = wc_get_products(array('limit' => -1));
                foreach ($products as $product) {
                    echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . '</option>';
                }
                ?>
            </select>
        </p>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Map variant
        $('.map-variant').on('click', function() {
            var $button = $(this);
            var variantId = $button.data('variant-id');

            $('#variant-id').val(variantId);
            $('#map-variant-dialog').dialog({
                title: '<?php _e('Map to WooCommerce', 'gift-i-card'); ?>',
                modal: true,
                width: 500,
                buttons: {
                    '<?php _e('Map', 'gift-i-card'); ?>': function() {
                        var $dialog = $(this);
                        var $form = $('#map-variant-form');
                        var $select = $('#wc-product');

                        if (!$select.val()) {
                            alert('<?php _e('Please select a product', 'gift-i-card'); ?>');
                            return;
                        }

                        $.post(ajaxurl, {
                            action: 'gicapi_map_variant',
                            nonce: '<?php echo wp_create_nonce('gicapi_map_variant'); ?>',
                            wc_product_id: $select.val(),
                            gic_variant_id: $('#variant-id').val()
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert(response.data);
                            }
                        });

                        $dialog.dialog('close');
                    },
                    '<?php _e('Cancel', 'gift-i-card'); ?>': function() {
                        $(this).dialog('close');
                    }
                }
            });
        });
    });
</script>