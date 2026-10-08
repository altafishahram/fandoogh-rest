# Multi-branch operations — 1.4.0 implementation

## Model

One WordPress/WooCommerce store shares a payment gateway, merchant account, currency, tax rules and shipping configuration. Branches have separate Woo products/categories, native prices/stock, tables/QR tokens, menu appearance, languages/content, ordering switches, operational orders/reports and staff access. Equivalent foods use separate products per branch. Woo SKUs stay globally unique. Variations inherit parent ownership.

The reference website remains a normal WordPress/builder page. Link to `/menu/{branch-slug}/`, select a builder menu page per branch, or render `[fandoogh_rest_branches]` for enabled branch links. Existing `/menu/` and QR routes resolve the default branch. Menu shortcodes, the Gutenberg block and Elementor widget accept a branch ID or slug. Separate branch embeds on one page have isolated carts and CSS scopes.

## Administration

Site administrators are collection managers. They create/rename/disable branches and assign employees to one or multiple branches. Existing operational roles/caps remain intact; legacy staff belong to branch 1. Branch managers cannot broaden assignments or change shared currency/routes/translation credentials. Every management request requires validated branch identity and the relevant nonce/capability. Foreign product/category/table/order/media IDs, parent categories, reorder lists and resumable import tokens are rejected. Product queries enforce ownership before pagination.

Disabling prevents public menus and new orders without deleting history. Assigned staff retain authenticated management access; the central panel can reenable branches even when all branches are disabled. Staff selectors hide disabled branches; an authenticated `/cafe-panel/?branch_id=ID` can still open assigned history.

## Commerce and asynchronous work

One native Woo cart/checkout belongs to one branch. Ownership persists on cart items/session and is revalidated on classic/Store API adds, quantity updates and checkout. Stale tabs and reassigned products are rejected. Foreign checkout drafts are rejected before Woo synchronizes their line items. Explicit customer consent is required to replace a populated foreign cart. Detached checkout references leave existing orders and their native stock/payment lifecycle intact.

Order branch ID/name/address snapshots are immutable. QR tokens resolve table ownership before reading that branch's menu-only/order policy. Event lists/reads and Push workers check employee branch membership. Translation jobs persist branch identity, restore context after work and update only that branch's content. Provider credentials/quota remain shared and redacted.

## Upgrade and limits

Existing unassigned records resolve to branch 1 without changing IDs, prices, orders or table tokens. New records have explicit ownership. Legacy staff assignments migrate once. Default settings remain canonical in `admincafe_settings`; additional settings live in `fandoogh_branches`. Events and translation jobs gain indexed branch ownership. Upgrade refreshes rewrite rules.

Public `/checkout` clients now supply `branch_id`; table QR ownership is authoritative. Back up before upgrading. Older code does not enforce branch boundaries: downgrading after multi-branch writes requires restoring the matching database, rather than just replacing PHP files.

Deferred: branch-specific shipping regions/rates, geographic delivery selection, structured automatic opening-hour scheduling, product duplication tools and collection-wide consolidated analytics. Shipping/tax/gateway configuration stays shared native Woo. Hours remain per-branch display text. Elementor is implemented but not tested against a live Elementor installation in this stage. Live payment, HTTPS device Push and concurrent reservation load tests remain deployment validation.

## Verification

Run database suites sequentially on a disposable marked database. `wp-branches-core.php` checks branch selection/assignments/context/shared-setting permissions/disabled recovery. `wp-branches-commerce.php` dispatches actual Woo Store API routes and checks QR, stock restoration, immutable drafts, direct order IDs, reports/events and Push recipient denial. `wp-branches-content.php` checks independent same-name categories, foreign content/import IDs, four-language guest menus, builder CSS and translation workers.

GitHub CI runs these on MySQL HPOS with native reservations enabled. Local SQLite checks exclude MySQL-only reservation SQL; they cannot substitute for the CI result.
