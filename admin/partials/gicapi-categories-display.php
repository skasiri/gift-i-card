<?php
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin name for this file
$plugin_name = 'gift-i-card';

// Get search query
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Get current page
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;

// Get categories from API
$api = GICAPI_API::get_instance();
$categories = $api->get_categories();

if (!$categories) {
    echo '<div class="notice notice-error"><p>' . esc_html__('Error fetching categories from API', 'gift-i-card') . '</p></div>';
    return;
}

// Filter categories if search is active
if ($search) {
    $categories = array_filter($categories, function ($category) use ($search) {
        return stripos($category['name'], $search) !== false;
    });
}

// Calculate pagination
$total_categories = count($categories);
$total_pages = ceil($total_categories / $per_page);
$offset = ($paged - 1) * $per_page;
$categories = array_slice($categories, $offset, $per_page);
?>

<div class="wrap gicapi-admin-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?> - <?php esc_html_e('Categories', 'gift-i-card'); ?></h1>

    <div class="gicapi-toolbar">
        <form method="get" class="search-form">
            <input type="hidden" name="page" value="<?php echo esc_attr($plugin_name . '-products'); ?>">
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php esc_html_e('Search Categories:', 'gift-i-card'); ?></label>
                <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($search); ?>">
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e('Search Categories', 'gift-i-card'); ?>">
            </p>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-thumbnail"><?php esc_html_e('Thumbnail', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e('Name', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('SKU', 'gift-i-card'); ?></th>
                <th scope="col" class="manage-column"><?php esc_html_e('Product Count', 'gift-i-card'); ?></th>
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
                    $products_page_url = add_query_arg('category', $category_sku, admin_url('admin.php?page=' . $plugin_name . '-products'));
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
                    </tr>
                <?php
                endforeach;
            else :
                ?>
                <tr>
                    <td colspan="4"><?php esc_html_e('No categories found.', 'gift-i-card'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                /* translators: %s: number of items */
                ?>
                <span class="displaying-num"><?php echo esc_html(sprintf(_n('%s item', '%s items', $total_categories, 'gift-i-card'), number_format_i18n($total_categories))); ?></span>
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