=== Gift-i-Card ===
Contributors: saeidkasiri
Tags: woocommerce, gift card, gifticard
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.0
WC requires at least: 5.0
WC tested up to: 8.0
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate WooCommerce with Gifticard.ir service for automatic gift card order placement, status tracking, and customer delivery.

== Description ==
Gift-i-Card integrates WooCommerce with the Gifticard.ir service for international users.

**Main Features and Operations:**
- **Support for Simple and Variable WooCommerce Products:**
  - The plugin fully supports mapping both simple and variable WooCommerce products (including product variations) to Gift-i-Card variants.
- **Connection Status & Real-Time Wallet Balance:**
  - Admins can view the current connection status to the Gift-i-Card API and see the real-time balance of their Gift-i-Card wallet directly from the plugin dashboard.
- **Product Mapping:**
  - Map each WooCommerce product or variation to a specific Gift-i-Card variant using the admin panel.
  - Mapping is managed via AJAX and stored in product meta fields, allowing you to sell exact gift card types from your WooCommerce catalog.
  - The mapping interface supports searching WooCommerce products and linking them to Gift-i-Card variants.
- **Automatic Gift Card Order Placement:**
  - When a customer places an order containing mapped products, the plugin automatically sends a purchase request to the Gift-i-Card API for each mapped item.
  - The plugin handles the API response, stores the Gift-i-Card order details (including codes) in the WooCommerce order meta, and adds order notes for tracking.
- **Order Status Automation:**
  - The plugin monitors the status of Gift-i-Card orders (via API and webhook).
  - If all mapped items are successfully fulfilled, the WooCommerce order status is automatically set to “completed.”
  - If any mapped item fails, the order status can be set to “failed” (configurable).
  - Status updates are handled both by scheduled checks (cron) and real-time webhook notifications from Gift-i-Card.
- **Display of Purchased Gift Cards:**
  - The purchased gift card codes and related information (serial, card code, redeem link, expiration date, etc.) are displayed to the customer:
    - In the order confirmation email
    - On the order details page in the user account
    - On the “Thank You” page after payment
  - The display is dynamic and only shown for items mapped to Gift-i-Card variants.
- **Admin Sync and Management:**
  - Admins can sync categories, products, and variants from the Gift-i-Card API to WordPress for easier mapping and management.
  - There are tools for bulk deletion of plugin data and for manual order management.

== Installation ==
**Setup Requirements:**
- To start using the plugin, you need to enter your `base_url`, `consumer_key`, and `consumer_secret` in the plugin settings.
- For production, get your credentials from [gifticard.ir](https://gifticard.ir).
- You can also obtain a Sandbox token for testing and development.

1. Upload the plugin files to the `/wp-content/plugins/gift-i-card` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Gift-i-Card menu to configure the plugin.
4. Map your WooCommerce products to Gift-i-Card variants as needed.

== Changelog ==
= 1.0.0 =
* Initial release

== Upgrade Notice ==
= 1.0.0 =
First public release. 