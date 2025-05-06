<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get search query
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Get current page
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;

// Get categories with search and pagination
$args = array(
    'post_type' => 'gic_cat',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'title',
    'order' => 'ASC'
);

if ($search) {
    $args['s'] = $search;
}

$categories = get_posts($args);
$total_categories = wp_count_posts('gic_cat')->publish;
?>

<div class="wrap">
    <h1><?php _e('Gift-i-Card Categories', 'gift-i-card'); ?></h1>

    <!-- Search Box -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($plugin_name); ?>-products">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search categories...', 'gift-i-card'); ?>">
                <input type="submit" class="button" value="<?php _e('Search', 'gift-i-card'); ?>">
            </form>
        </div>
    </div>

    <!-- Categories Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('SKU', 'gift-i-card'); ?></th>
                <th><?php _e('Name', 'gift-i-card'); ?></th>
                <th><?php _e('Count', 'gift-i-card'); ?></th>
                <th><?php _e('Permalink', 'gift-i-card'); ?></th>
                <th><?php _e('Thumbnail', 'gift-i-card'); ?></th>
                <th><?php _e('Actions', 'gift-i-card'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $category): ?>
                <?php
                $sku = get_post_meta($category->ID, '_gicapi_category_sku', true);
                $count = get_post_meta($category->ID, '_gicapi_category_count', true);
                $permalink = get_post_meta($category->ID, '_gicapi_category_permalink', true);
                $thumbnail = get_post_meta($category->ID, '_gicapi_category_thumbnail', true);
                ?>
                <tr>
                    <td><?php echo esc_html($sku); ?></td>
                    <td><?php echo esc_html($category->post_title); ?></td>
                    <td><?php echo esc_html($count); ?></td>
                    <td>
                        <a href="<?php echo esc_url($permalink); ?>" target="_blank">
                            <?php echo esc_html($permalink); ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($thumbnail): ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" alt="" style="max-width: 100px;">
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url(add_query_arg(array('page' => $plugin_name . '-products', 'category' => $category->ID))); ?>" class="button">
                            <?php _e('View Products', 'gift-i-card'); ?>
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
                'total' => ceil($total_categories / $per_page),
                'current' => $paged
            ));

            if ($pagination) {
                echo '<div class="tablenav-pages">' . $pagination . '</div>';
            }
            ?>
        </div>
    </div>
</div>