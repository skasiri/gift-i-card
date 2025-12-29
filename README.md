# Gift-i-Card

Gift-i-Card integrates WooCommerce with the Gifticard.ir service for international users, enabling automatic gift card order placement, status tracking, and customer delivery.

**Other Languages:** [فارسی (Persian)](README-fa.md)

## Features

### Support for Simple and Variable WooCommerce Products

- The plugin fully supports mapping both simple and variable WooCommerce products (including product variations) to Gift-i-Card variants.

### Product Creation from Gift-i-Card Variants

- Create WooCommerce simple products directly from Gift-i-Card variants with custom settings.
- Create WooCommerce variable products with multiple variants in one operation.
- Automatic mapping of created products to Gift-i-Card variants.
- Customizable product details (name, SKU, price, status) during creation.

### Price Synchronization

- Automatically synchronize product prices from Gift-i-Card API based on variant prices plus configurable profit margin.
- Configurable profit margin settings (percentage or fixed amount) in product settings.
- Individual price sync control for each mapped product/variation with custom profit margin.
- Price synchronization runs automatically during product status sync (cron or page visit).

### Connection Status & Real-Time Wallet Balance

- Admins can view the current connection status to the Gift-i-Card API and see the real-time balance of their Gift-i-Card wallet directly from the plugin dashboard.

### Product Mapping

- Map each WooCommerce product or variation to a specific Gift-i-Card variant using the admin panel.
- Mapping is managed via AJAX and stored in product meta fields, allowing you to sell exact gift card types from your WooCommerce catalog.
- The mapping interface supports searching WooCommerce products and linking them to Gift-i-Card variants.

### Product Status Synchronization

- Automatically synchronize Gift-i-Card product availability status with WooCommerce stock status.
- Individual stock sync control for each mapped product - enable/disable stock synchronization per product.
- Global stock sync setting with per-product override capability.
- Configurable status mapping for different Gift-i-Card delivery types (Instant, Manual, Out of Stock, Deleted/Not Available).
- Batch processing system for efficient handling of large product catalogs (configurable batch size).
- Scheduled synchronization via WordPress cron jobs (recommended: 2-3 times daily).
- Manual synchronization option for immediate updates.
- Real-time status display showing next execution time, current sync status, and batch progress.
- Automatic stock status updates based on Gift-i-Card inventory changes.
- Progress tracking with visual progress bar for batch processing status.

### Automatic Gift Card Order Placement

- When a customer places an order containing mapped products, the plugin automatically sends a purchase request to the Gift-i-Card API for each mapped item.
- The plugin handles the API response, stores the Gift-i-Card order details (including codes) in the WooCommerce order meta, and adds order notes for tracking.

### Order Status Automation

- The plugin monitors the status of Gift-i-Card orders (via API and webhook).
- If all mapped items are successfully fulfilled, the WooCommerce order status is automatically set to "completed."
- If any mapped item fails, the order status can be set to "failed" (configurable).
- Status updates are handled both by scheduled checks (cron) and real-time webhook notifications from Gift-i-Card.
- The plugin can automatically set WooCommerce order status to "cancelled" when required (auto-cancellation).

### Display of Purchased Gift Cards

- The purchased gift card codes and related information (serial, card code, redeem link, expiration date, etc.) are displayed to the customer:
  - In the order confirmation email
  - On the order details page in the user account
  - On the "Thank You" page after payment
- The display is dynamic and only shown for items mapped to Gift-i-Card variants.

### Admin Sync and Management

- Admins can sync categories, products, and variants from the Gift-i-Card API to WordPress for easier mapping and management.
- There are tools for bulk deletion of plugin data and for manual order management.

### Compatibility

- Fully compatible with both HPOS (High-Performance Order Storage) and the legacy WooCommerce order storage system
- Tested up to WordPress 6.9
- Tested up to WooCommerce 10.4.3

## Setup Requirements

- To start using the plugin, you need to enter your `base_url`, `consumer_key`, and `consumer_secret` in the plugin settings.
- For production, get your credentials from [gifticard.ir](https://gifticard.ir).
- You can also obtain a Sandbox token for testing and development.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/gift-i-card` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Gift-i-Card menu to configure the plugin.
4. Map your WooCommerce products to Gift-i-Card variants as needed.

## Links

- [Gifticard](https://gifticard.ir)

## License

This plugin is licensed under the GPLv2 or later. See the LICENSE file for details.
