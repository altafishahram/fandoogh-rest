# Multi-branch design — planned, not implemented

This document records the accepted design for the next development stage. Fandoogh Rest 1.3.1 remains a single-branch plugin. Publishing this plan does not enable multi-branch ordering.

## Confirmed requirements

- One restaurant brand, one reference website, one WordPress installation and one WooCommerce store.
- One shared payment gateway and merchant account; one store currency.
- The reference website introduces branches and links to their individual menus. It can be built with Elementor or another site builder.
- Branches may have completely different food products, categories, images, descriptions and customer translations.
- Each branch owns its prices, WooCommerce inventory, tables, QR identities, appearance, business hours and enabled ordering channels.
- Customer languages remain Persian, English, simplified Chinese and Turkish. The operations panel remains Persian.
- A collection manager can access all branches. Branch managers and operational staff access only assigned branches and permitted functions.
- Orders, reports and Windows/mobile notifications must be scoped to the appropriate branch.

## Proposed implementation boundaries

Introduce a `Branches` module and an explicit validated branch context. Each saleable WooCommerce product belongs to one branch; variations inherit their parent branch. Copying a product to another branch creates an independent WooCommerce product, preserving native price, stock reservation and refund behavior. Category membership and display order also require a branch boundary.

The active WooCommerce cart and each resulting order belong to exactly one branch. Validate product ownership, table ownership and ordering availability again on the server for add-to-cart, cart changes and both classic/Store API checkout. A branch switch with a populated cart requires a clear customer decision. Preserve branch attribution on the order and its operational events even after branch names or addresses change.

Branch authorization must cover reads and writes, including direct object IDs, imports, uploads, reports, staff assignment, tracking and notification delivery. A client-side branch selector is not an authorization mechanism. Shared credentials must never be returned through a branch settings API.

QR table tokens resolve the branch on the server. Product rendering remains shared by standalone menus, shortcodes, blocks and Elementor. Proposed routes are `/branches` and `/menu/{branch-slug}`; choose conflict-free rewrites and retain old routes as aliases for the default branch.

## Upgrade and acceptance criteria

- Migrate existing settings, products, table tokens, employees and operational records into a default branch without changing WooCommerce product/order IDs or QR tokens.
- Keep merchant credentials and currency at collection level; scope delivery regions, charges and fulfillment choices to each branch.
- Test cross-branch denial for every management/guest endpoint and notification channel.
- Test checkout branch validation through both classic and Store API paths, including stale carts and concurrent tabs.
- Test independent prices/stock, reservation cancellation and refund restoration through WooCommerce APIs.
- Test existing QR links, page-builder embeds, four languages and saved custom CSS after migration.
- Release multi-branch support through a separate feature pull request and versioned migration after these checks pass.
