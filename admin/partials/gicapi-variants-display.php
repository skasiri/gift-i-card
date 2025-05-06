<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get product ID
$product_id = isset($_GET['product']) ? absint($_GET['product']) : 0;

// Get search query
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Get current page
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;

// Get variants with search and pagination
$args = array(
    'post_type' => 'gic_var',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => array(
        array(
            'key' => '_gicapi_variant_product',
            'value' => $product_id
        )
    )
);

if ($search) {
    $args['s'] = $search;
}

$variants = get_posts($args);
$total_variants = wp_count_posts('gic_var')->publish;
?>

<div class="wrap">
    <h1><?php _e('Gift-i-Card Variants', 'gift-i-card'); ?></h1>

    <!-- Back to Products -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <a href="<?php echo esc_url(remove_query_arg('product')); ?>" class="button">
                <?php _e('Back to Products', 'gift-i-card'); ?>
            </a>
        </div>
    </div>

    <!-- Search Box -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="gicapi-products">
                <input type="hidden" name="category" value="<?php echo esc_attr($_GET['category']); ?>">
                <input type="hidden" name="product" value="<?php echo esc_attr($product_id); ?>">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search variants...', 'gift-i-card'); ?>">
                <input type="submit" class="button" value="<?php _e('Search', 'gift-i-card'); ?>">
            </form>
        </div>
    </div>

    <!-- Variants Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Name', 'gift-i-card'); ?></th>
                <th><?php _e('SKU', 'gift-i-card'); ?></th>
                <th><?php _e('Price', 'gift-i-card'); ?></th>
                <th><?php _e('Value', 'gift-i-card'); ?></th>
                <th><?php _e('Max Order', 'gift-i-card'); ?></th>
                <th><?php _e('Stock Status', 'gift-i-card'); ?></th>
                <th><?php _e('Actions', 'gift-i-card'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($variants as $variant): ?>
                <?php
                $sku = get_post_meta($variant->ID, '_gicapi_variant_sku', true);
                $price = get_post_meta($variant->ID, '_gicapi_variant_price', true);
                $value = get_post_meta($variant->ID, '_gicapi_variant_value', true);
                $max_order = get_post_meta($variant->ID, '_gicapi_variant_max_order', true);
                $stock_status = get_post_meta($variant->ID, '_gicapi_variant_stock_status', true);
                ?>
                <tr>
                    <td><?php echo esc_html($variant->post_title); ?></td>
                    <td><?php echo esc_html($sku); ?></td>
                    <td><?php echo esc_html(number_format($price, 0, '.', ',')); ?></td>
                    <td><?php echo esc_html($value); ?></td>
                    <td><?php echo esc_html($max_order); ?></td>
                    <td><?php echo esc_html($stock_status); ?></td>
                    <td>
                        <button class="button map-variant" data-variant-id="<?php echo esc_attr($variant->ID); ?>">
                            <?php _e('Map to WooCommerce', 'gift-i-card'); ?>
                        </button>
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
                'total' => ceil($total_variants / $per_page),
                'current' => $paged
            ));

            if ($pagination) {
                echo '<div class="tablenav-pages">' . $pagination . '</div>';
            }
            ?>
        </div>
    </div>
</div>

<div id="map-variant-dialog" style="display: none;">
    <form id="map-variant-form">
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
                    $mapped_variant = get_post_meta($wc_product->ID, '_gicapi_variant_id', true);
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
        <input type="hidden" name="variant_id" id="variant-id">
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Map variant
        $('.map-variant').on('click', function() {
            var $button = $(this);
            var variantId = $button.data('variant-id');

            $('#variant-id').val(variantId);
            $('#map-variant-dialog').dialog({
                title: '<?php _e('Map to WooCommerce', 'gift-i-card'); ?>',
                modal: true,
                width: 500,
                buttons: {
                    '<?php _e('Map', 'gift-i-card'); ?>': function() {
                        var $dialog = $(this);
                        var $form = $('#map-variant-form');
                        var $select = $('#wc-product');

                        if (!$select.val()) {
                            alert('<?php _e('Please select a product', 'gift-i-card'); ?>');
                            return;
                        }

                        $.post(ajaxurl, {
                            action: 'gicapi_map_variant',
                            nonce: '<?php echo wp_create_nonce('gicapi_map_variant'); ?>',
                            wc_product_id: $select.val(),
                            gic_variant_id: $('#variant-id').val()
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