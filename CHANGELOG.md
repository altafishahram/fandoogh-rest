# Changelog

## 1.5.0 — Customer menu design and product galleries

- Apply the approved warm ivory and olive customer menu, with larger mobile photo cards, restrained motion, a refreshed language gate and product detail layout. Preserve branch content, builder components, custom appearance settings and existing CSS hooks.
- Add ordered WooCommerce product galleries and gallery management in the restaurant panel. Validate branch-owned image attachments before saving; preserve an omitted gallery and support explicit clearing or shortening.
- Browse detail photos by touch swipe, mouse drag or keyboard, with direct-selection indicators and no previous/next buttons. Support RTL, variant photos, unavailable images and reduced motion.
- Keep customer quantities, variable-product selection and the existing table/online checkout flows. Add gallery and customer rendering regressions plus real WordPress gallery integration to CI.
- Bundle menu and panel styles into one stylesheet and resolve bundled fonts relative to the plugin directory, including sites installed in a subdirectory.
- Include the required frontend build verifier in the development archive and verify its presence before publication.

## 1.4.0 — Multi-branch operations

- Add stable branch identities, central branch management and explicit employee assignments.
- Isolate products/categories, settings, imports, media, translations, tables/QR, orders, reports and notifications. Preserve existing IDs and table tokens under the default branch.
- Add `/menu/{branch-slug}/`, builder branch attributes and a branch directory shortcode. Compile CSS per branch, including multiple embeds on one page.
- Bind Woo cart items and order snapshots to a branch; guard classic and Store API adds/updates/checkout, stale tabs, reassigned products and existing checkout drafts. Require explicit consent before replacing a foreign cart.
- Keep Woo gateway/currency/shipping/tax configuration and translation credentials shared. Branch-specific shipping regions/rates are deferred.
- Add real WordPress branch isolation regressions and frontend race/cart/consent coverage; run the complete integration suite on MySQL HPOS in CI.

### Upgrade boundary

Existing unassigned records resolve to default branch 1 without rewriting Woo IDs or QR tokens. New records receive explicit branch ownership. Refresh permalinks on upgrade. Public checkout requests now carry `branch_id`; QR table ownership remains server-authoritative. Downgrading after adding new branches requires restoring the pre-upgrade database: old versions do not enforce branch isolation.

## 1.3.1 — Fandoogh Rest publication

- Rename the product to **رستوران فندوق / Fandoogh Rest**, plugin entrypoint and
  distributable folder to `fandoogh-rest`, and PHP namespace and translation domain.
- Preserve existing `admincafe_*` data, roles, capabilities, hooks, REST routes,
  request headers and CSS scopes for installed-site compatibility.
- Add CI, pull request and issue templates, reproducible package checks and
  documented Git merge, rollback and release procedures.
- Record future multi-branch requirements with a shared payment gateway; no
  multi-branch behavior is included in this release.

### Migration

Back up the site and database and test on staging. Deactivate AdminCafe, install
and activate Fandoogh Rest, and verify menu routes, permissions and existing
orders. Do not keep both plugins active. Do not delete the old plugin through
WordPress until its uninstall cleanup behavior has been reviewed; uninstalling
can remove shared persistent data. Keep the backup and prior ZIP for rollback.

## 1.3.0 — Imported development baseline

The first Git commit captures the supplied existing plugin. Its earlier
development history was not available and has not been reconstructed. The
historical validation record in `docs/VALIDATION.md` describes that supplied
snapshot, not new GitHub CI results.
