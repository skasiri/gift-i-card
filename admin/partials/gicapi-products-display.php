<?php

/**
 * Displays the products table for a selected category.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin name for this file
$plugin_name = 'gift-i-card';

$category_sku = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$parent_page_url = admin_url('admin.php?page=' . $plugin_name . '-products');

// Get current page and items per page
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 10;

if (strpos($category_sku, 'GC-') !== 0) {
    echo '<div class="wrap gicapi-admin-page"><h1>' . esc_html__('Category is not valid.', 'gift-i-card') . '</h1></div>';
    return;
}

// Get products from API
$api = GICAPI_API::get_instance();
$response = $api->get_products($category_sku, $paged, $per_page); // Get products with pagination

if (!$response) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Error fetching products from API', 'gift-i-card') . '</p></div>';
    return;
}

$products = isset($response['products']) ? $response['products'] : array();
$total_products = isset($response['total']) ? $response['total'] : 0;
$total_pages = ceil($total_products / $per_page);

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
                $variants_page_url = add_query_arg(array('page' => $plugin_name . '-products', 'category' => $category_sku, 'product' => $product_sku));
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
    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                /* translators: %s: number of items */
                ?>
                <span class="displaying-num"><?php printf(_n('%s item', '%s items', $total_products, 'gift-i-card'), number_format_i18n($total_products)); ?></span>
                <span class="pagination-links">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg(array('page' => $plugin_name . '-products', 'category' => $category_sku, 'paged' => '%#%')),
                        'format' => '',
                        'prev_text' => __('&laquo;', 'gift-i-card'),
                        'next_text' => __('&raquo;', 'gift-i-card'),
                        'total' => $total_pages,
                        'current' => $paged
                    ));
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>