jQuery(document).ready(function ($) {
    // ایجاد سفارش دستی
    $('.gicapi-create-order-manually').on('click', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $loading = $button.siblings('.gicapi-loading');
        var orderId = $button.data('order-id');
        var itemId = $button.data('item-id');
        var nonce = $button.data('nonce');
        $button.prop('disabled', true);
        $loading.show();
        $.ajax({
            url: (typeof gicapi_ajax !== 'undefined' && gicapi_ajax.ajaxurl) ? gicapi_ajax.ajaxurl : ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_create_order_manually',
                order_id: orderId,
                item_id: itemId,
                nonce: nonce
            },
            success: function (response) {
                if (response.success) {
                    $button.closest('.gicapi-gift-card-details').append('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    setTimeout(function () { location.reload(); }, 2000);
                } else {
                    $button.closest('.gicapi-gift-card-details').append('<div class="notice notice-error"><p>' + (response.data || 'خطا رخ داد') + '</p></div>');
                }
            },
            error: function () {
                $button.closest('.gicapi-gift-card-details').append('<div class="notice notice-error"><p>خطای شبکه رخ داد</p></div>');
            },
            complete: function () {
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });
    // تایید سفارش دستی
    $('.gicapi-confirm-order-manually').on('click', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $loading = $button.siblings('.gicapi-loading');
        var orderId = $button.data('order-id');
        var nonce = $button.data('nonce');
        $button.prop('disabled', true);
        $loading.show();
        $.ajax({
            url: (typeof gicapi_ajax !== 'undefined' && gicapi_ajax.ajaxurl) ? gicapi_ajax.ajaxurl : ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_confirm_order_manually',
                order_id: orderId,
                nonce: nonce
            },
            success: function (response) {
                if (response.success) {
                    $button.closest('.gicapi-gift-card-details').append('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    setTimeout(function () { location.reload(); }, 2000);
                } else {
                    $button.closest('.gicapi-gift-card-details').append('<div class="notice notice-error"><p>' + (response.data || 'خطا رخ داد') + '</p></div>');
                }
            },
            error: function () {
                $button.closest('.gicapi-gift-card-details').append('<div class="notice notice-error"><p>خطای شبکه رخ داد</p></div>');
            },
            complete: function () {
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });
    // به‌روزرسانی وضعیت دستی
    $('.gicapi-update-status-manually').on('click', function (e) {
        e.preventDefault();
        var $button = $(this);
        var $loading = $button.siblings('.gicapi-loading');
        var orderId = $button.data('order-id');
        var nonce = $button.data('nonce');
        $button.prop('disabled', true);
        $loading.show();
        $.ajax({
            url: (typeof gicapi_ajax !== 'undefined' && gicapi_ajax.ajaxurl) ? gicapi_ajax.ajaxurl : ajaxurl,
            type: 'POST',
            data: {
                action: 'gicapi_update_status_manually',
                order_id: orderId,
                nonce: nonce
            },
            success: function (response) {
                if (response.success) {
                    $button.closest('.gicapi-gift-card-details').append('<div class="notice notice-success"><p>' + response.data + '</p></div>');
                    setTimeout(function () { location.reload(); }, 2000);
                } else {
                    $button.closest('.gicapi-gift-card-details').append('<div class="notice notice-error"><p>' + (response.data || 'خطا رخ داد') + '</p></div>');
                }
            },
            error: function () {
                $button.closest('.gicapi-gift-card-details').append('<div class="notice notice-error"><p>خطای شبکه رخ داد</p></div>');
            },
            complete: function () {
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });
}); 