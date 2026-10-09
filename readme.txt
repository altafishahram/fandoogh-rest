=== Fandoogh Rest ===
Tags: restaurant, digital menu, woocommerce, qr, rtl
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.2
Requires Plugins: woocommerce
Stable tag: 1.5.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce digital menus and a dedicated restaurant operations panel with Persian RTL, table approval, native online checkout and QR codes.

== Description ==
Fandoogh Rest uses WooCommerce products, categories, inventory and orders as the single data source. The dedicated /cafe-panel/ handles menu editing, table requests, cashier orders, reports, staff permissions, imports, appearance and notifications.

The menu works as a standalone /menu/ route or as shared components in WordPress, Elementor and shortcode-compatible builders. Menu-only QR codes work without creating tables or enabling orders. Table orders require staff approval before preparation. Pickup and delivery use the native WooCommerce cart, checkout and payment gateways.

Each branch has its own menu, prices/stock, tables, settings, translations, reports and scoped staff access. The payment gateway and store currency remain shared. Use branch="slug" in menu shortcodes or [fandoogh_rest_branches] for branch menu links.

Includes local Vazirmatn fonts, CSV/XLSX column mapping, category-specific ordering, PNG/SVG QR downloads, four-language customer menus and checkout, a Persian panel, a panel manifest/service worker and bundled Web Push dependencies.

Choose from five menu designs and customize cards, food/language dialogs, categories, buttons and cart with validated CSS editors. A mobile/desktop preview is isolated from the operations panel. Theme and custom CSS also apply to builder menu components; the native Woo checkout keeps the site's theme.

== Installation ==
1. Activate WooCommerce 9.0+ on WordPress 6.5+ and PHP 8.2+.
2. Upload and activate the Fandoogh Rest ZIP.
3. Open /cafe-panel/ as a site administrator.
4. Configure the menu, food products, staff and ordering modes.
5. Configure native WooCommerce gateway/shipping/checkout pages before enabling online orders.

All dependencies and built assets are bundled; no Composer or Node is needed on the target site. See README.fa.md for complete Persian setup instructions.

== Frequently Asked Questions ==
= Is WooCommerce optional? =
No. Products, prices, stock, carts and orders belong to WooCommerce.

= Must I create a WordPress page? =
No. The standalone menu route is provided. A custom published page can also be selected and built with the Gutenberg block, Elementor widget or shortcodes.

= Which shortcodes are available? =
[fandoogh_rest_menu], [fandoogh_rest_categories], [fandoogh_rest_products category="12"], [fandoogh_rest_cart]. Original admincafe_* aliases remain supported. Use mode="menu" for a display-only component. Server ordering settings remain authoritative.

= Does changing currency convert prices? =
No. The currency selector changes the WooCommerce currency; monetary numbers remain unchanged. Verify gateway compatibility with IRT/Toman or any selected currency.

= Can the manager enter only Persian food text? =
Yes, after configuring the optional Google Cloud Translation Basic connection in the dedicated panel. English, simplified Chinese and Turkish results are stored locally, existing manual translations are protected, and public visits do not call the provider. API access/billing and working background jobs are required. Manual translation remains available.

= Are system notifications supported? =
Web Push requires HTTPS, browser/OS support, user permission and working server-side outgoing HTTPS/cron. iOS/iPadOS web apps require Home Screen installation. In-panel notifications remain available without Push. No offline ordering is provided.

= Does uninstall erase food and order data? =
No. Normal uninstall preserves data. Optional FANDOOGH_REST_REMOVE_DATA only removes Fandoogh Rest-specific settings, roles and events; WooCommerce products/orders remain.

== Changelog ==
= 1.5.0 =
Approved warm olive customer menu, larger mobile photo cards and WooCommerce product galleries with touch, mouse and keyboard navigation. No previous/next gallery buttons. Gallery upload/reordering in the dedicated panel, branch media validation and bundled CSS/font verification.

= 1.4.0 =
Multi-branch menus and staff operations, isolated carts/orders/QR/content/notifications, branch builder embeds and a shared Woo gateway/currency. Branch-specific shipping rates are deferred.

= 1.3.1 =
Fandoogh Rest branding, compatible legacy identifiers and shortcodes, documented Git workflow, CI and reproducible packages. Multi-branch support remains planned.

= 1.3.0 =
Five selectable menu designs, validated and scoped custom CSS editors for menu elements, and an isolated responsive appearance preview. Appearance also applies to the entry language dialog and builder components.

= 1.2.0 =
Optional automatic Persian-to-English/Chinese/Turkish content translation using Google Cloud Translation Basic. Stored results, background processing, manual edit protection, private credentials and translation status/retry controls.

= 1.1.0 =
Customer entry language dialog with local SVG flags; Persian, English, simplified Chinese and Turkish menus, content/cart translations and Woo checkout locale persistence. Persian management translation editors, validated settings, canonical product IDs and immutable order snapshots. Official core/Woo language data bundled as a fallback.

= 1.0.0 =
Initial modular release: WooCommerce catalog and orders, dedicated management panel, staff approval, pickup/delivery checkout, QR, imports, builders, appearance, reports and notifications.

== Third-party services and licenses ==
Automatic translation is disabled by default. When configured and enabled by a manager, food/category names and descriptions and restaurant display text are sent over HTTPS to Google Cloud Translation Basic. Customer/order details, prices and stock are excluded. A Google Cloud project with billing/API access and a restricted API key is required; provider charges and terms apply. Translation results are stored locally so menu visits do not invoke the service. See docs/TRANSLATION.md, https://docs.cloud.google.com/translate/docs/basic/setup-basic and https://cloud.google.com/products/translate/pricing for setup and pricing; provider terms: https://cloud.google.com/terms and privacy: https://cloud.google.com/terms/cloud-privacy-notice.

Local fonts do not contact a remote font provider. Optional browser Web Push sends encrypted notifications to the recipient browser's push provider after explicit subscription. Devices can be removed from the panel. Dependencies retain their original license files under vendor/ and assets/FONT-LICENSE.txt.

== Validation ==
Local integration verified on WordPress 7.1.2 / WooCommerce 11.1.2 with HPOS, PHP 8.3.35 and SQLite Database Integration. Required CI also verifies WordPress 7.1.3 / WooCommerce 11.2.0 on MySQL 8 HPOS. A real bank gateway, HTTPS push delivery, physical phone gestures and a live Elementor installation require target-site validation.
