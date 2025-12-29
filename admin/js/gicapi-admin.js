jQuery(document).ready(function ($) {
    // Category Filter
    $('#gicapi-category-filter').on('change', function () {
        var category = $(this).val();
        window.location.href = window.location.pathname + '?page=gicapi-products&category=' + category;
    });

    // Map Product Dialog
    $('.gicapi-map-product').on('click', function (e) {
        e.preventDefault();

        var $dialog = $('#gicapi-map-dialog');
        var $overlay = $('#gicapi-dialog-overlay');
        var productId = $(this).data('product-id');

        $dialog.find('input[name="product_id"]').val(productId);
        $dialog.find('select[name="woocommerce_product"]').val('');

        $overlay.show();
        $dialog.show();
    });

    // Close Dialog
    $('.gicapi-dialog .close, #gicapi-dialog-overlay').on('click', function () {
        $('.gicapi-dialog').hide();
        $('#gicapi-dialog-overlay').hide();
    });



    // Force Refresh Token Button
    $('#gicapi-force-refresh-token-button').on('click', function () {
        var $button = $(this);
        var $messageDiv = $('#gicapi-refresh-token-message');

        // غیرفعال کردن دکمه و نمایش پیام در حال بارگذاری
        $button.prop('disabled', true);
        $messageDiv.html('<div class="notice notice-info inline"><p><span class="spinner is-active" style="float: none; vertical-align: middle; margin-left: 5px;"></span> ' + gicapi_admin_params.text_refreshing_token + '</p></div>');

        $.ajax({
            url: gicapi_admin_params.ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_force_refresh_token',
                _ajax_nonce: gicapi_admin_params.force_refresh_token_nonce
            },
            success: function (response) {
                if (response.success) {
                    $messageDiv.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                    // به‌روزرسانی خودکار صفحه پس از 2 ثانیه
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                } else {
                    var errorMessage = (response.data && response.data.message) ? response.data.message : gicapi_admin_params.text_error_unknown;
                    $messageDiv.html('<div class="notice notice-error inline"><p>' + errorMessage + '</p></div>');
                }
            },
            error: function (xhr, status, error) {
                var serverError = gicapi_admin_params.text_error_server_communication + error;
                $messageDiv.html('<div class="notice notice-error inline"><p>' + serverError + '</p></div>');
            },
            complete: function () {
                $button.prop('disabled', false);
            }
        });
    });



});

// Tab functionality
jQuery(document).ready(function ($) {
    $('.nav-tab').on('click', function (e) {
        e.preventDefault();

        // Remove active class from all tabs
        $('.nav-tab').removeClass('nav-tab-active');

        // Add active class to clicked tab
        $(this).addClass('nav-tab-active');

        // Hide all tab content
        $('.tab-content').hide();

        // Show the selected tab content
        $($(this).attr('href')).show();
    });

    // Show first tab by default
    $('.nav-tab:first').trigger('click');
});

// Variants display functionality
jQuery(document).ready(function ($) {
    // Initialize select2 for product search
    $('.wc-product-search').select2({
        dropdownParent: $('#gicapi-mapping-modal'),
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    action: 'gicapi_search_products',
                    nonce: gicapi_admin_params.search_products_nonce,
                    category_sku: $('#gicapi-mapping-modal').data('category-sku'),
                    product_sku: $('#gicapi-mapping-modal').data('product-sku')
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: gicapi_admin_params.text_search_product || 'Search for a product...',
        width: '100%'
    });

    // Open modal for adding mapping
    $('.gicapi-add-mapping').on('click', function () {
        var variantSku = $(this).data('variant-sku');
        var categorySku = $(this).data('category-sku');
        var productSku = $(this).data('product-sku');
        $('#modal-variant-id').val(variantSku);
        $('#gicapi-mapping-modal').data('category-sku', categorySku).data('product-sku', productSku).show();
        $('.wc-product-search').val(null).trigger('change');
    });

    // Close modal
    $('.gicapi-modal-close, #close-modal').on('click', function () {
        $('#gicapi-mapping-modal').hide();
        $('.wc-product-search').val(null).trigger('change');
    });

    // Save mapping
    $('#save-mapping').on('click', function () {
        var $button = $(this);
        var variantSku = $('#modal-variant-id').val();
        var productId = $('.wc-product-search').val();
        var categorySku = $('#gicapi-mapping-modal').data('category-sku');
        var productSku = $('#gicapi-mapping-modal').data('product-sku');

        if (!productId) {
            alert(gicapi_admin_params.text_select_product || 'Please select a product');
            return;
        }

        $button.prop('disabled', true);
        $('.spinner').show();

        $.post(ajaxurl, {
            action: 'gicapi_add_mapping',
            nonce: gicapi_admin_params.add_mapping_nonce,
            variant_sku: variantSku,
            product_id: productId,
            category_sku: categorySku,
            product_sku: productSku
        }, function (response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || (gicapi_admin_params.text_error_mapping || 'Error adding mapping'));
            }
        }).fail(function () {
            alert(gicapi_admin_params.text_error_mapping || 'Error adding mapping');
        }).always(function () {
            $button.prop('disabled', false);
            $('.spinner').hide();
        });
    });

    // Open create simple product modal
    $('.gicapi-create-simple-product').on('click', function () {
        var $button = $(this);
        var variantSku = $button.data('variant-sku');
        var variantName = $button.data('variant-name');
        var categorySku = $button.data('category-sku');
        var productSku = $button.data('product-sku');
        var price = $button.data('price');
        var value = $button.data('value');

        // Populate modal fields
        $('#create-product-variant-sku').val(variantSku);
        $('#create-product-category-sku').val(categorySku);
        $('#create-product-product-sku').val(productSku);
        $('#create-product-variant-value').val(value);

        $('#create-product-name').val(variantName);
        $('#create-product-sku').val('');
        $('#create-product-price').val(price);
        $('#create-product-status').val('publish');

        // Update mapping info display
        $('#mapping-category-sku').text(categorySku);
        $('#mapping-product-sku').text(productSku);
        $('#mapping-variant-sku').text(variantSku);
        $('#mapping-variant-value').text(value);

        $('#gicapi-create-product-modal').show();
    });

    // Close create product modal
    $('.gicapi-create-product-modal-close, #close-create-product-modal').on('click', function () {
        $('#gicapi-create-product-modal').hide();
    });

    // Create simple product from modal
    $('#create-simple-product').on('click', function () {
        var $button = $(this);
        var productName = $('#create-product-name').val().trim();
        var productSku = $('#create-product-sku').val().trim();
        var price = $('#create-product-price').val();
        var status = $('#create-product-status').val();

        var variantSku = $('#create-product-variant-sku').val();
        var categorySku = $('#create-product-category-sku').val();
        var productSkuApi = $('#create-product-product-sku').val();
        var variantValue = $('#create-product-variant-value').val();

        // Validation
        if (!productName) {
            alert(gicapi_admin_params.text_product_name_required || 'Product name is required');
            $('#create-product-name').focus();
            return;
        }

        if (productSku && productSku.length > 0) {
            // If SKU is provided, check for uniqueness via AJAX
            var skuValid = false;
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                async: false,
                data: {
                    action: 'gicapi_check_sku_uniqueness',
                    nonce: gicapi_admin_params.check_sku_uniqueness_nonce,
                    sku: productSku
                },
                success: function (response) {
                    if (!response.success) {
                        alert(response.data || 'SKU already exists');
                        $('#create-product-sku').focus();
                        skuValid = false;
                    } else {
                        skuValid = true;
                    }
                },
                error: function () {
                    alert('Error checking SKU uniqueness');
                    skuValid = false;
                }
            });

            if (!skuValid) {
                return;
            }
        }

        if (!price || price < 0) {
            alert(gicapi_admin_params.text_product_price_required || 'Valid product price is required');
            $('#create-product-price').focus();
            return;
        }

        $button.prop('disabled', true);
        $('.spinner').show();

        $.post(ajaxurl, {
            action: 'gicapi_create_simple_product',
            nonce: gicapi_admin_params.create_simple_product_nonce,
            variant_sku: variantSku,
            variant_name: productName,
            category_sku: categorySku,
            product_sku: productSkuApi,
            variant_value: variantValue,
            product_name: productName,
            product_sku_field: productSku,
            price: price,
            product_status: status
        }, function (response) {
            if (response.success) {
                $('#gicapi-create-product-modal').hide();
                location.reload();
            } else {
                alert(response.data || (gicapi_admin_params.text_error_creating_product || 'Error creating simple product'));
            }
        }).fail(function () {
            alert(gicapi_admin_params.text_error_creating_product || 'Error creating simple product');
        }).always(function () {
            $button.prop('disabled', false);
            $('.spinner').hide();
        });
    });

    // Remove mapping
    $('.gicapi-remove-mapping').on('click', function () {
        if (!confirm(gicapi_admin_params.text_confirm_remove || 'Are you sure you want to remove this mapping?')) {
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
            nonce: gicapi_admin_params.remove_mapping_nonce,
            variant_sku: variantSku,
            product_id: productId,
            category_sku: categorySku,
            product_sku: productSku
        }, function (response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || (gicapi_admin_params.text_error_removing || 'Error removing mapping'));
            }
        }).fail(function () {
            alert(gicapi_admin_params.text_error_removing || 'Error removing mapping');
        }).always(function () {
            $button.prop('disabled', false);
        });
    });
});

// Cron settings functionality
jQuery(document).ready(function ($) {
    $('#gicapi-manual-update').on('click', function () {
        var button = $(this);
        var resultDiv = $('#gicapi-manual-update-result');

        button.prop('disabled', true).text(gicapi_admin_params.text_updating || 'Updating...');
        resultDiv.html('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_manual_update_orders',
                nonce: gicapi_admin_params.manual_update_orders_nonce
            },
            success: function (response) {
                if (response.success) {
                    resultDiv.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                } else {
                    resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function () {
                resultDiv.html('<div class="notice notice-error"><p>' + (gicapi_admin_params.text_error_updating || 'An error occurred while updating orders.') + '</p></div>');
            },
            complete: function () {
                button.prop('disabled', false).text(gicapi_admin_params.text_update_orders || 'Update Pending/Processing Orders Now');
            }
        });
    });

    $('#gicapi-manual-product-sync').on('click', function () {
        var button = $(this);
        var resultDiv = $('#gicapi-manual-product-sync-result');

        button.prop('disabled', true).text(gicapi_admin_params.text_syncing || 'Syncing...');
        resultDiv.html('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_manual_sync_products',
                nonce: gicapi_admin_params.manual_sync_products_nonce
            },
            success: function (response) {
                if (response.success) {
                    resultDiv.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                } else {
                    resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function () {
                resultDiv.html('<div class="notice notice-error"><p>' + (gicapi_admin_params.text_error_syncing || 'An error occurred while syncing products.') + '</p></div>');
            },
            complete: function () {
                button.prop('disabled', false).text(gicapi_admin_params.text_sync_products || 'Sync All Products Now');
            }
        });
    });

    $('#gicapi-reschedule-cron').on('click', function () {
        var button = $(this);
        var resultDiv = $('#gicapi-debug-result');

        button.prop('disabled', true).text(gicapi_admin_params.text_rescheduling || 'Rescheduling...');
        resultDiv.html('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_reschedule_cron',
                nonce: gicapi_admin_params.reschedule_cron_nonce
            },
            success: function (response) {
                if (response.success) {
                    resultDiv.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    // Reload page after 2 seconds to show updated status
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                } else {
                    resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function () {
                resultDiv.html('<div class="notice notice-error"><p>' + (gicapi_admin_params.text_error_rescheduling || 'An error occurred while rescheduling cron job.') + '</p></div>');
            },
            complete: function () {
                button.prop('disabled', false).text(gicapi_admin_params.text_reschedule_cron || 'Reschedule Cron Job');
            }
        });
    });

    $('#gicapi-check-repair-cron').on('click', function () {
        var button = $(this);
        var resultDiv = $('#gicapi-debug-result');

        button.prop('disabled', true).text(gicapi_admin_params.text_checking || 'Checking...');
        resultDiv.html('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_check_repair_cron',
                nonce: gicapi_admin_params.check_repair_cron_nonce
            },
            success: function (response) {
                if (response.success) {
                    resultDiv.html('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    // Reload page after 2 seconds to show updated status
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                } else {
                    resultDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function () {
                resultDiv.html('<div class="notice notice-error"><p>' + (gicapi_admin_params.text_error_checking || 'An error occurred while checking cron job.') + '</p></div>');
            },
            complete: function () {
                button.prop('disabled', false).text(gicapi_admin_params.text_check_repair_cron || 'Check & Repair Cron Job');
            }
        });
    });
}); 