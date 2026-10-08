# AdminCafe architecture and development

## Boundaries

AdminCafe is a PSR-4 PHP plugin with isolated Vue 3 applications built by Vite and styled with scoped Tailwind/CSS. WooCommerce is required and owns products, taxonomy, inventory, money, carts, order items, refunds and payment status. AdminCafe adds operational metadata and presentation rather than duplicate food/order post types.

| Module | Responsibility |
|---|---|
| `Core` | Bootstrap, installation/version upgrades, roles, validated settings, routes, assets, guest session security and currency extension |
| `Localization` | Four customer language contexts, isolated Persian operations, shared UI catalogs and official checkout language pack fallbacks |
| `Translation` | Optional background Persian content translation, provider adapters, private credential configuration, field provenance and guarded job processing |
| `Appearance` | Validated custom CSS, isolated public/preview scopes and a nonce-protected draft preview API |
| `Menu` | Woo CRUD catalog, simple products and existing variations, category display/order metadata |
| `Tables` | Stable opaque QR identities, table configuration and effective ordering policy |
| `Commerce` | Stock-checked orders, table approval, financial/operational state separation and native checkout guards |
| `Rest` | Capability-protected management API and guest ordering API |
| `Reports` | Woo order queries, paid revenue net of refunds and aggregated menu metrics |
| `Import` | Bounded CSV/XLSX parsing, column mapping, user-owned resumable batches and import deduplication |
| `Notifications` | Persistent per-user event reads, queued Web Push, VAPID, device ownership and channel preferences |
| `Integrations` | Shortcodes, dynamic Gutenberg block and conditionally loaded Elementor widget |
| `frontend` | Shared menu/cart state, dedicated panel, forms, QR export, appearance preview and PWA enrollment |

Each PHP module exposes `register()` for hook registration. `Core/Plugin.php` is the composition root. Vue menu and panel have separate entries; functional logic lives in composables, pure display/domain helpers and individual page components. Production never enables the development mock adapter implicitly.

Appearance stores a preset identifier and editable CSS maps inside general settings. A bounded server tokenizer/compiler validates the supported CSS grammar and scopes every output selector; raw editor maps are excluded from customer bootstrap. Initial page configuration exposes an appearance-only whitelist so the entry language dialog uses the saved design before any product request. Draft preview uses the same parser through a capability/nonce-protected route, without option writes. Its sample canvas is teleported into a sandboxed same-origin iframe with separate viewport, stylesheet, compiled preview scope and FontFace cache, so responsive rules run against the selected preview width. Five frontend theme token sets share the public and preview renderers; picking a preset changes palette defaults, while content, layout, fonts, typography and custom CSS remain editable.

## Data and flow

Menu reads published, visible Woo products in selected categories. Prices and stock come from Woo, including variants; descriptions are serialized as plain text. Product order per category lives in term metadata. Tables/settings live in prefixed options; uploaded images remain regular WordPress attachments.

Table requests are bound to a Woo guest session plus an opaque table token. CSRF and origin checks precede validated item IDs/quantities. The server resolves price, reserves stock and persists a Woo order, then publishes the event. A session/table/request id ledger plus a fingerprint prevents resubmission and cross-context replay. A stale uncertain request is blocked for staff review instead of blindly creating a second order. Manual counter orders use staff authentication and the same product/stock validation.

Operational states are `awaiting_approval → accepted → preparing → ready → delivered`, with allowed cancellation transitions. Financial status is independent: approval is not payment, and cancellation is not a refund. Online orders require paid confirmation or an explicitly permitted offline method before acceptance. Staff mutation locks protect duplicate settlement/refund calls.

Online clients fill Woo's Store API cart, select pickup/delivery through the protected AdminCafe API, and navigate to native Woo checkout. Both classic and Store API checkout enforce current channel settings. Pickup shipping suppression is session-scoped; products are not globally changed to virtual.

Events live in `{$wpdb->prefix}admincafe_events`; reads live in `admincafe_event_reads`. New-order event keys deduplicate both panel and Push deliveries. Browser device subscriptions belong to a user, require notification capability at delivery and are revoked on logout. Action Scheduler retries sending up to three times, with WP-Cron fallback. Events expire after 90 days; request ledgers remain for deduplication.

## API and security

Namespace: `admincafe/v1`. Management writes require a signed-in WordPress cookie, `X-WP-Nonce` and the specific capability. Guest writes use `X-AdminCafe-Token`, a token bound to a fresh Woo session, same-origin validation and throttling. Guest requests must omit an empty WordPress nonce header. Browser code never receives WooCommerce consumer secrets.

Use Woo CRUD/query APIs for business data and prepared statements for the add-on's event/lease storage. All request shapes and enumerations are bounded/validated. Media accepts only genuine JPEG/PNG/WebP up to 5MB/40 megapixels. XLSX is parsed without extraction, DTD/entities or cached formulas; expanded archives/XML/rows/cells are bounded. Push endpoints are allowlisted browser provider hosts, HTTPS port 443, with strict encryption key lengths.

API/panel responses are no-store; the service worker has no fetch/cache handler. It does not cache credentials/customer orders. Scope is the panel path. Page caching/CDN rules still need to exclude cart, checkout, panel, QR and AdminCafe REST routes on deployment. Actual WordPress hardening, backup, HTTPS and gateway setup belong to the host/site configuration.

## Extension points

- `admincafe_order_created` and `admincafe_order_stage_changed`: subscribe to persisted operations; notification errors do not roll back a valid order.
- `admincafe_management_bootstrap`: enrich the management configuration.
- `admincafe_offline_payment_gateways`: allowed offline gateway identifiers, default `cod`, `bacs`, `cheque`.
- Same-site public menu renderer is shared by the standalone route, builder components and shortcodes. Server policy remains authoritative regardless of rendering mode.
- New schema versions belong in `Installation::upgrade()`; use Woo metadata and CRUD for HPOS compatibility.
- `Core/Currency` adds IRT/Toman; settings write the Woo currency option. Currency changes never convert existing prices or historical order currency.

## Build and validation

Customer languages are `fa`, `en`, `zh` (simplified Chinese) and `tr`. Product/variation `_admincafe_translations` and category term metadata hold validated locale maps; Persian remains canonical Woo content. Content and per-field fallback never duplicate product identities or money. `content_translations` settings cover restaurant text and custom messages. `enabled_languages` is a replacing list, and its default must be enabled.

The shared frontend state keeps language outside the cart persistence key. Entry selection gates menu loading, multiple builder roots share one dialog, and request generations discard stale bootstrap responses. Standalone pages update their document language/direction/title; embedded components leave host page metadata alone.

Only public menu/order routes, native Woo cart/checkout and corresponding Store API/wc-ajax requests receive the customer locale. Panel/management routes remain Persian even inside a REST batch after a customer locale switch. The CSRF-protected checkout/table endpoint persists the validated preference to the Woo session and a SameSite/HttpOnly cookie. GET bootstrap changes presentation without persisting a session preference. Order items keep canonical names and immutable customer-language snapshots; language is excluded from the financial idempotency fingerprint.

`frontend/src/customer-i18n.js` is the customer UI source, staged into `resources/customer-strings.json`; server validation text lives in `customer-server-strings.json`. `languages/build_customer_catalog.py` builds Chinese/Turkish customer PO/MO assets. The Persian operational and legacy English catalogs use `languages/build_catalog.py`. Official core/Woo MO and JS packs are bundled as data under `languages/checkout`; URLs, component versions, checksums and license are recorded in `SOURCES.json`. Installed/custom language packs take precedence. These UI catalogs do not require remote translation. Optional automatic content translation is a separate module, disabled until configured; public menu rendering reads stored content and never calls the provider.

Automatic translation uses a provider interface with a Google Cloud Translation Basic v2 adapter. Its fixed HTTPS endpoint receives only plain Persian display text, with explicit source/target and NMT model; API key authentication is server-side. Private configuration is separate from `admincafe_settings`; redacted management responses cannot reveal its encrypted credential. An optional `ADMINCAFE_GOOGLE_TRANSLATE_API_KEY` constant takes precedence. Queue entries contain source identifiers/fingerprints and safe error codes, never credentials or customer data. Action Scheduler runs work asynchronously, with WP-Cron fallback.

Canonical source changes invalidate stale work; unchanged source avoids translation on price/stock updates. Stored generated-output provenance distinguishes machine fields from manual corrections. The `admincafe_translation_manual_input` action records explicit manual intent, including empty fields removed by display-map sanitization. Workers reread source/provenance before writing and avoid overriding manual changes made while a network request is in flight. Existing Woo IDs, monetary fields, stock and immutable order snapshots remain unchanged. See `docs/TRANSLATION.md` for setup, consumption limits and credential rotation.

The source tree includes locked Composer/npm dependencies. Build with a PHP 8.2+ CLI with OpenSSL/cURL/mbstring/Zip/GD, Node and Python 3 for catalog generation:

```powershell
composer install --no-dev --prefer-dist --optimize-autoloader
npm --prefix frontend ci
npm --prefix frontend test
npm --prefix frontend run build
node scripts/stage.mjs
php scripts/icons.php
python languages/build_catalog.py
python languages/build_customer_catalog.py
python tests/localization.py
python tests/customer_localization.py
php tests/backend.php
php tests/commerce.php
php tests/translation.php
php scripts/package.php
```

Rebuild Vite before staging icons/block JS because the frontend build clears `assets/`. Final ZIP includes `vendor`, built assets, local font licenses, PHP resources, templates, catalogs and documentation. It excludes the disposable WordPress database, credentials, downloaded tools, tests, node_modules and frontend development sources. Source development files remain in the project.

The disposable integration runners refuse any database without the explicit `admincafe_test_environment=local-disposable` marker. Run `php tests/integration.php /absolute/disposable/wordpress/wp-load.php`; it writes local fixtures and orders. The multilingual/commerce runners and `tests/wp-translation.php` and `tests/wp-translation-security.php` use the same loader argument. Run database suites sequentially. Translation tests block outgoing HTTP and use a fake provider; they do not verify a live Google account or language quality. Never point these scripts at a live restaurant database. `.tools/README.md` documents the isolated local environment and loopback server; `.tools` is not distributed.

Automated coverage includes catalog validation, staff restrictions, table policy, checkout channel guards, settlement/refund permissions, request locks/recovery, import parsing/checkpoints, order/event deduplication, localization/placeholder parity, frontend domain behavior and guest header regression. The local integration uses real WordPress/Woo/HPOS on SQLite. Release validation still needs MySQL/MariaDB concurrency, a real gateway sandbox, HTTPS Push on recipient devices and the chosen site theme/Elementor combination.

## Licenses

AdminCafe is GPL-2.0-or-later. Vazirmatn is packaged under SIL OFL; proprietary IRANSans/Dana are user supplied. Composer dependencies retain their own licenses. No generated test site or third-party commercial font is included in the release ZIP.
