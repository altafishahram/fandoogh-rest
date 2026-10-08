# Fandoogh Rest 1.4.0 — multi-branch operations

One restaurant collection uses one WordPress site and WooCommerce merchant account. Menus and operations are branch-specific; payment gateways, currency, native tax/shipping setup and translation provider credentials/quota are shared.

## Setup

1. Sign in to `/cafe-panel/` with a site administrator account. The current restaurant remains branch 1.
2. Open Branches, create each branch with a unique slug, then select it from the panel selector.
3. Add its separate Woo products/categories, prices/inventory, menu texts/translations, appearance and tables. Ordering starts disabled on new branches; enable the required modes after configuring native checkout.
4. Assign each employee to the allowed branches. Operational capabilities still determine permitted tasks; branch managers cannot broaden assignments or change shared store/provider settings.
5. Link the reference website to `/menu/{slug}/`, choose a published builder menu page per branch, or embed `[fandoogh_rest_branches]`. Menu shortcodes/block/Elementor widget accept branch IDs/slugs. Download per-branch/menu-only or table QR codes.

## Upgrade and rollback

Back up files/database and validate on staging. Upgrade the existing `fandoogh-rest` plugin using the installable ZIP. Existing unassigned products, categories, media, tables and orders resolve to branch 1 without changing their IDs or QR tokens. Default settings remain canonical; indexed event/job ownership and employee assignments migrate, and rewrite rules refresh.

Each Woo cart/order belongs to one branch. The customer must explicitly agree before a populated foreign cart is cleared. Draft orders and their stock/payment lifecycle are preserved when checkout references change. Public checkout API clients supply `branch_id`; server-side table ownership remains authoritative.

Disabling a branch stops its public menu/new ordering while preserving authenticated operational history. Restore a matching database backup when downgrading after branch writes: older versions cannot enforce the new boundaries. A Git revert alone does not undo live order/payment effects.

## Validation and boundaries

See [1.4.0 validation](VALIDATION-1.4.0.md) for exact checked revisions/environments and [operations model](MULTIBRANCH-PLAN.md) for contracts. Required CI covers 199 standalone PHP assertions, 38 frontend tests, localization/dependency/package checks and 598 disposable database checks on WordPress 7.1.3 / WooCommerce 11.2.0 / MySQL 8 HPOS. The browser uses the actual production panel and a temporary branch fixture, then restores original settings.

Branch-specific shipping regions/rates, geographic delivery selection, automatic hours scheduling, product duplication and consolidated collection analytics are deferred. Live Elementor, gateway/refunds, HTTPS Push delivery on Windows/mobile and production concurrent load remain deployment checks. No live provider request/payment was executed.

Install `fandoogh-rest-1.4.0.zip`; the `source` archive is for development. Release archives exclude private tools/site data/credentials/screenshots and are accompanied by SHA256SUMS. Source archive entries are checked against the tagged Git tree before publication.
