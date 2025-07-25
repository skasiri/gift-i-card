<?php
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin name for this file
$plugin_name = 'gift-i-card';

// Get current page
$paged = isset($_GET['paged']) ? absint(wp_unslash($_GET['paged'])) : 1;
$per_page = 20;

// Get categories from API
$api = GICAPI_API::get_instance();
$categories = $api->get_categories();

if (!$categories) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Error fetching categories from API', 'gift-i-card') . '</p></div>';
    return;
}

// Calculate pagination
$total_categories = count($categories);
$total_pages = ceil($total_categories / $per_page);
$offset = ($paged - 1) * $per_page;
$categories = array_slice($categories, $offset, $per_page);
?>

<div class="wrap gicapi-admin-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?> - <?php esc_html_e('Categories', 'gift-i-card'); ?></h1>

    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-thumbnail"><?php esc_html_e('Thumbnail', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Name', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('SKU', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Product Count', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Mapped Products', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Mapped Variants', 'gift-i-card'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php
            if (!empty($categories)) :
                foreach ($categories as $category) :
                    $category_name = $category['name'];
                    $category_sku = $category['sku'];
                    $category_count = $category['count'];
                    $category_thumbnail = isset($category['thumbnail']) ? $category['thumbnail'] : '';

                    // Count mapped products for this category (unique product_sku identifiers)
                    $mapped_products_count = 0;
                    $args_products = array(
                        'post_type' => array('product', 'product_variation'),
                        'post_status' => 'publish',
                        'posts_per_page' => -1,
                        'meta_query' => array(
                            array(
                                'key' => '_gicapi_mapped_category_skus',
                                'value' => $category_sku,
                                'compare' => 'LIKE'
                            )
                        )
                    );
                    $query_products = new WP_Query($args_products);
                    $unique_product_skus = array();

                    if ($query_products->have_posts()) {
                        while ($query_products->have_posts()) {
                            $query_products->the_post();
                            $product_skus = get_post_meta(get_the_ID(), '_gicapi_mapped_product_skus', true);

                            // Handle both array and string formats
                            if (is_array($product_skus)) {
                                foreach ($product_skus as $sku) {
                                    if (!empty($sku)) {
                                        $unique_product_skus[] = $sku;
                                    }
                                }
                            } elseif (is_string($product_skus) && !empty($product_skus)) {
                                // Handle comma-separated string format
                                $skus = explode(',', $product_skus);
                                foreach ($skus as $sku) {
                                    $sku = trim($sku);
                                    if (!empty($sku)) {
                                        $unique_product_skus[] = $sku;
                                    }
                                }
                            }
                        }
                    }
                    wp_reset_postdata();

                    $mapped_products_count = count(array_unique($unique_product_skus));

                    // Count mapped variants for this category (all variations and products with variant mappings)
                    $mapped_variants_count = 0;
                    $args_variants = array(
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
                                'key' => '_gicapi_mapped_variant_skus',
                                'compare' => 'EXISTS'
                            )
                        ),
                        'fields' => 'ids'
                    );
                    $query_variants = new WP_Query($args_variants);
                    $mapped_variants_count = $query_variants->found_posts;
                    wp_reset_postdata();

                    $products_page_url = add_query_arg(array('category' => $category_sku), admin_url('admin.php?page=' . $plugin_name . '-products'));
            ?>
                    <tr>
                        <td class="column-thumbnail">
                            <?php if ($category_thumbnail) : ?>
                                <img src="<?php echo esc_url($category_thumbnail); ?>" alt="<?php echo esc_attr($category_name); ?>" style="max-width: 50px; height: auto;">
                            <?php endif; ?>
                        </td>
                        <td class="column-title column-primary">
                            <strong>
                                <a href="<?php echo esc_url($products_page_url); ?>" class="row-title">
                                    <?php echo esc_html($category_name); ?>
                                </a>
                            </strong>
                        </td>
                        <td class="column-sku"><?php echo esc_html($category_sku); ?></td>
                        <td class="column-count"><?php echo esc_html($category_count); ?></td>
                        <td class="column-mapped-products"><?php echo esc_html($mapped_products_count); ?></td>
                        <td class="column-mapped-variants"><?php echo esc_html($mapped_variants_count); ?></td>
                    </tr>
                <?php
                endforeach;
            else :
                ?>
                <tr>
                    <td colspan="6"><?php esc_html_e('No categories found.', 'gift-i-card'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-thumbnail"><?php esc_html_e('Thumbnail', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Name', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('SKU', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Product Count', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Mapped Products', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Mapped Variants', 'gift-i-card'); ?></th>
            </tr>
        </tfoot>
    </table>

    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php
                                                /* translators: %s: number of categories displayed */
                                                echo esc_html(sprintf(_n('%s item', '%s items', $total_categories, 'gift-i-card'), number_format_i18n($total_categories)));
                                                ?>
                </span>
                <span class="pagination-links">
                    <?php
                    echo wp_kses_post(paginate_links(array(
                        'base' => add_query_arg(array('page' => $plugin_name . '-products', 'paged' => '%#%')),
                        'format' => '',
                        'prev_text' => esc_html__('&laquo;', 'gift-i-card'),
                        'next_text' => esc_html__('&raquo;', 'gift-i-card'),
                        'total' => $total_pages,
                        'current' => $paged
                    )));
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>