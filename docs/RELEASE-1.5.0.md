# Fandoogh Rest 1.5.0 — 2026-10-09

This compatible minor release packages the approved customer menu and WooCommerce product gallery implementation from [PR #10](https://github.com/altafishahram/fandoogh-rest/pull/10), merged as `1557b220a85dbb0365db22f5bfb80ecc4514b491`.

The warm ivory/olive layout has larger mobile photo cards, a refreshed language gate and full-aspect detail photos. Customers use touch swipes, mouse dragging, keyboard navigation or direct-selection dots; there are no previous/next gallery buttons. The dedicated product form supports gallery uploads, ordering and removal with branch-owned image validation. Existing Woo gallery images and variant-specific photos are supported.

Products, orders, branch identities, translations, cart/checkout and appearance settings remain in their existing stores. No new database migration is introduced. Default colors affect fresh installations or an explicitly selected theme; existing stored colors and custom CSS are preserved. Images come from each restaurant's own WooCommerce products, not bundled demonstration data. See the [Persian gallery guide](MENU-GALLERY.md).

The release increments plugin and frontend versions, translation catalog headers and the readme stable tag together. It also includes the mandatory frontend build verifier in the source archive and checks its presence during package verification.

## Validation

The reviewed feature passed 47 frontend tests; standalone PHP suites cover 91 backend, 49 commerce, 45 mocked-provider translation and 34 appearance checks, plus branding compatibility. Language catalogs passed key and placeholder parity checks. Local real WordPress checks include 16 gallery, 179 appearance and 41 branch-content checks. Browser verification covers mobile layout, mouse dragging and RTL keyboard navigation. Feature CI passed PHP 8.2/8.3 builds, deterministic archives and the full WordPress 7.1.3 / WooCommerce 11.2.0 integration suite on MySQL 8 HPOS.

Release metadata and the source packaging change must pass the same required CI before publication. Release ZIPs are built from the reviewed release commit, inspected for required files and private-data exclusions, checked for deterministic rebuilds and installed on disposable staging. Physical phone gestures, a live payment gateway, HTTPS push delivery and the selected production theme/Elementor remain target-site checks.

The locally built 1.5.0 installable ZIP passed deterministic package verification and was installed over the previous active plugin through WP-CLI. A before/after snapshot confirmed preservation of seven products, 51 orders, settings, tables, roles and branch assignments; the installed plugin reports version 1.5.0.

## Installation and upgrade

Use `fandoogh-rest-1.5.0.zip` for installation. The `fandoogh-rest-source-1.5.0.zip` archive is for development and requires the documented build toolchain. Back up the site before upgrading and use WordPress's existing-plugin replacement flow; do not install a second legacy AdminCafe copy alongside Fandoogh Rest. PHP 8.2+, WordPress 6.5+ and WooCommerce 9.0+ are the declared minimum requirements.

Both archives include SHA256 hashes and exclude private site data, credentials, screenshots and local tools. Published 1.4.0 artifacts and tags remain unchanged.
