# Changelog

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
