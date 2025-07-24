/**
 * Gift-i-Card Public JavaScript
 * Handles interactive functionality for gift card display
 */

jQuery(document).ready(function ($) {

    // Manual order creation
    $('.gicapi-create-order-manually').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $loading = $button.siblings('.gicapi-loading');
        var orderId = $button.data('order-id');
        var itemId = $button.data('item-id');
        var nonce = $button.data('nonce');

        // Show loading state
        $button.prop('disabled', true);
        $loading.show();

        // Make AJAX request
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
                    // Show success message
                    $button.closest('.gicapi-gift-card-details').append(
                        '<div class="notice notice-success"><p>' + response.data + '</p></div>'
                    );

                    // Reload the page after a short delay to show updated information
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error message
                    $button.closest('.gicapi-gift-card-details').append(
                        '<div class="notice notice-error"><p>' + (response.data || 'An error occurred') + '</p></div>'
                    );
                }
            },
            error: function (xhr, status, error) {
                // Show error message
                $button.closest('.gicapi-gift-card-details').append(
                    '<div class="notice notice-error"><p>Network error occurred</p></div>'
                );
            },
            complete: function () {
                // Hide loading state
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });

    // Manual order confirmation
    $('.gicapi-confirm-order-manually').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $loading = $button.siblings('.gicapi-loading');
        var orderId = $button.data('order-id');
        var nonce = $button.data('nonce');

        // Show loading state
        $button.prop('disabled', true);
        $loading.show();

        // Make AJAX request
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
                    // Show success message
                    $button.closest('.gicapi-gift-card-details').append(
                        '<div class="notice notice-success"><p>' + response.data + '</p></div>'
                    );

                    // Reload the page after a short delay to show updated information
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error message
                    $button.closest('.gicapi-gift-card-details').append(
                        '<div class="notice notice-error"><p>' + (response.data || 'An error occurred') + '</p></div>'
                    );
                }
            },
            error: function (xhr, status, error) {
                // Show error message
                $button.closest('.gicapi-gift-card-details').append(
                    '<div class="notice notice-error"><p>Network error occurred</p></div>'
                );
            },
            complete: function () {
                // Hide loading state
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });

    // Manual status update
    $('.gicapi-update-status-manually').on('click', function (e) {
        e.preventDefault();

        var $button = $(this);
        var $loading = $button.siblings('.gicapi-loading');
        var orderId = $button.data('order-id');
        var nonce = $button.data('nonce');

        // Show loading state
        $button.prop('disabled', true);
        $loading.show();

        // Make AJAX request
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
                    // Show success message
                    $button.closest('.gicapi-gift-card-details').append(
                        '<div class="notice notice-success"><p>' + response.data + '</p></div>'
                    );

                    // Reload the page after a short delay to show updated information
                    setTimeout(function () {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error message
                    $button.closest('.gicapi-gift-card-details').append(
                        '<div class="notice notice-error"><p>' + (response.data || 'An error occurred') + '</p></div>'
                    );
                }
            },
            error: function (xhr, status, error) {
                // Show error message
                $button.closest('.gicapi-gift-card-details').append(
                    '<div class="notice notice-error"><p>Network error occurred</p></div>'
                );
            },
            complete: function () {
                // Hide loading state
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    });

    // Toggle redemption details visibility
    $('.gicapi-toggle-details').on('click', function (e) {
        e.preventDefault();

        var $details = $(this).closest('.gicapi-gift-card-details').find('.gicapi-redemption-details');
        var $button = $(this);

        if ($details.is(':visible')) {
            $details.slideUp();
            $button.text('Show Details');
        } else {
            $details.slideDown();
            $button.text('Hide Details');
        }
    });

    // Print gift card information
    $('.gicapi-print-info').on('click', function (e) {
        e.preventDefault();

        var $container = $(this).closest('.gicapi-gift-card-summary, .gicapi-item-gift-card-info');

        // Create print window
        var printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Gift Card Information</title>');
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; }');
        printWindow.document.write('table { border-collapse: collapse; width: 100%; margin: 10px 0; }');
        printWindow.document.write('th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }');
        printWindow.document.write('th { background-color: #f2f2f2; }');
        printWindow.document.write('h2, h3, h4 { color: #333; }');
        printWindow.document.write('.button { display: none; }');
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write($container.html());
        printWindow.document.write('</body></html>');
        printWindow.document.close();

        printWindow.print();
    });

    // Initialize tooltips for better UX
    if ($.fn.tooltip) {
        $('[data-tooltip]').tooltip({
            position: { my: 'left+5 center', at: 'right center' }
        });
    }

    // Auto-refresh redemption data (if needed)
    function refreshRedemptionData() {
        // This function can be used to periodically refresh redemption data
        // Implementation depends on specific requirements
    }

    // Export gift card data to CSV
    $('.gicapi-export-csv').on('click', function (e) {
        e.preventDefault();

        var $table = $(this).closest('.gicapi-gift-card-summary').find('table');
        var csv = [];

        // Get headers
        var headers = [];
        $table.find('thead th').each(function () {
            headers.push($(this).text().trim());
        });
        csv.push(headers.join(','));

        // Get data rows
        $table.find('tbody tr').each(function () {
            var row = [];
            $(this).find('td').each(function () {
                var cellText = $(this).text().trim();
                // Escape commas and quotes
                if (cellText.indexOf(',') !== -1 || cellText.indexOf('"') !== -1) {
                    cellText = '"' + cellText.replace(/"/g, '""') + '"';
                }
                row.push(cellText);
            });
            csv.push(row.join(','));
        });

        // Download CSV file
        var csvContent = csv.join('\n');
        var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        var url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'gift_card_data.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Copy functionality for gift card data
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.gicapi-copy-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var text = this.getAttribute('data-copy');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                } else {
                    var textarea = document.createElement('textarea');
                    textarea.value = text;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                }
                var old = this.innerHTML;
                this.innerHTML = '<span style="font-size:11px;">✔</span>';
                setTimeout(() => {
                    this.innerHTML = old;
                }, 1200);
            });
        });
    });

}); 