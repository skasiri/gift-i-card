=== Gift-i-Card ===
Contributors: saeidkasiri
Tags: woocommerce, gift card, ایران, فارسی, gifticard
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.0
WC requires at least: 5.0
WC tested up to: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

**English:**
Gift-i-Card integrates WooCommerce with the Gifticard.pro service for international users.

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

**فارسی:**
افزونه «گیفتی کارت» فروشگاه ووکامرس شما را به سرویس gifticard.ir متصل می‌کند و ویژه کاربران ایرانی است.

**عملیات و امکانات اصلی با جزئیات:**
- **پشتیبانی از محصولات ساده و متغیر ووکامرس:**
  - افزونه به طور کامل از مپ کردن محصولات ساده و متغیر ووکامرس (و همچنین واریانت‌ها) به واریانت‌های گیفتی کارت پشتیبانی می‌کند.
- **نمایش وضعیت اتصال و موجودی لحظه‌ای کیف پول:**
  - مدیر سایت می‌تواند وضعیت اتصال به API گیفتی کارت و موجودی لحظه‌ای کیف پول خود را مستقیماً از پیشخوان افزونه مشاهده کند.
- **مپ کردن محصولات:**
  - در پنل مدیریت، می‌توانید هر محصول یا واریانت ووکامرس را به یک واریانت خاص از گیفتی کارت متصل کنید.
  - این ارتباط از طریق جستجو و انتخاب محصول ووکامرس و ذخیره‌سازی در متای محصول انجام می‌شود و امکان فروش دقیق انواع گیفت کارت را فراهم می‌کند.
  - رابط کاربری مپ کردن، جستجوی محصولات ووکامرس و اتصال به واریانت‌های گیفتی کارت را ساده می‌کند.
- **ثبت سفارش خودکار در گیفتی کارت:**
  - پس از ثبت سفارش توسط مشتری، برای هر آیتم مرتبط، افزونه به صورت خودکار درخواست خرید را به API گیفتی کارت ارسال می‌کند.
  - پاسخ دریافتی (شامل کدها و اطلاعات سفارش) در متای سفارش ووکامرس ذخیره شده و یادداشت‌هایی برای پیگیری به سفارش افزوده می‌شود.
- **تغییر وضعیت خودکار سفارش ووکامرس:**
  - افزونه وضعیت سفارشات گیفتی کارت را از طریق API و وب‌هوک بررسی می‌کند.
  - اگر همه آیتم‌های مرتبط با موفقیت تأمین شوند، وضعیت سفارش ووکامرس به صورت خودکار به «تکمیل شده» تغییر می‌کند.
  - در صورت ناموفق بودن هر آیتم، وضعیت سفارش می‌تواند به «ناموفق» تغییر کند (قابل تنظیم).
  - این تغییر وضعیت هم به صورت زمان‌بندی شده (کرون) و هم به صورت آنی از طریق وب‌هوک انجام می‌شود.
- **نمایش کدهای گیفت کارت خریداری‌شده:**
  - کدها و اطلاعات گیفت کارت (سریال، کد کارت، لینک فعال‌سازی، تاریخ انقضا و ...) به مشتری نمایش داده می‌شود:
    - در ایمیل تأیید سفارش
    - در صفحه جزئیات سفارش در حساب کاربری
    - در صفحه تشکر پس از پرداخت
  - این نمایش فقط برای آیتم‌هایی که به واریانت گیفتی کارت مپ شده‌اند فعال است.
- **مدیریت و همگام‌سازی توسط مدیر سایت:**
  - مدیر سایت می‌تواند دسته‌بندی‌ها، محصولات و واریانت‌ها را از API گیفتی کارت به وردپرس همگام‌سازی کند تا فرآیند مپ کردن و مدیریت ساده‌تر شود.
  - ابزارهایی برای حذف گروهی داده‌های افزونه و مدیریت دستی سفارشات نیز وجود دارد.

== Installation ==

**English:**
**Setup Requirements:**
- To start using the plugin, you need to enter your `base_url`, `consumer_key`, and `consumer_secret` in the plugin settings.
- For production, get your credentials from [gifticard.pro](https://gifticard.pro).
- You can also obtain a Sandbox token for testing and development.

**فارسی:**
**پیش‌نیازهای راه‌اندازی:**
- برای شروع کار با افزونه، باید `base_url`، `consumer_key` و `consumer_secret` را در تنظیمات افزونه وارد کنید.
- برای محیط اصلی (Production)، این اطلاعات را از [gifticard.ir](https://gifticard.ir) دریافت کنید.
- همچنین امکان دریافت توکن محیط آزمایشی (Sandbox) برای تست و توسعه وجود دارد.

1. Upload the plugin files to the `/wp-content/plugins/gift-i-card` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Gift-i-Card menu to configure the plugin

== Changelog ==
= 1.0.0 =
* Initial release

== Upgrade Notice ==
= 1.0.0 =
First public release. 