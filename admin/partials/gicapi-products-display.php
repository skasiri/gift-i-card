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
            'key' => 'category_sku',
            'value' => $selected_category
        )
    );
}

$products = get_posts($args);
?>

<div class="wrap">
    <h1><?php _e('Gift Card Products', 'gift-i-card'); ?></h1>

    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="category" id="category-filter">
                <option value=""><?php _e('All Categories', 'gift-i-card'); ?></option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo esc_attr(get_post_meta($category->ID, 'sku', true)); ?>" <?php selected($selected_category, get_post_meta($category->ID, 'sku', true)); ?>>
                        <?php echo esc_html($category->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="button" id="sync-categories"><?php _e('Sync Categories', 'gift-i-card'); ?></button>
            <button class="button" id="sync-products"><?php _e('Sync Products', 'gift-i-card'); ?></button>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Category', 'gift-i-card'); ?></th>
                <th><?php _e('Product', 'gift-i-card'); ?></th>
                <th><?php _e('Variant', 'gift-i-card'); ?></th>
                <th><?php _e('Price', 'gift-i-card'); ?></th>
                <th><?php _e('Stock Status', 'gift-i-card'); ?></th>
                <th><?php _e('Actions', 'gift-i-card'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <?php
                $variants = get_posts(array(
                    'post_type' => 'gic_var',
                    'meta_key' => 'product_sku',
                    'meta_value' => get_post_meta($product->ID, 'sku', true),
                    'posts_per_page' => -1
                ));

                foreach ($variants as $variant):
                    $category_sku = get_post_meta($product->ID, 'category_sku', true);
                    $category = get_posts(array(
                        'post_type' => 'gic_cat',
                        'meta_key' => 'sku',
                        'meta_value' => $category_sku,
                        'posts_per_page' => 1
                    ));
                    $category_name = !empty($category) ? $category[0]->post_title : '';
                ?>
                    <tr>
                        <td><?php echo esc_html($category_name); ?></td>
                        <td><?php echo esc_html($product->post_title); ?></td>
                        <td><?php echo esc_html($variant->post_title); ?></td>
                        <td>
                            <?php
                            $price = get_post_meta($variant->ID, 'price', true);
                            $currency = get_post_meta($variant->ID, 'price_currency', true);
                            echo esc_html($price . ' ' . $currency);
                            ?>
                        </td>
                        <td><?php echo esc_html(get_post_meta($variant->ID, 'stock_status', true)); ?></td>
                        <td>
                            <button class="button map-product" data-variant-sku="<?php echo esc_attr(get_post_meta($variant->ID, 'sku', true)); ?>">
                                <?php _e('Map to WooCommerce', 'gift-i-card'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
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
                    $mapped_variant = get_post_meta($wc_product->ID, '_gic_variant_sku', true);
                    if (!$mapped_variant):
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
        <input type="hidden" name="variant_sku" id="variant-sku">
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

        // Sync categories
        $('#sync-categories').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true).text('<?php _e('Syncing...', 'gift-i-card'); ?>');

            $.post(ajaxurl, {
                action: 'gicapi_sync_categories',
                nonce: '<?php echo wp_create_nonce('gicapi_sync_categories'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            }).always(function() {
                $button.prop('disabled', false).text('<?php _e('Sync Categories', 'gift-i-card'); ?>');
            });
        });

        // Sync products
        $('#sync-products').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true).text('<?php _e('Syncing...', 'gift-i-card'); ?>');

            $.post(ajaxurl, {
                action: 'gicapi_sync_products',
                nonce: '<?php echo wp_create_nonce('gicapi_sync_products'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            }).always(function() {
                $button.prop('disabled', false).text('<?php _e('Sync Products', 'gift-i-card'); ?>');
            });
        });

        // Map product
        $('.map-product').on('click', function() {
            var $button = $(this);
            var variantSku = $button.data('variant-sku');

            $('#variant-sku').val(variantSku);
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
                            gic_variant_sku: $('#variant-sku').val()
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