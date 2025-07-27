/**
 * Gift-i-Card Public JavaScript
 * Handles interactive functionality for gift card display
 */

jQuery(document).ready(function ($) {

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

}); 