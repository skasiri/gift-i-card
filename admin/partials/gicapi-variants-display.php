<?php

/**
 * Displays the variants table for a selected product.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$category_id = isset($_GET['category']) ? absint($_GET['category']) : 0;
$product_id = isset($_GET['product']) ? absint($_GET['product']) : 0;

$product = $product_id ? get_post($product_id) : null;
$product_name = $product ? $product->post_title : __('Unknown Product', 'gift-i-card');

$category = $category_id ? get_post($category_id) : null;
$category_name = $category ? $category->post_title : __('Unknown Category', 'gift-i-card');

$products_page_url = add_query_arg('category', $category_id, menu_page_url($plugin_name . '-products', false));
$categories_page_url = menu_page_url($plugin_name . '-products', false);

?>
<div class="wrap gicapi-admin-page">
    <h1>
        <a href="<?php echo esc_url($categories_page_url); ?>"><?php echo esc_html(get_admin_page_title()); ?></a> &raquo;
        <a href="<?php echo esc_url($products_page_url); ?>"><?php echo esc_html($category_name); ?></a> &raquo;
        <?php echo esc_html($product_name); ?> - <?php _e('Variants', 'gift-i-card'); ?>
    </h1>

    <div class="gicapi-toolbar">
        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'update_variants'), 'gicapi_update_data')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> <?php _e('Update Variants from API', 'gift-i-card'); ?>
        </a>
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
            <?php
            $variants = get_posts(array(
                'post_type' => 'gic_var',
                'posts_per_page' => -1, // Adjust later for pagination
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_gicapi_variant_product',
                        'value' => $product_id,
                        'compare' => '='
                    )
                )
            ));

            if (!empty($variants)) :
                foreach ($variants as $variant) :
                    $variant_id = $variant->ID;
                    $variant_name = $variant->post_title;
                    $variant_sku = get_post_meta($variant_id, '_gicapi_variant_sku', true);
                    $variant_price = get_post_meta($variant_id, '_gicapi_variant_price', true);
                    $variant_value = get_post_meta($variant_id, '_gicapi_variant_value', true);
                    $variant_max_order = get_post_meta($variant_id, '_gicapi_variant_max_order', true);
                    $variant_stock_status = get_post_meta($variant_id, '_gicapi_variant_stock_status', true);
                    $mapped_product_id = get_post_meta($variant_id, '_gicapi_mapped_wc_product_id', true);
                    $mapped_product = $mapped_product_id ? wc_get_product($mapped_product_id) : null;
            ?>
                    <tr>
                        <td class="title column-title has-row-actions column-primary" data-colname="<?php _e('Name', 'gift-i-card'); ?>">
                            <strong><?php echo esc_html($variant_name); ?></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="#" class="edit-mapping" data-variant-id="<?php echo esc_attr($variant_id); ?>" data-variant-name="<?php echo esc_attr($variant_name); ?>"><?php _e('Map Product', 'gift-i-card'); ?></a></span>
                            </div>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e('Show more details'); ?></span></button>
                        </td>
                        <td data-colname="<?php _e('SKU', 'gift-i-card'); ?>"><?php echo esc_html($variant_sku); ?></td>
                        <td data-colname="<?php _e('Price', 'gift-i-card'); ?>"><?php echo esc_html($variant_price); ?></td>
                        <td data-colname="<?php _e('Value', 'gift-i-card'); ?>"><?php echo esc_html($variant_value); ?></td>
                        <td data-colname="<?php _e('Max Order', 'gift-i-card'); ?>"><?php echo esc_html($variant_max_order); ?></td>
                        <td data-colname="<?php _e('Stock Status', 'gift-i-card'); ?>">
                            <span class="gicapi-stock-status-<?php echo esc_attr($variant_stock_status); ?>">
                                <?php echo esc_html(ucfirst($variant_stock_status)); ?>
                            </span>
                        </td>
                        <td data-colname="<?php _e('Mapped WC Product', 'gift-i-card'); ?>" id="mapped-product-<?php echo esc_attr($variant_id); ?>">
                            <?php if ($mapped_product) : ?>
                                <a href="<?php echo esc_url(get_edit_post_link($mapped_product_id)); ?>" target="_blank">
                                    <?php echo esc_html($mapped_product->get_formatted_name()); ?>
                                </a>
                            <?php else : ?>
                                <span class="gicapi-not-mapped"><?php _e('Not Mapped', 'gift-i-card'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7"><?php _e('No variants found for this product. Try updating from API.', 'gift-i-card'); ?></td>
                </tr>
            <?php endif; ?>
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