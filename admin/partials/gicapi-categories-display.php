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

<div class="wrap gicapi-admin-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?> - <?php _e('Categories', 'gift-i-card'); ?></h1>

    <div class="gicapi-toolbar">
        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('action', 'update_categories'), 'gicapi_update_data')); ?>" class="button button-secondary">
            <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> <?php _e('Update Categories', 'gift-i-card'); ?>
        </a>
        <!-- Add search form here later -->
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-thumbnail"><?php _e('Thumbnail', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Name', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('SKU', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Product Count', 'gift-i-card'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php
            if (!empty($categories)) :
                foreach ($categories as $category) :
                    $category_id = $category->ID;
                    $category_name = $category->post_title;
                    $category_sku = get_post_meta($category_id, '_gicapi_category_sku', true);
                    $category_count = get_post_meta($category_id, '_gicapi_category_count', true);
                    $category_thumbnail = get_post_meta($category_id, '_gicapi_category_thumbnail', true);
                    $products_page_url = add_query_arg('category', $category_sku, menu_page_url($plugin_name . '-products', false));
                    $is_deleted = get_post_meta($category_id, '_gicapi_is_deleted', true) === 'true';
            ?>
                    <tr class="<?php if ($is_deleted) echo 'gicapi-item-deleted'; ?>">
                        <td class="column-thumbnail">
                            <?php if ($category_thumbnail) : ?>
                                <img src="<?php echo esc_url($category_thumbnail); ?>" alt="<?php echo esc_attr($category_name); ?>" width="40" height="40" style="object-fit: contain;">
                            <?php endif; ?>
                        </td>
                        <td class="title column-title has-row-actions column-primary" data-colname="<?php _e('Name', 'gift-i-card'); ?>">
                            <strong>
                                <a class="row-title" href="<?php echo esc_url($products_page_url); ?>">
                                    <?php echo esc_html($category_name); ?>
                                </a>
                                <?php if ($is_deleted) : ?>
                                    <span class="gicapi-deleted-status"> (<?php _e('Deleted', 'gift-i-card'); ?>)</span>
                                <?php endif; ?>
                            </strong>
                            <div class="row-actions">
                                <span class="view"><a href="<?php echo esc_url($products_page_url); ?>" <?php if ($is_deleted) echo 'style="pointer-events:none; opacity:0.5;"'; ?>><?php _e('View Products', 'gift-i-card'); ?></a></span>
                            </div>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php _e('Show more details'); ?></span></button>
                        </td>
                        <td data-colname="<?php _e('SKU', 'gift-i-card'); ?>"><?php echo esc_html($category_sku); ?></td>
                        <td data-colname="<?php _e('Product Count', 'gift-i-card'); ?>"><?php echo esc_html($category_count); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4"><?php _e('No categories found. Try updating from API.', 'gift-i-card'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column column-thumbnail"><?php _e('Thumbnail', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column column-title column-primary"><?php _e('Name', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('SKU', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php _e('Product Count', 'gift-i-card'); ?></th>
            </tr>
        </tfoot>
    </table>
    <!-- Add pagination controls here later -->
</div>