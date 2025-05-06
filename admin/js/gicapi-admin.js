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
}); 