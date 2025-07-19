<?php

/**
 * Displays the variants table for a selected product.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin name for this file
$plugin_name = 'gift-i-card';

$category_sku = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : '';
$product_sku = isset($_GET['product']) ? sanitize_text_field(wp_unslash($_GET['product'])) : '';

// Get product info from API
$api = GICAPI_API::get_instance();

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

$products_page_url = add_query_arg(array('page' => $plugin_name . '-products', 'category' => $category_sku));
$categories_page_url = admin_url('admin.php?page=' . $plugin_name . '-products');

// Get variants from API
$variants = $api->get_variants($product_sku);

$product_name = __('Unknown Product', 'gift-i-card');

if ($variants[0]) {
    $value = $variants[0]['value'];
    $product_name = str_replace($value . ' - ', '', $variants[0]['name']);
}

if (!$variants) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Error fetching variants from API', 'gift-i-card') . '</p></div>';
    return;
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
        <?php echo esc_html($product_name); ?> - <?php esc_html_e('Variants', 'gift-i-card'); ?>
    </h1>

    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Name', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('SKU', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Price', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Value', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Stock Status', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Mapped WC Products', 'gift-i-card'); ?> <span class="mapped-count"></span></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php foreach ($variants as $variant) :
                $variant_name = $variant['name'];
                $variant_sku = $variant['sku'];
                $variant_price = isset($variant['price']) ? $variant['price'] : '';
                $variant_value = isset($variant['value']) ? $variant['value'] : '';
                $variant_max_order = isset($variant['max_order_per_item']) ? $variant['max_order_per_item'] : 0;
                $variant_stock_status = isset($variant['stock_status']) ? $variant['stock_status'] : '';

                // Get mapped products based on product meta
                $args = array(
                    'post_type' => array('product', 'product_variation'),
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => '_gicapi_mapped_category_skus',
                            'value' => $category_sku,
                            'compare' => 'LIKE'
                        ),
                        array(
                            'key' => '_gicapi_mapped_product_skus',
                            'value' => $product_sku,
                            'compare' => 'LIKE'
                        ),
                        array(
                            'key' => '_gicapi_mapped_variant_skus',
                            'value' => $variant_sku,
                            'compare' => 'LIKE'
                        )
                    )
                );

                $query = new WP_Query($args);
                $mapped_products = array();
                $mapped_count = 0;

                if ($query->have_posts()) {
                    while ($query->have_posts()) {
                        $query->the_post();
                        $product = wc_get_product(get_the_ID());
                        if ($product) {
                            $mapped_products[] = $product;
                            $mapped_count++;
                        }
                    }
                }
                wp_reset_postdata();
            ?>
                <tr>
                    <td class="column-title column-primary">
                        <strong><?php echo esc_html($variant_name); ?></strong>
                    </td>
                    <td class="column-sku"><?php echo esc_html($variant_sku); ?></td>
                    <td class="column-price"><?php echo esc_html($variant_price); ?></td>
                    <td class="column-value"><?php echo esc_html($variant_value); ?></td>
                    <td class="column-stock-status"><?php echo esc_html($variant_stock_status); ?></td>
                    <td class="column-mapped-products">
                        <div class="gicapi-mapped-products">
                            <div class="gicapi-mapped-products-list">
                                <?php
                                if (!empty($mapped_products)) :
                                ?>
                                    <?php foreach ($mapped_products as $product) : ?>
                                        <div class="gicapi-mapped-product-item">
                                            <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" target="_blank">
                                                <?php echo esc_html($product->get_name()); ?> (<?php echo esc_html($product->get_sku()); ?>)
                                            </a>
                                            <span class="gicapi-remove-mapping" data-variant-sku="<?php echo esc_attr($variant_sku); ?>" data-product-id="<?php echo esc_attr($product->get_id()); ?>" data-category-sku="<?php echo esc_attr($category_sku); ?>" data-product-sku="<?php echo esc_attr($product_sku); ?>">
                                                <span class="dashicons dashicons-no-alt"></span>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="gicapi-mapped-products-footer">
                                <span class="mapped-count"><?php
                                                            /* translators: %d: number of products mapped */
                                                            echo esc_html(sprintf(_n('%d product mapped', '%d products mapped', $mapped_count, 'gift-i-card'), $mapped_count));
                                                            ?>
                                </span>
                                <button type="button" class="button gicapi-add-mapping" data-variant-sku="<?php echo esc_attr($variant_sku); ?>" data-category-sku="<?php echo esc_attr($category_sku); ?>" data-product-sku="<?php echo esc_attr($product_sku); ?>">
                                    <?php esc_html_e('Add Mapping', 'gift-i-card'); ?>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal for mapping -->
<div id="gicapi-mapping-modal" class="gicapi-modal" style="display:none;">
    <div class="gicapi-modal-content">
        <div class="gicapi-modal-header">
            <h2><?php esc_html_e('Map Gift-i-Card Variant to WooCommerce Product', 'gift-i-card'); ?></h2>
            <span class="gicapi-modal-close">&times;</span>
        </div>
        <div class="gicapi-modal-body">
            <input type="hidden" id="modal-variant-id">
            <div class="gicapi-product-search-wrapper">
                <select id="wc-product-search" class="wc-product-search" data-placeholder="<?php esc_attr_e('Search for a product...', 'gift-i-card'); ?>" data-allow_clear="true">
                    <option></option>
                </select>
            </div>
        </div>
        <div class="gicapi-modal-footer">
            <button id="save-mapping" class="button button-primary"><?php esc_html_e('Add Mapping', 'gift-i-card'); ?></button>
            <button id="close-modal" class="button button-secondary"><?php esc_html_e('Cancel', 'gift-i-card'); ?></button>
            <span class="spinner"></span>
        </div>
    </div>
</div>