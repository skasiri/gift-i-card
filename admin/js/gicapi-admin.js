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
        // Prevent body scroll when modal is open
        $('body').css('overflow', 'hidden');
    });

    // Close modal
    $('.gicapi-modal-close, #close-modal').on('click', function () {
        $('#gicapi-mapping-modal').hide();
        $('.wc-product-search').val(null).trigger('change');
        // Restore body scroll when modal is closed
        $('body').css('overflow', '');
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
        $('#create-product-status').val('draft');

        // Update mapping info display
        $('#mapping-category-sku').text(categorySku);
        $('#mapping-product-sku').text(productSku);
        $('#mapping-variant-sku').text(variantSku);
        $('#mapping-variant-value').text(value);

        $('#gicapi-create-product-modal').show();
        // Prevent body scroll when modal is open
        $('body').css('overflow', 'hidden');
    });

    // Close create product modal
    $('.gicapi-create-product-modal-close, #close-create-product-modal').on('click', function () {
        $('#gicapi-create-product-modal').hide();
        // Restore body scroll when modal is closed
        $('body').css('overflow', '');
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

        var priceSyncEnabled = $('#create-product-price-sync-enabled').is(':checked') ? 'yes' : 'no';
        var priceSyncMargin = $('#create-product-price-sync-margin').val() || 0;
        var priceSyncMarginType = $('#create-product-price-sync-margin-type').val() || 'percentage';

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
            product_status: status,
            price_sync_enabled: priceSyncEnabled,
            price_sync_margin: priceSyncMargin,
            price_sync_margin_type: priceSyncMarginType
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

    // Open create variable product modal
    $('.gicapi-create-variable-product').on('click', function () {
        var $button = $(this);
        var categorySku = $button.data('category-sku');
        var productSku = $button.data('product-sku');
        var productName = $button.data('product-name');

        // Populate modal fields
        $('#create-variable-product-category-sku').val(categorySku);
        $('#create-variable-product-product-sku').val(productSku);
        $('#create-variable-product-name').val(productName);
        $('#create-variable-product-sku').val('');
        $('#create-variable-product-status').val('draft');
        $('#create-variable-product-attribute-name').val('Variant Value');

        // Update mapping info display
        $('#variable-mapping-category-sku').text(categorySku);
        $('#variable-mapping-product-sku').text(productSku);

        // Store product name in a data attribute for use in variant loading
        $('#create-variable-product-modal').data('product-name', productName);

        // Load variants
        loadVariantsForVariableProduct(categorySku, productSku);

        $('#gicapi-create-variable-product-modal').show();
        // Prevent body scroll when modal is open
        $('body').css('overflow', 'hidden');
    });

    // Close create variable product modal
    $('.gicapi-create-variable-product-modal-close, #close-create-variable-product-modal').on('click', function () {
        $('#gicapi-create-variable-product-modal').hide();
        // Restore body scroll when modal is closed
        $('body').css('overflow', '');
    });

    // Create variable product from modal
    $('#create-variable-product-confirm').on('click', function () {
        var $button = $(this);
        var productName = $('#create-variable-product-name').val().trim();
        var productSku = $('#create-variable-product-sku').val().trim();
        var status = $('#create-variable-product-status').val();
        var attributeName = $('#create-variable-product-attribute-name').val().trim() || 'Variant Value';

        var categorySku = $('#create-variable-product-category-sku').val();
        var productSkuApi = $('#create-variable-product-product-sku').val();

        // Get selected variants with their edited details
        var selectedVariants = [];
        $('.variant-checkbox:checked').each(function () {
            var variantSku = $(this).val();
            var safeId = variantSku.replace(/[^a-zA-Z0-9]/g, '_');
            var variantData = {
                sku: variantSku,
                name: $('#variant-name-' + safeId).val().trim(),
                price: $('#variant-price-' + safeId).val(),
                value: $('#variant-value-' + safeId).val().trim(),
                variation_sku: $('#variant-variation-sku-' + safeId).val().trim()
            };
            selectedVariants.push(variantData);
        });

        // Validation
        if (!productName) {
            alert(gicapi_admin_params.text_product_name_required || 'Product name is required');
            $('#create-variable-product-name').focus();
            return;
        }

        if (selectedVariants.length === 0) {
            alert(gicapi_admin_params.text_at_least_one_variant || 'At least one variant must be selected');
            return;
        }

        // Validate variant details
        var hasError = false;
        selectedVariants.forEach(function (variant) {
            if (!variant.name) {
                alert(gicapi_admin_params.text_variant_name_required || 'Variant name is required for all selected variants');
                hasError = true;
                return false;
            }
            if (!variant.price || variant.price < 0) {
                alert(gicapi_admin_params.text_variant_price_required || 'Valid variant price is required for all selected variants');
                hasError = true;
                return false;
            }
        });

        if (hasError) {
            return;
        }

        // Check SKU uniqueness if provided
        if (productSku && productSku.length > 0) {
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
                        alert(response.data || 'Product SKU already exists');
                        $('#create-variable-product-sku').focus();
                        skuValid = false;
                    } else {
                        skuValid = true;
                    }
                },
                error: function () {
                    alert('Error checking product SKU uniqueness');
                    skuValid = false;
                }
            });

            if (!skuValid) {
                return;
            }
        }

        $button.prop('disabled', true);
        $('.spinner').show();

        var priceSyncEnabled = $('#create-variable-product-price-sync-enabled').is(':checked') ? 'yes' : 'no';
        var priceSyncMargin = $('#create-variable-product-price-sync-margin').val() || 0;
        var priceSyncMarginType = $('#create-variable-product-price-sync-margin-type').val() || 'percentage';

        $.post(ajaxurl, {
            action: 'gicapi_create_variable_product',
            nonce: gicapi_admin_params.create_variable_product_nonce,
            category_sku: categorySku,
            product_sku: productSkuApi,
            product_name: productName,
            product_sku_field: productSku,
            product_status: status,
            attribute_name: attributeName,
            selected_variants: JSON.stringify(selectedVariants),
            price_sync_enabled: priceSyncEnabled,
            price_sync_margin: priceSyncMargin,
            price_sync_margin_type: priceSyncMarginType
        }, function (response) {
            if (response.success) {
                $('#gicapi-create-variable-product-modal').hide();
                location.reload();
            } else {
                alert(response.data || (gicapi_admin_params.text_error_creating_variable_product || 'Error creating variable product'));
            }
        }).fail(function () {
            alert(gicapi_admin_params.text_error_creating_variable_product || 'Error creating variable product');
        }).always(function () {
            $button.prop('disabled', false);
            $('.spinner').hide();
        });
    });

    // Function to load variants for variable product modal
    function loadVariantsForVariableProduct(categorySku, productSku) {
        $('#variants-selection-container').html('<p>' + (gicapi_admin_params.text_loading_variants || 'Loading variants...') + '</p>');

        // Get product name from modal data attribute
        var productName = $('#create-variable-product-modal').data('product-name') || '';

        $.post(ajaxurl, {
            action: 'gicapi_get_variants_for_variable_product',
            nonce: gicapi_admin_params.get_variants_for_variable_product_nonce,
            category_sku: categorySku,
            product_sku: productSku
        }, function (response) {
            if (response.success && response.data.variants) {
                var html = '<div class="variants-list">';
                html += '<div class="variant-header">';
                html += '<label><input type="checkbox" id="select-all-variants" checked> ' + (gicapi_admin_params.text_select_all || 'Select All') + '</label>';
                html += '</div>';

                response.data.variants.forEach(function (variant) {
                    var safeId = variant.sku.replace(/[^a-zA-Z0-9]/g, '_');

                    // Remove product name from variant name
                    var variantName = variant.name || '';
                    if (productName && variantName) {
                        // Escape special regex characters in product name
                        var escapedProductName = productName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        // Try to remove product name with various separators
                        var patterns = [
                            new RegExp('^' + escapedProductName + '\\s*-\\s*', 'i'),  // "Product - "
                            new RegExp('^' + escapedProductName + '\\s+', 'i'),        // "Product "
                            new RegExp('^' + escapedProductName + '$', 'i')            // "Product" (exact match)
                        ];

                        var found = false;
                        for (var i = 0; i < patterns.length; i++) {
                            if (patterns[i].test(variantName)) {
                                variantName = variantName.replace(patterns[i], '').trim();
                                found = true;
                                break;
                            }
                        }

                        // If nothing left or pattern didn't match, use original name
                        if (!variantName || !found) {
                            variantName = variant.name;
                        }
                    }

                    html += '<div class="variant-item">';
                    html += '<div class="variant-checkbox">';
                    html += '<label><input type="checkbox" class="variant-checkbox" value="' + variant.sku + '" checked> ' + variant.name + '</label>';
                    html += '</div>';
                    html += '<div class="variant-details">';
                    html += '<div class="variant-field">';
                    html += '<label>' + (gicapi_admin_params.text_variant_name || 'Name:') + '</label>';
                    html += '<input type="text" id="variant-name-' + safeId + '" value="' + variantName + '" class="regular-text">';
                    html += '</div>';
                    html += '<div class="variant-field">';
                    html += '<label>' + (gicapi_admin_params.text_variant_price || 'Price:') + '</label>';
                    html += '<input type="number" id="variant-price-' + safeId + '" value="' + (variant.price || '') + '" step="0.01" min="0" class="regular-text">';
                    html += '</div>';
                    html += '<div class="variant-field">';
                    html += '<label>' + (gicapi_admin_params.text_variant_value || 'Value:') + '</label>';
                    html += '<input type="text" id="variant-value-' + safeId + '" value="' + (variant.value || '') + '" class="regular-text">';
                    html += '</div>';
                    html += '<div class="variant-field">';
                    html += '<label>' + (gicapi_admin_params.text_variation_sku || 'Variation SKU:') + '</label>';
                    html += '<input type="text" id="variant-variation-sku-' + safeId + '" value="' + variant.sku + '_var" placeholder="' + (gicapi_admin_params.text_placeholder_variation_sku || 'e.g., VAR-001') + '" class="regular-text">';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                });

                html += '</div>';
                $('#variants-selection-container').html(html);

                // Handle select all checkbox
                $('#select-all-variants').on('change', function () {
                    $('.variant-checkbox').prop('checked', $(this).is(':checked'));
                });

                // Handle individual checkboxes
                $('.variant-checkbox').on('change', function () {
                    var allChecked = $('.variant-checkbox:checked').length === $('.variant-checkbox').length;
                    $('#select-all-variants').prop('checked', allChecked);
                });
            } else {
                $('#variants-selection-container').html('<p>' + (gicapi_admin_params.text_error_loading_variants || 'Error loading variants.') + '</p>');
            }
        }).fail(function () {
            $('#variants-selection-container').html('<p>' + (gicapi_admin_params.text_error_loading_variants || 'Error loading variants.') + '</p>');
        });
    }

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

    // Price sync functionality
    // Product price sync toggle (for each mapped product)
    $(document).on('change', '.gicapi-product-price-sync-toggle-input', function () {
        var $toggle = $(this);
        var productId = $toggle.data('product-id');
        var variantSku = $toggle.data('variant-sku');
        var enabled = $toggle.is(':checked') ? 'yes' : 'no';

        // Get current settings from the customize button (or use defaults)
        var $customizeButton = $('.gicapi-customize-product-price-sync[data-product-id="' + productId + '"]');
        var currentMargin = $customizeButton.data('profit-margin');
        var currentMarginType = $customizeButton.data('profit-margin-type');

        // If product doesn't have explicit settings, use global defaults
        if (currentMargin === undefined || currentMargin === '') {
            currentMargin = 0; // Will be set from global settings on server side
        }
        if (currentMarginType === undefined || currentMarginType === '') {
            currentMarginType = 'percentage'; // Will be set from global settings on server side
        }

        $.post(ajaxurl, {
            action: 'gicapi_save_product_price_sync',
            nonce: gicapi_admin_params.save_product_price_sync_nonce,
            product_id: productId,
            variant_sku: variantSku,
            enabled: enabled,
            profit_margin: currentMargin,
            profit_margin_type: currentMarginType
        }, function (response) {
            if (!response.success) {
                alert(response.data || 'Error saving price sync settings');
                $toggle.prop('checked', !$toggle.is(':checked'));
            } else {
                // Update the customize button data attributes
                $customizeButton.data('price-sync-enabled', enabled);
            }
        }).fail(function () {
            alert('Error saving price sync settings');
            $toggle.prop('checked', !$toggle.is(':checked'));
        });
    });

    // Stock sync functionality
    // Product stock sync toggle (for each mapped product)
    $(document).on('change', '.gicapi-product-stock-sync-toggle-input', function () {
        var $toggle = $(this);
        var productId = $toggle.data('product-id');
        var variantSku = $toggle.data('variant-sku');
        var enabled = $toggle.is(':checked') ? 'yes' : 'no';

        $.post(ajaxurl, {
            action: 'gicapi_save_product_stock_sync',
            nonce: gicapi_admin_params.save_product_stock_sync_nonce,
            product_id: productId,
            variant_sku: variantSku,
            enabled: enabled
        }, function (response) {
            if (!response.success) {
                alert(response.data || 'Error saving stock sync settings');
                $toggle.prop('checked', !$toggle.is(':checked'));
            }
        }).fail(function () {
            alert('Error saving stock sync settings');
            $toggle.prop('checked', !$toggle.is(':checked'));
        });
    });

    // Open product price sync customization modal (using event delegation for dynamic elements)
    $(document).on('click', '.gicapi-customize-product-price-sync', function () {
        var productId = $(this).data('product-id');
        var variantSku = $(this).data('variant-sku');
        var enabled = $(this).data('price-sync-enabled');
        var margin = $(this).data('profit-margin');
        var marginType = $(this).data('profit-margin-type');

        $('#product-price-sync-product-id').val(productId);
        $('#product-price-sync-variant-sku').val(variantSku);
        $('#product-price-sync-enabled').prop('checked', enabled === 'yes');
        $('#product-price-sync-margin-type').val(marginType);
        $('#product-price-sync-margin').val(margin);

        $('#gicapi-product-price-sync-modal').show();
        $('body').css('overflow', 'hidden');
    });

    // Close product price sync modal
    $('#close-product-price-sync-modal, #gicapi-product-price-sync-modal .gicapi-modal-close').on('click', function () {
        $('#gicapi-product-price-sync-modal').hide();
        $('body').css('overflow', '');
    });

    // Save product price sync settings
    $('#save-product-price-sync').on('click', function () {
        var $button = $(this);
        var productId = $('#product-price-sync-product-id').val();
        var variantSku = $('#product-price-sync-variant-sku').val();
        var enabled = $('#product-price-sync-enabled').is(':checked') ? 'yes' : 'no';
        var margin = $('#product-price-sync-margin').val();
        var marginType = $('#product-price-sync-margin-type').val();

        $button.prop('disabled', true);
        $('.spinner').show();

        $.post(ajaxurl, {
            action: 'gicapi_save_product_price_sync',
            nonce: gicapi_admin_params.save_product_price_sync_nonce,
            product_id: productId,
            variant_sku: variantSku,
            enabled: enabled,
            profit_margin: margin,
            profit_margin_type: marginType
        }, function (response) {
            if (response.success) {
                $('#gicapi-product-price-sync-modal').hide();
                $('body').css('overflow', '');
                location.reload();
            } else {
                alert(response.data || 'Error saving price sync settings');
            }
        }).fail(function () {
            alert('Error saving price sync settings');
        }).always(function () {
            $button.prop('disabled', false);
            $('.spinner').hide();
        });
    });

    // Toggle price sync settings visibility in create product modals
    $('#create-product-price-sync-enabled, #create-variable-product-price-sync-enabled').on('change', function () {
        var $checkbox = $(this);
        var isChecked = $checkbox.is(':checked');
        var $settingsRow = $checkbox.closest('tr').next('#create-product-price-sync-settings, #create-variable-product-price-sync-settings');
        var $marginRow = $settingsRow.next('#create-product-price-sync-margin-row, #create-variable-product-price-sync-margin-row');

        if (isChecked) {
            $settingsRow.show();
            $marginRow.show();
        } else {
            $settingsRow.hide();
            $marginRow.hide();
        }
    });

    // Initialize price sync settings visibility
    $('#create-product-price-sync-enabled, #create-variable-product-price-sync-enabled').trigger('change');
}); 