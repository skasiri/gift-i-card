<?php

/**
 * Displays the products table for a selected category.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$category_sku = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$parent_page_url = menu_page_url($plugin_name . '-products', false);

if (strpos($category_sku, 'GC-') !== 0) {
    echo '<div class="wrap gicapi-admin-page"><h1>' . esc_html__('Category is not valid.', 'gift-i-card') . '</h1></div>';
    return;
}

// Get products from API
$api = GICAPI_API::get_instance();
$response = $api->get_products($category_sku, 1, 999); // Get all products for the category

if (is_wp_error($response)) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Error fetching products from API', 'gift-i-card') . '</p></div>';
    return;
}

$products = isset($response['products']) ? $response['products'] : array();

if (empty($products)) {
    echo '<div class="wrap gicapi-admin-page"><h1>' . esc_html__('No products found for this category.', 'gift-i-card') . '</h1></div>';
    return;
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
?>
<div class="wrap gicapi-admin-page">
    <h1>
        <a href="<?php echo esc_url($parent_page_url); ?>"><?php echo esc_html(get_admin_page_title()); ?></a> &raquo;
        <?php echo esc_html($category_name); ?> - <?php _e('Products', 'gift-i-card'); ?>
    </h1>

    <div class="gicapi-toolbar">
        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'update_products'), 'gicapi_update_data')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> <?php _e('Update Products', 'gift-i-card'); ?>
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
            foreach ($products as $product) :
                $product_name = $product['name'];
                $product_sku = $product['sku'];
                $product_image_url = isset($product['image_url']) ? $product['image_url'] : '';
                $product_variant_count = isset($product['variant_count']) ? $product['variant_count'] : 0;
                $variants_page_url = add_query_arg('product', $product_sku);
            ?>
                <tr>
                    <td class="column-thumbnail">
                        <?php if ($product_image_url) : ?>
                            <img src="<?php echo esc_url($product_image_url); ?>" alt="<?php echo esc_attr($product_name); ?>" style="max-width: 50px; height: auto;">
                        <?php endif; ?>
                    </td>
                    <td class="column-title column-primary">
                        <strong>
                            <a href="<?php echo esc_url($variants_page_url); ?>" class="row-title">
                                <?php echo esc_html($product_name); ?>
                            </a>
                        </strong>
                    </td>
                    <td class="column-sku"><?php echo esc_html($product_sku); ?></td>
                    <td class="column-variant-count"><?php echo esc_html($product_variant_count); ?></td>
                </tr>
            <?php endforeach; ?>
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