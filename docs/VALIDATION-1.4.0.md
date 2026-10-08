# Multi-branch 1.4.0 validation — 2026-10-09

This record describes the development candidate on `feat/multi-branch`. It does not assert a published release or a passing remote CI run.

| Check | Result |
|---|---|
| PHP syntax | 58 files passed on portable PHP 8.3.35 |
| Standalone domain assertions | Backend 71, commerce 49, translation 45, appearance 34: 199 passed |
| Bootstrap compatibility | Current entrypoint, legacy entrypoint and duplicate activation guards passed |
| Frontend | 36 tests passed; production Vue/Vite build, resources and icons generated |
| Operational localization | 406 messages per fa/en, 812 MO translations, 26 Jed strings and 13 catalog checks passed |
| Customer localization | 119 messages per zh/tr, 238 MO translations, four-language parity and six official checkout packs verified |
| Composer manifest | Strict validation passed; lockfiles retained |
| Distribution | Install/source ZIP exclusions, entry ordering and identical deterministic rebuilds passed |
| Previous database suites | 58 base + 91 multilingual + 64 commerce language + 39 translation + 40 translation security + 179 appearance + 17 branding = 488 checks passed |
| New branch database suites | 20 core + 49 commerce + 41 content = 110 checks passed |

The database checks used disposable WordPress 7.1.2, WooCommerce 11.1.2 and SQLite with HPOS. They verify real Store API dispatch, foreign drafts/cart isolation, branch snapshots, category/product/media/import boundaries, scoped workers, QR policies, reports/events, recipient checks and settings permissions. Translation HTTP was blocked/mocked; no live provider request or payment was made.

SQLite does not support WooCommerce's MySQL stock reservation SQL. The ignored local runner disables reservation only for this disposable test process; production code is unchanged. Stock reduction/cancellation checks passed locally, but native reservation must pass the pinned WordPress 7.1.3 / WooCommerce 11.2.0 / MySQL 8 CI job before merge/release. The CI runner applies no SQLite workaround and runs all ten suites sequentially.

After the 598 database checks, a final small category-save fix queues translation after branch ownership/manual translations are persisted, and the core test's missing-loader guard exits nonzero. PHP syntax/standalone/frontend/catalog checks passed after that fix. A fresh full database/CI run must include this final code.

The final browser check could not run because starting the local server required an automatic permission review, which failed due to account usage limits. Earlier-version screenshots are not evidence for this new panel. Live Elementor rendering, gateway/refunds, HTTPS notifications on Windows/mobile and concurrent load remain deployment checks. Branch-specific shipping regions/rates, geolocation, structured hours scheduling, product duplication and collection-wide consolidated analytics are deferred.
