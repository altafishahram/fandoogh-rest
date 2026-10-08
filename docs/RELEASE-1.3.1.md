# Fandoogh Rest 1.3.1 publication

## Scope and compatibility

This release renames the supplied plugin to **رستوران فندوق / Fandoogh Rest**
and establishes traceable GitHub development. It does not add multi-branch
behavior. Future requirements are in [the branch plan](MULTIBRANCH-PLAN.md).

The bootstrap is `fandoogh-rest.php`, PHP namespace `FandooghRest`, primary
constants `FANDOOGH_REST_*`, translation domain `fandoogh-rest`, built stylesheet
`assets/fandoogh-rest.css` and ZIP directory `fandoogh-rest/`. The old entrypoint
is a headerless compatibility loader. Existing `ADMINCAFE_*` constants remain
aliases; configured translation/removal flags accept legacy names.

Stored settings, database tables, roles/capabilities, product and order metadata,
REST namespace `admincafe/v1`, `X-AdminCafe-Token`, registered block identity,
hooks and CSS scopes retain their existing names. Existing QR links and menu
routes are preserved. New `fandoogh_rest_*` shortcodes coexist with saved
`admincafe_*` shortcodes. Do not rename stored identifiers with a bulk replacement.

## Upgrade and rollback

1. Back up the database and current plugin files; retain the previous installable ZIP.
2. Test on staging with the actual theme, builder, gateway and cache configuration.
3. Deactivate the old plugin before activating the new installable ZIP. The new
   bootstrap rejects a simultaneously active legacy installation.
4. Verify existing products/orders, roles, saved settings, builder menu pages,
   QR URLs and checkout behavior. Verify configured credentials without printing them.
5. Review old uninstall configuration before deleting the previous plugin:
   optional cleanup flags can remove shared settings/roles/events.

If rollback is needed, deactivate the new plugin, restore the previous ZIP and
activate it. Use the database backup if a deployment changed persistent data.
A Git revert alone does not reverse live order/payment effects.

## Validation evidence

Historical validation of the imported source is in [VALIDATION.md](VALIDATION.md).
Fresh results must be reported separately with the tested commit and environment;
do not present historical browser/provider checks as newly executed.

CI checks syntax, PHP behavior and three bootstrap compatibility modes,
Node renderer/domain tests, locale keys/placeholders/catalogs, source publication
audit, dependency advisories, tracked generated-file consistency and deterministic
installable/source archives. Its separate MySQL job installs pinned official
WordPress/WooCommerce versions, prints exact versions, enables HPOS and runs seven
integration suites sequentially. Workflow runs and artifact names identify the
source commit. Fresh passing results are recorded in [VALIDATION.md](VALIDATION.md).

Release packages exclude credentials, databases, local tools and screenshots.
Identical inputs/toolchain reproduce archive ordering, timestamps and permissions;
SHA256 hashes accompany published artifacts. Live bank sandbox/refunds, browser
Push delivery, real provider setup and target builder/theme remain deployment checks.
