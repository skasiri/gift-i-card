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

    // Sync Categories
    $('#gicapi-sync-categories').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        $button.prop('disabled', true);

        $.ajax({
            url: gicapi_admin.ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_sync_categories',
                _ajax_nonce: gicapi_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert('دسته‌بندی‌ها با موفقیت به‌روزرسانی شدند');
                    location.reload();
                } else {
                    alert('خطا در به‌روزرسانی دسته‌بندی‌ها: ' + response.data.message);
                }
            },
            error: function (xhr, status, error) {
                if (xhr.status === 403) {
                    alert('دسترسی غیرمجاز. لطفا دوباره وارد شوید.');
                } else {
                    alert('خطا در ارتباط با سرور: ' + error);
                }
            },
            complete: function () {
                $button.prop('disabled', false);
            }
        });
    });

    // Sync Products
    $('#gicapi-sync-products').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        $button.prop('disabled', true);

        $.ajax({
            url: gicapi_admin.ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_sync_products',
                _ajax_nonce: gicapi_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert('محصولات با موفقیت به‌روزرسانی شدند');
                    location.reload();
                } else {
                    alert('خطا در به‌روزرسانی محصولات: ' + response.data.message);
                }
            },
            error: function (xhr, status, error) {
                if (xhr.status === 403) {
                    alert('دسترسی غیرمجاز. لطفا دوباره وارد شوید.');
                } else {
                    alert('خطا در ارتباط با سرور: ' + error);
                }
            },
            complete: function () {
                $button.prop('disabled', false);
            }
        });
    });

    // Map Product Submit
    $('#gicapi-map-submit').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $form = $('#gicapi-map-form');
        var $dialog = $('#gicapi-map-dialog');
        var $overlay = $('#gicapi-dialog-overlay');

        $button.prop('disabled', true);

        $.ajax({
            url: gicapi_admin.ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_map_product',
                _ajax_nonce: gicapi_admin.nonce,
                product_id: $form.find('input[name="product_id"]').val(),
                woocommerce_product: $form.find('select[name="woocommerce_product"]').val()
            },
            success: function (response) {
                if (response.success) {
                    alert('محصول با موفقیت مرتبط شد');
                    location.reload();
                } else {
                    alert('خطا در مرتبط کردن محصول: ' + response.data.message);
                }
            },
            error: function (xhr, status, error) {
                if (xhr.status === 403) {
                    alert('دسترسی غیرمجاز. لطفا دوباره وارد شوید.');
                } else {
                    alert('خطا در ارتباط با سرور: ' + error);
                }
            },
            complete: function () {
                $button.prop('disabled', false);
                $dialog.hide();
                $overlay.hide();
            }
        });
    });

    // Force Refresh Token Button
    $('#gicapi-force-refresh-token-button').on('click', function () {
        var $button = $(this);
        var $messageDiv = $('#gicapi-refresh-token-message');

        // Use localized text
        $messageDiv.html('<span class="spinner is-active" style="float: none; vertical-align: middle; margin-left: 5px;"></span> ' + gicapi_admin_params.text_refreshing_token);
        $button.prop('disabled', true);

        $.ajax({
            url: gicapi_admin_params.ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_force_refresh_token',
                _ajax_nonce: gicapi_admin_params.force_refresh_token_nonce
            },
            success: function (response) {
                if (response.success) {
                    $messageDiv.html('<p style="color: green;">' + response.data.message + '</p>');
                    // Consider briefly showing success then clearing, or instructing user to save settings if applicable
                    // Example: Refresh page to see updated status if connection notice changes
                    // setTimeout(function(){ location.reload(); }, 2000); 
                } else {
                    var errorMessage = (response.data && response.data.message) ? response.data.message : gicapi_admin_params.text_error_unknown;
                    $messageDiv.html('<p style="color: red;">' + errorMessage + '</p>');
                }
            },
            error: function (xhr, status, error) {
                var serverError = gicapi_admin_params.text_error_server_communication + error;
                $messageDiv.html('<p style="color: red;">' + serverError + '</p>');
            },
            complete: function () {
                $button.prop('disabled', false);
                // Remove spinner after a short delay to ensure message is visible
                setTimeout(function () {
                    $messageDiv.find('.spinner').remove();
                    // Optionally clear the message after a few more seconds
                    // setTimeout(function() { $messageDiv.html(''); }, 5000);
                }, 1000);
            }
        });
    });

    $('#gicapi-delete-all-data').on('click', function (e) {
        e.preventDefault();

        if (!confirm('آیا مطمئن هستید که می‌خواهید تمام داده‌های کارت هدیه را حذف کنید؟ این عمل غیرقابل بازگشت است!')) {
            return;
        }

        const button = $(this);
        const messageDiv = $('#gicapi-delete-data-message');

        button.prop('disabled', true);
        messageDiv.html('<p style="color: #666;">در حال حذف داده‌ها...</p>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_delete_all_data',
                nonce: gicapi_admin.nonce
            },
            success: function (response) {
                if (response.success) {
                    messageDiv.html('<p style="color: green;">' + response.data.message + '</p>');
                } else {
                    messageDiv.html('<p style="color: red;">' + response.data.message + '</p>');
                }
            },
            error: function () {
                messageDiv.html('<p style="color: red;">خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.</p>');
            },
            complete: function () {
                button.prop('disabled', false);
            }
        });
    });

}); 