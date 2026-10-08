# Version 1.3.0 validation — 2026-10-08

The installable ZIP was built with bundled production Composer dependencies, Vite assets, local fonts/licenses, PHP resources and translation catalogs, then installed into the disposable local WordPress site through the normal ZIP installer.

| Check | Result |
|---|---|
| PHP source syntax | 37 production/build files passed |
| Backend behavior | 71 assertions passed |
| Commerce rules | 49 assertions passed |
| Vue/domain and rendered interface regression tests | 30 tests passed; language gate, four flags, keyboard selection, shared cart, translation controls, theme selection, draft validation races, explicit CSS reset and per-document fonts included |
| Real WordPress/WooCommerce integration | 58 checks passed, including actual HPOS orders, database imports and builder page resolution |
| Real multilingual WordPress/Woo integration | 91 assertions passed: four locales, product/category/variation content, metadata validation, enabled/default languages and isolated Persian management |
| Real multilingual commerce integration | 64 assertions passed: native Woo hydration, descriptions, immutable order snapshots, canonical manager labels and variation metadata |
| Automatic translation unit tests | 45 assertions passed: configuration types, credential encryption/redaction, official provider request contract, Unicode chunking and manual field protection; HTTP fully mocked |
| Automatic translation WordPress worker integration | 39 assertions passed: three language checkpoints, field-only regeneration, preserved prices/manual corrections, category/restaurant text, scope, quotas, bounded retry, recovery and output limits; provider mocked |
| Automatic translation WordPress security integration | 40 checks passed: real capabilities/nonces, private configuration, source whitelist, transport errors, stale source/manual writes and empty-map compare-and-swap; outbound HTTP blocked or mocked |
| Appearance compiler | 34 checks passed: selector scoping, declaration syntax, supported responsive rules, malformed/escaped/resource CSS rejection and limits |
| Real appearance WordPress security integration | 179 checks passed: private draft maps, public/initial/builder appearance, every element scope, preview without writes, capability/nonce denial, limits, atomic rejection and fail-closed tampered values; original settings restored |
| Operational localization | 376 messages per fa/en locale; 752 MO values, 24 Jed values and 13 catalog checks passed |
| Customer localization | 103 messages per customer locale, 206 Chinese/Turkish MO values, four-language key/placeholder parity and six official checkout language packs verified |
| Dependency platform requirements | Composer production requirements passed on portable PHP 8.3.35 |
| Production dependency advisories | Dependency versions are unchanged from 1.1.0, where Composer audit and npm audit --omit=dev reported none |
| Real appearance browser | Midnight theme, custom 32px card corners and copper borders applied in an isolated preview. A custom max-width:640px rule produced 19px description text at a 360px iframe viewport and 14px at 960px. Detail, language and cart previews remain isolated from management |
| Customer gate regression | Actual Menu renderer tests retain entry gating, theme/compiled CSS before entry, one shared dialog across builder roots, keyboard selection and hidden food content before choice |
| Menu-only QR regression | Server-side mode enforcement remains covered; stable table identity and builder destination resolution covered by base integration |

Environment: WordPress 7.1.2, WooCommerce 11.1.2, PHP 8.3.35 and the official SQLite Database Integration drop-in. HPOS was enabled. Test credentials, database and tools are excluded from distribution. SMTP was disabled; no real customer order or payment was made. The browser preview uses disposable sample products and orders.

Integration tests include same-origin/session CSRF, management permission denial, server totals, staff approval before preparation, idempotent order retries, menu-only enforcement, event deduplication, read acknowledgments, push endpoint SSRF rejection, CSV/XLSX safety, preserved price on unavailable imports, checkout channel requirements, pickup/delivery shipping behavior and currency/address serialization.

The following remain target-environment checks: MySQL/MariaDB concurrency, a real gateway sandbox and refund support, HTTPS Web Push on Windows/mobile recipient devices, live Elementor rendering and the chosen theme/cache/plugin combination. Minimum supported versions are declared requirements; every minimum-version combination has not been individually tested.

Checkout validation includes the actual Woo `Hydration` service and EN/ZH/TR native checkout HTML preload payloads, plus a live Chinese Blocks page. Merchant-configured gateway labels, shipping-method labels, privacy/terms text and surrounding builder/theme content remain owned by the site and their extensions. Disposable preview foods were seeded with known manual translations. No real payment was executed.

The live Chinese Blocks and 390×844 mobile entry screenshots were captured during the 1.2.0 release; their data/hydration and customer renderer regressions passed again for 1.3.0. Appearance UI testing used the actual management REST API, local login and the production bundle. The responsive preview is a sandboxed same-origin iframe with a separate menu root; custom styles and sample interactions do not mutate products, orders or the surrounding panel. CSS is a documented bounded subset rather than an unrestricted stylesheet loader. Target theme/builder compatibility and site-defined animation/font behavior still require deployment checks.

Automatic content translation is implemented as an optional module and is disabled in the delivered local preview. Its unit/integration tests use synthetic keys and fake translation responses; no Google account, billable request or linguistic-quality claim was verified. Live provider setup and simultaneous MySQL/MariaDB row-lock behavior remain target-environment checks. The real panel displays the unconfigured credential state, zero consumption, and disabled backfill/retry controls; saving disabled settings and refreshing status use actual nonce-protected REST requests.
