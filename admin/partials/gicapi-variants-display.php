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

// Verify nonce if form is submitted
if (isset($_GET['category']) || isset($_GET['product'])) {
    if (!wp_verify_nonce($_GET['gicapi_nonce'] ?? '', 'gicapi_view_variants')) {
        wp_die(esc_html__('Security check failed.', 'gift-i-card'));
    }
}

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

$nonce = wp_create_nonce('gicapi_view_products');
$products_page_url = add_query_arg(array('page' => $plugin_name . '-products', 'category' => $category_sku, 'gicapi_nonce' => $nonce));
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

    <div class="gicapi-toolbar">
        <!-- Add search form here later -->
    </div>

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
                                            <a href="<?php echo esc_url(get_edit_post_link($product->get_id())); ?>" target="_blank">
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

<style>
    .gicapi-modal {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .gicapi-modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 50%;
        max-width: 600px;
        border-radius: 4px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 100000;
    }

    .gicapi-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }

    .gicapi-modal-close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .gicapi-modal-close:hover {
        color: black;
    }

    .gicapi-product-search-wrapper {
        margin-bottom: 20px;
        position: relative;
        z-index: 100001;
    }

    .gicapi-modal-body {
        position: relative;
        z-index: 100001;
    }

    .gicapi-mapped-products {
        margin-bottom: 10px;
    }

    .gicapi-mapped-products-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }

    .gicapi-mapped-products-footer .mapped-count {
        color: #666;
        font-style: italic;
    }

    .gicapi-mapped-product-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 5px;
        padding: 8px;
        background: #f8f8f8;
        border-radius: 3px;
        border: 1px solid #e5e5e5;
    }

    .gicapi-mapped-product-item a {
        flex: 1;
        margin-right: 10px;
        text-decoration: none;
    }

    .gicapi-mapped-product-item a:hover {
        color: #2271b1;
    }

    .gicapi-remove-mapping {
        cursor: pointer;
        color: #cc0000;
        padding: 2px;
        border-radius: 3px;
    }

    .gicapi-remove-mapping:hover {
        background: #ffebee;
    }

    .gicapi-mapped-products-list {
        max-height: 200px;
        overflow-y: auto;
        margin-bottom: 10px;
        padding-right: 5px;
    }

    .gicapi-mapped-products-list::-webkit-scrollbar {
        width: 8px;
    }

    .gicapi-mapped-products-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .gicapi-mapped-products-list::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .gicapi-mapped-products-list::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Select2 Custom Styles */
    .select2-container--default .select2-selection--single {
        height: 32px;
        border-color: #ddd;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 32px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 30px;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        border-color: #ddd;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #2271b1;
    }

    .select2-dropdown {
        border-color: #ddd;
    }

    .select2-container {
        z-index: 100002 !important;
    }

    .select2-dropdown {
        z-index: 100002 !important;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Initialize select2 for product search
        $('.wc-product-search').select2({
            dropdownParent: $('#gicapi-mapping-modal'),
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        action: 'gicapi_search_products',
                        nonce: '<?php echo esc_js(wp_create_nonce('gicapi_search_products')); ?>',
                        category_sku: '<?php echo esc_js($category_sku); ?>',
                        product_sku: '<?php echo esc_js($product_sku); ?>'
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            minimumInputLength: 2,
            placeholder: '<?php echo esc_js(__('Search for a product...', 'gift-i-card')); ?>',
            width: '100%'
        });

        // Open modal for adding mapping
        $('.gicapi-add-mapping').on('click', function() {
            var variantSku = $(this).data('variant-sku');
            var categorySku = $(this).data('category-sku');
            var productSku = $(this).data('product-sku');
            $('#modal-variant-id').val(variantSku);
            $('#gicapi-mapping-modal').show();
            $('.wc-product-search').val(null).trigger('change');
        });

        // Close modal
        $('.gicapi-modal-close, #close-modal').on('click', function() {
            $('#gicapi-mapping-modal').hide();
            $('.wc-product-search').val(null).trigger('change');
        });

        // Save mapping
        $('#save-mapping').on('click', function() {
            var $button = $(this);
            var variantSku = $('#modal-variant-id').val();
            var productId = $('.wc-product-search').val();
            var categorySku = '<?php echo esc_js($category_sku); ?>';
            var productSku = '<?php echo esc_js($product_sku); ?>';

            if (!productId) {
                alert('<?php echo esc_js(__('Please select a product', 'gift-i-card')); ?>');
                return;
            }

            $button.prop('disabled', true);
            $('.spinner').show();

            $.post(ajaxurl, {
                action: 'gicapi_add_mapping',
                nonce: '<?php echo esc_js(wp_create_nonce('gicapi_add_mapping')); ?>',
                variant_sku: variantSku,
                product_id: productId,
                category_sku: categorySku,
                product_sku: productSku
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || '<?php echo esc_js(__('Error adding mapping', 'gift-i-card')); ?>');
                }
            }).fail(function() {
                alert('<?php echo esc_js(__('Error adding mapping', 'gift-i-card')); ?>');
            }).always(function() {
                $button.prop('disabled', false);
                $('.spinner').hide();
            });
        });

        // Remove mapping
        $('.gicapi-remove-mapping').on('click', function() {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to remove this mapping?', 'gift-i-card')); ?>')) {
                return;
            }

            var $button = $(this);
            var variantSku = $button.data('variant-sku');
            var productId = $button.data('product-id');
            var categorySku = $button.data('category-sku');
            var productSku = $button.data('product-sku');

            $button.prop('disabled', true);

            $.post(ajaxurl, {
                action: 'gicapi_remove_mapping',
                nonce: '<?php echo esc_js(wp_create_nonce('gicapi_remove_mapping')); ?>',
                variant_sku: variantSku,
                product_id: productId,
                category_sku: categorySku,
                product_sku: productSku
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || '<?php echo esc_js(__('Error removing mapping', 'gift-i-card')); ?>');
                }
            }).fail(function() {
                alert('<?php echo esc_js(__('Error removing mapping', 'gift-i-card')); ?>');
            }).always(function() {
                $button.prop('disabled', false);
            });
        });
    });
</script>