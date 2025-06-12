<?php

/**
 * Displays the products table for a selected category.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$category_meta = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$parent_page_url = menu_page_url($plugin_name . '-products', false);

if (strpos($category_meta, 'GC-') !== 0) {
    echo '<div class="wrap gicapi-admin-page"><h1>Category is not valid.</h1></div>';
    return;
}

$products_args = array(
    'post_type' => 'gic_prod',
    'posts_per_page' => -1, // Adjust later for pagination
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_gicapi_product_category',
            'value' => $category_meta,
            'compare' => '='
        )
    )
);
$products = get_posts($products_args);

if (empty($products)) {
    echo '<div class="wrap gicapi-admin-page"><h1>Category is not valid.</h1></div>';
    return;
}

$category_args = array(
    'post_type' => 'gic_cat',
    'posts_per_page' => 1,
    'meta_query' => array(
        array(
            'key' => '_gicapi_category_sku',
            'value' => $category_meta,
            'compare' => '='
        )
    )
);
$category = get_posts($category_args);
$category_name = !empty($category) ? $category[0]->post_title : __('Unknown Category', 'gift-i-card');


?>
<div class="wrap gicapi-admin-page">
    <h1>
        <a href="<?php echo esc_url($parent_page_url); ?>"><?php echo esc_html(get_admin_page_title()); ?></a> &raquo;
        <?php echo esc_html($category_name); ?> - <?php _e('Products', 'gift-i-card'); ?>
    </h1>

    <div class="gicapi-toolbar">
        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'update_products'), 'gicapi_update_data')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> <?php _e('Update Products from API', 'gift-i-card'); ?>
        </a>
        <!-- Add search form here later -->
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-thumbnail"><?php _e('Image', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Name', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('SKU', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Variant Count', 'gift-i-card'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php


            if (!empty($products)) :
                foreach ($products as $product) :
                    $product_id = $product->ID;
                    $product_name = $product->post_title;
                    $product_sku = get_post_meta($product_id, '_gicapi_product_sku', true);
                    $product_image_url = get_post_meta($product_id, '_gicapi_product_image_url', true);
                    $product_variant_count = get_post_meta($product_id, '_gicapi_product_variant_count', true);
                    $variants_page_url = add_query_arg('product', $product_id);
                    $is_deleted = get_post_meta($product_id, '_gicapi_is_deleted', true) === 'true';
            ?>
                    <tr class="<?php if ($is_deleted) echo 'gicapi-item-deleted'; ?>">
                        <td class="column-thumbnail">
                            <?php if ($product_image_url) : ?>
                                <img src="<?php echo esc_url($product_image_url); ?>" alt="<?php echo esc_attr($product_name); ?>" width="40" height="40" style="object-fit: contain;">
                            <?php endif; ?>
                        </td>
                        <td class="title column-title has-row-actions column-primary" data-colname="<?php _e('Name', 'gift-i-card'); ?>">
                            <strong>
                                <a class="row-title" href="<?php echo esc_url($variants_page_url); ?>">
                                    <?php echo esc_html($product_name); ?>
                                </a>
                                <?php if ($is_deleted) : ?>
                                    <span class="gicapi-deleted-status"> (<?php _e('Deleted', 'gift-i-card'); ?>)</span>
                                <?php endif; ?>
                            </strong>
                            <div class="row-actions">
                                <span class="view"><a href="<?php echo esc_url($variants_page_url); ?>" <?php if ($is_deleted) echo 'style="pointer-events:none; opacity:0.5;"'; ?>><?php _e('View Variants', 'gift-i-card'); ?></a></span>
                            </div>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e('Show more details'); ?></span></button>
                        </td>
                        <td data-colname="<?php _e('SKU', 'gift-i-card'); ?>"><?php echo esc_html($product_sku); ?></td>
                        <td data-colname="<?php _e('Variant Count', 'gift-i-card'); ?>"><?php echo esc_html($product_variant_count); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4"><?php _e('No products found for this category. Try updating from API.', 'gift-i-card'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-thumbnail"><?php _e('Image', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Name', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('SKU', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Variant Count', 'gift-i-card'); ?></th>
            </tr>
        </tfoot>
    </table>
    <!-- Add pagination controls here later -->
</div>