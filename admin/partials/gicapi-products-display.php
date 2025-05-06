<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get categories
$categories = get_posts(array(
    'post_type' => 'gic_cat',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

// Get selected category
$selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

// Get products
$args = array(
    'post_type' => 'gic_prod',
    'posts_per_page' => 20,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    'orderby' => 'title',
    'order' => 'ASC'
);

if ($selected_category) {
    $args['meta_query'] = array(
        array(
            'key' => '_gicapi_product_category',
            'value' => $selected_category
        )
    );
}

$products = get_posts($args);
?>

<div class="wrap">
    <h1><?php _e('Gift-i-Card Products', 'gift-i-card'); ?></h1>

    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="category" id="category-filter">
                <option value=""><?php _e('All Categories', 'gift-i-card'); ?></option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo esc_attr($category->ID); ?>" <?php selected($selected_category, $category->ID); ?>>
                        <?php echo esc_html($category->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Category', 'gift-i-card'); ?></th>
                <th><?php _e('Product', 'gift-i-card'); ?></th>
                <th><?php _e('Price', 'gift-i-card'); ?></th>
                <th><?php _e('Stock', 'gift-i-card'); ?></th>
                <th><?php _e('Actions', 'gift-i-card'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <?php
                $category_id = get_post_meta($product->ID, '_gicapi_product_category', true);
                $category = get_post($category_id);
                $price = get_post_meta($product->ID, '_gicapi_product_price', true);
                $stock = get_post_meta($product->ID, '_gicapi_product_stock', true);
                ?>
                <tr>
                    <td><?php echo esc_html($category ? $category->post_title : ''); ?></td>
                    <td><?php echo esc_html($product->post_title); ?></td>
                    <td><?php echo esc_html(number_format($price, 0, '.', ',')); ?></td>
                    <td><?php echo esc_html($stock); ?></td>
                    <td>
                        <button class="button map-product" data-product-id="<?php echo esc_attr($product->ID); ?>">
                            <?php _e('Map to WooCommerce', 'gift-i-card'); ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php
            $pagination = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => ceil(wp_count_posts('gic_prod')->publish / 20),
                'current' => get_query_var('paged') ? get_query_var('paged') : 1
            ));

            if ($pagination) {
                echo '<div class="tablenav-pages">' . $pagination . '</div>';
            }
            ?>
        </div>
    </div>
</div>

<div id="map-product-dialog" style="display: none;">
    <form id="map-product-form">
        <p>
            <label for="wc-product"><?php _e('WooCommerce Product:', 'gift-i-card'); ?></label>
            <select name="wc_product_id" id="wc-product" required>
                <option value=""><?php _e('Select a product', 'gift-i-card'); ?></option>
                <?php
                $wc_products = get_posts(array(
                    'post_type' => 'product',
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));

                foreach ($wc_products as $wc_product):
                    $mapped_product = get_post_meta($wc_product->ID, '_gicapi_product_id', true);
                    if (!$mapped_product):
                ?>
                        <option value="<?php echo esc_attr($wc_product->ID); ?>">
                            <?php echo esc_html($wc_product->post_title); ?>
                        </option>
                <?php
                    endif;
                endforeach;
                ?>
            </select>
        </p>
        <input type="hidden" name="product_id" id="product-id">
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Category filter
        $('#category-filter').on('change', function() {
            var url = new URL(window.location.href);
            url.searchParams.set('category', $(this).val());
            window.location.href = url.toString();
        });

        // Map product
        $('.map-product').on('click', function() {
            var $button = $(this);
            var productId = $button.data('product-id');

            $('#product-id').val(productId);
            $('#map-product-dialog').dialog({
                title: '<?php _e('Map to WooCommerce', 'gift-i-card'); ?>',
                modal: true,
                width: 500,
                buttons: {
                    '<?php _e('Map', 'gift-i-card'); ?>': function() {
                        var $dialog = $(this);
                        var $form = $('#map-product-form');
                        var $select = $('#wc-product');

                        if (!$select.val()) {
                            alert('<?php _e('Please select a product', 'gift-i-card'); ?>');
                            return;
                        }

                        $.post(ajaxurl, {
                            action: 'gicapi_map_product',
                            nonce: '<?php echo wp_create_nonce('gicapi_map_product'); ?>',
                            wc_product_id: $select.val(),
                            gic_product_id: $('#product-id').val()
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