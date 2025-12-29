=== Gift-i-Card ===
Contributors: saeidksr
Tags: woocommerce, gift card, gifticard
Requires at least: 5.2
Tested up to: 6.8
Requires PHP: 7.0
WC requires at least: 5.0
WC tested up to: 8.0
Stable tag: 1.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Seamlessly integrate WooCommerce with Gift-i-Card service to automate gift card ordering, real-time status tracking, and instant customer delivery.

== Description ==
Gift-i-Card seamlessly connects WooCommerce with the Gifticard.pro service, enabling global gift card sales and automated fulfillment.

**Main Features and Operations:**
- **Support for Simple and Variable WooCommerce Products:**
  - The plugin fully supports mapping both simple and variable WooCommerce products (including product variations) to Gift-i-Card variants.
- **Connection Status & Real-Time Wallet Balance:**
  - Admins can view the current connection status to the Gift-i-Card API and see the real-time balance of their Gift-i-Card wallet directly from the plugin dashboard.
- **Product Mapping:**
  - Map each WooCommerce product or variation to a specific Gift-i-Card variant using the admin panel.
  - Mapping is managed via AJAX and stored in product meta fields, allowing you to sell exact gift card types from your WooCommerce catalog.
  - The mapping interface supports searching WooCommerce products and linking them to Gift-i-Card variants.
- **Product Status Synchronization:**
  - Automatically synchronize Gift-i-Card product availability status with WooCommerce stock status.
  - Configurable status mapping for different Gift-i-Card delivery types (Instant, Manual, Out of Stock, Deleted/Not Available).
  - Batch processing system for efficient handling of large product catalogs (configurable batch size).
  - Scheduled synchronization via WordPress cron jobs (recommended: 2-3 times daily).
  - Manual synchronization option for immediate updates.
  - Real-time status display showing next execution time, current sync status, and batch progress.
  - Automatic stock status updates based on Gift-i-Card inventory changes.
  - Progress tracking with visual progress bar for batch processing status.
- **Automatic Gift Card Order Placement:**
  - When a customer places an order containing mapped products, the plugin automatically sends a purchase request to the Gift-i-Card API for each mapped item.
  - The plugin handles the API response, stores the Gift-i-Card order details (including codes) in the WooCommerce order meta, and adds order notes for tracking.
- **Order Status Automation:**
  - The plugin monitors the status of Gift-i-Card orders (via API and webhook).
  - If all mapped items are successfully fulfilled, the WooCommerce order status is automatically set to “completed.”
  - If any mapped item fails, the order status can be set to “failed” (configurable).
  - Status updates are handled both by scheduled checks (cron) and real-time webhook notifications from Gift-i-Card.
  - The plugin can automatically set WooCommerce order status to "cancelled" when required (auto-cancellation).
- **Display of Purchased Gift Cards:**
  - The purchased gift card codes and related information (serial, card code, redeem link, expiration date, etc.) are displayed to the customer:
    - In the order confirmation email
    - On the order details page in the user account
    - On the “Thank You” page after payment
  - The display is dynamic and only shown for items mapped to Gift-i-Card variants.
- **Admin Sync and Management:**
  - Admins can sync categories, products, and variants from the Gift-i-Card API to WordPress for easier mapping and management.
  - There are tools for bulk deletion of plugin data and for manual order management.

**Compatibility:**
- Fully compatible with both HPOS (High-Performance Order Storage) and the legacy WooCommerce order storage system

== Installation ==
**Setup Requirements:**
- To start using the plugin, you need to enter your `base_url`, `consumer_key`, and `consumer_secret` in the plugin settings.
- For production, get your credentials from [gifticard.pro](https://gifticard.pro) (international users) or [gifticard.ir](https://gifticard.ir) (Persian users).
- You can also obtain a Sandbox token for testing and development.

1. Upload the plugin files to the `/wp-content/plugins/gift-i-card` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Gift-i-Card menu to configure the plugin.
4. Map your WooCommerce products to Gift-i-Card variants as needed.

== Changelog ==
= 1.2.3 =
* Added batch processing system for product synchronization to handle large product catalogs efficiently
* Configurable batch size setting for product sync (default: 10 products per batch)
* Progress tracking system for batch processing with visual progress bar in admin
* Improved performance for stores with many products by processing in smaller chunks
* Enhanced cron job reliability with resumable batch processing
* Added batch processing status display in cron settings panel
* Optimized API calls by grouping products in batches instead of processing all at once

= 1.2.2 =
* Fixed issue with order not being processed when manually created by admin

= 1.2.1 =
* Separated admin order styles and scripts from public assets for better performance
* Created dedicated CSS and JS files for order admin functionality (gicapi-order-admin.css, gicapi-order-admin.js)
* Improved asset loading efficiency by loading order-specific styles only on WooCommerce order edit pages
* Enhanced code organization and maintainability
* Fixed potential conflicts between public and admin assets

= 1.2.0 =
* Fixed jQuery tooltip functionality error in public JavaScript
* Added proper jQuery UI dependencies for tooltip support
* Enhanced JavaScript error handling for better user experience
* Improved CSS loading with jQuery UI styles

= 1.1.6 =
* Various bug fixes and performance improvements
* Enhanced error handling and logging
* Improved plugin stability and reliability

= 1.1.5 =
* Added support for new languages (Arabic, Spanish, Russian, Persian)
* Improved internationalization and localization
* Enhanced user experience with better language support

== Upgrade Notice ==
= 1.2.3 =
This version introduces batch processing for product synchronization, significantly improving performance for stores with large product catalogs. The system now processes products in configurable batches, preventing timeouts and memory issues. Upgrade recommended for stores with 50+ products.

= 1.2.2 =
This version fixes an important issue where orders manually created by administrators were not being processed correctly, ensuring proper order handling for all order creation methods.

= 1.2.1 =
This version improves performance and code organization by separating admin order styles and scripts from public assets, ensuring better resource management and faster page loading.

= 1.2.0 =
This version fixes the jQuery tooltip error and improves JavaScript functionality by adding proper jQuery UI dependencies and enhanced error handling.

= 1.1.6 =
This version includes various bug fixes and performance improvements, enhancing overall plugin stability and reliability.

= 1.1.5 =
This version adds comprehensive multi-language support including Arabic, Spanish, Russian, and Persian translations, improving the plugin's global accessibility.