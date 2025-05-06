<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get category ID
$category_id = isset($_GET['category']) ? absint($_GET['category']) : 0;

// Get search query
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Get current page
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;

// Get products with search and pagination
$args = array(
    'post_type' => 'gic_prod',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => array(
        array(
            'key' => '_gicapi_product_category',
            'value' => $category_id
        )
    )
);

if ($search) {
    $args['s'] = $search;
}

$products = get_posts($args);
$total_products = wp_count_posts('gic_prod')->publish;
?>

<div class="wrap">
    <h1><?php _e('Gift-i-Card Products', 'gift-i-card'); ?></h1>

    <!-- Back to Categories -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <a href="<?php echo esc_url(remove_query_arg('category')); ?>" class="button">
                <?php _e('Back to Categories', 'gift-i-card'); ?>
            </a>
        </div>
    </div>

    <!-- Search Box -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="gicapi-products">
                <input type="hidden" name="category" value="<?php echo esc_attr($category_id); ?>">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search products...', 'gift-i-card'); ?>">
                <input type="submit" class="button" value="<?php _e('Search', 'gift-i-card'); ?>">
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Name', 'gift-i-card'); ?></th>
                <th><?php _e('SKU', 'gift-i-card'); ?></th>
                <th><?php _e('URL', 'gift-i-card'); ?></th>
                <th><?php _e('Image', 'gift-i-card'); ?></th>
                <th><?php _e('Variant Count', 'gift-i-card'); ?></th>
                <th><?php _e('Actions', 'gift-i-card'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <?php
                $sku = get_post_meta($product->ID, '_gicapi_product_sku', true);
                $url = get_post_meta($product->ID, '_gicapi_product_url', true);
                $image_url = get_post_meta($product->ID, '_gicapi_product_image_url', true);
                $variant_count = get_post_meta($product->ID, '_gicapi_product_variant_count', true);
                ?>
                <tr>
                    <td><?php echo esc_html($product->post_title); ?></td>
                    <td><?php echo esc_html($sku); ?></td>
                    <td>
                        <a href="<?php echo esc_url($url); ?>" target="_blank">
                            <?php echo esc_html($url); ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($image_url): ?>
                            <img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width: 100px;">
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($variant_count); ?></td>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg(array('page' => 'gicapi-products', 'category' => $category_id, 'product' => $product->ID))); ?>" class="button">
                            <?php _e('View Variants', 'gift-i-card'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            $pagination = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => ceil($total_products / $per_page),
                'current' => $paged
            ));

            if ($pagination) {
                echo '<div class="tablenav-pages">' . $pagination . '</div>';
            }
            ?>
        </div>
    </div>
</div>