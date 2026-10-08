# Multi-branch 1.4.0 validation — 2026-10-09

This record identifies checks performed on `feat/multi-branch` before the reviewed 1.4.0 merge. Published archives are produced by CI from the release commit; source/checksum verification is part of publication.

| Check | Result |
|---|---|
| PHP syntax | 58 files passed on portable PHP 8.3.35 |
| Standalone domain assertions | Backend 71, commerce 49, translation 45, appearance 34: 199 passed |
| Bootstrap compatibility | Current entrypoint, legacy entrypoint and duplicate activation guards passed |
| Frontend | 38 tests passed; production Vue/Vite build, resources and icons generated |
| Operational localization | 406 messages per fa/en, 812 MO translations, 26 Jed strings and 13 catalog checks passed |
| Customer localization | 119 messages per zh/tr, 238 MO translations, four-language parity and six official checkout packs verified |
| Composer manifest | Strict validation passed; lockfiles retained |
| Distribution | Install/source ZIP exclusions, entry ordering and identical deterministic rebuilds passed |
| Previous database suites | 58 base + 91 multilingual + 64 commerce language + 39 translation + 40 translation security + 179 appearance + 17 branding = 488 checks passed |
| New branch database suites | 20 core + 49 commerce + 41 content = 110 checks passed |

The database checks used disposable WordPress 7.1.2, WooCommerce 11.1.2 and SQLite with HPOS. They verify real Store API dispatch, foreign drafts/cart isolation, branch snapshots, category/product/media/import boundaries, scoped workers, QR policies, reports/events, recipient checks and settings permissions. Translation HTTP was blocked/mocked; no live provider request or payment was made.

SQLite does not support WooCommerce's MySQL stock reservation SQL. The ignored local runner disables reservation only for this disposable test process; production code is unchanged. Stock reduction/cancellation checks passed locally, but native reservation must pass the pinned WordPress 7.1.3 / WooCommerce 11.2.0 / MySQL 8 CI job before merge/release. The CI runner applies no SQLite workaround and runs all ten suites sequentially.

The 110 new local database checks were repeated after the final category-save fix and passed. That fix queues translation only after ownership/manual translations persist; the core test exits nonzero when its required loader/marker is missing. Source `e2390a93c534389c415f5c88b59fcf8a9e386161` passed [push CI](https://github.com/altafishahram/fandoogh-rest/actions/runs/37860320192) and [PR CI](https://github.com/altafishahram/fandoogh-rest/actions/runs/37860415184): both PHP versions and all 598 real MySQL HPOS checks succeeded with native reservations enabled. The follow-up frontend fix adds two regressions for synchronous tab clearing and delayed result/error rejection. Source `f92cfa9e0d5634011d637f839f5f75700c9b8eec` passed [fresh push CI](https://github.com/altafishahram/fandoogh-rest/actions/runs/37860888796): 38 frontend tests, PHP 8.2/8.3 checks and all 598 MySQL HPOS checks. Required PR checks must pass on the final head before merge.

The actual production panel created a temporary second branch, displayed independent menu links, switched to its empty products view and returned to the original populated menu. Its public Chinese menu displayed the four-language entry gate and correct branch identity without default foods. Browser console inspection found no warnings/errors. Private before/after snapshots restored the original branch registry/settings and removed the temporary fixture. This check exposed old-tab cards briefly appearing during navigation; the follow-up frontend fix clears data before loading and rejects stale results/errors, with two new regression tests. The rebuilt production panel was rechecked: navigating from overview to branches clears old rows immediately while loading. Live Elementor rendering, gateway/refunds, HTTPS notifications on Windows/mobile and concurrent load remain deployment checks. Branch-specific shipping regions/rates, geolocation, structured hours scheduling, product duplication and collection-wide consolidated analytics are deferred.
