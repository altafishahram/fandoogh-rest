# Development and traceable releases

## Git history

`main` contains reviewed, passing releases. Start each change on a focused branch.
Use Conventional Commit messages (`feat`, `fix`, `refactor`, `test`, `docs`,
`build`, `ci`, `chore`) and keep logical changes separately reviewable. Semantic
versions use patch for compatible fixes, minor for compatible additions and major
for breaking contracts. Every release has a changelog entry and annotated tag.

Open a pull request describing final behavior, compatibility and actual validation.
Merge using **Create a merge commit** after required checks pass. This preserves
each development stage and provides one parent commit to revert a merged feature.
For conflicts, update the feature branch from `main`, resolve deliberately and
rerun relevant tests. Never force push `main` or rewrite published release tags.
Require the CI jobs, prohibit force pushes/deletion, and enable merge commits in
GitHub repository settings. Documentation alone does not enforce those settings.

To isolate a regression: `git bisect start`, `git bisect bad <bad-tag>`,
`git bisect good <good-tag>`, test each selected revision, mark `good` or `bad`,
then `git bisect reset`. Each selected revision must be rebuilt with its lockfiles.
To roll back a merged feature, open a fix branch with
`git revert -m 1 <merge-commit>` and submit a PR. A Git revert cannot undo database
migrations or external payment effects; use a verified backup/migration procedure.

The imported baseline is a truthful snapshot. Earlier local development stages
were unavailable; Git history begins at import, rather than simulating past work.

## Local build

Use PHP 8.2+ with ZIP, GD, intl, mbstring, OpenSSL and curl; Node 24; Python 3.12;
and Composer 2. From the repository root:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
composer validate --strict
composer check-platform-reqs --no-dev
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
php tests/appearance.php
php scripts/package.php
php scripts/source-package.php
php scripts/verify-package.php
```

Build before staging because Vite clears `assets`. Composer's lockfile and npm's
lockfile are mandatory. Update dependency versions explicitly in reviewed PRs.
Inspect advisory reports and affected paths; a passing build is not an audit.

## CI and integration

CI runs PHP 8.2/8.3 standalone suites, Node 24 renderer/domain tests, translation
catalog checks, builds and package verification. A separate PHP 8.3/MySQL 8 job
installs WordPress and WooCommerce in `$RUNNER_TEMP`, enables real HPOS, blocks
mail and executes seven database suites sequentially. Its script refuses to run
outside a GitHub Actions temporary directory and creates the required disposable
marker. Local database runners also refuse a database without that marker.

The integration job pins WordPress and WooCommerce versions in its environment
and prints exact installed versions. Change these pins in a reviewed PR so an
upstream release cannot silently change a historical test environment. Reproduction
uses the workflow's pins and its runtime log. ZIP determinism applies to
identical inputs and toolchain, not arbitrary versions of ZIP/zlib/dependencies.

Never point these tests at production. They create products, staff, orders and
settings. Gateway sandbox, mobile Web Push, live translation provider, chosen
theme/Elementor and concurrent production workloads remain deployment checks.

## Release

Update plugin header/constants, Composer identity when relevant, frontend version,
readme stable tag, catalog headers and changelog together. Build from a clean
checkout; complete CI; inspect installable/source ZIPs and SHA256 hashes. Install
the ZIP on disposable staging and verify upgrade compatibility before tagging.
Create annotated `vX.Y.Z` only for a reviewed commit, push that tag and attach
the verified ZIPs/hashes to its GitHub release. Version tags identify exact source;
CI artifacts also identify the commit they were built from. Release creation is
separate from ordinary PR validation and is not performed by untrusted PR code.

Both archives use sorted paths, fixed 1980 timestamps and 0644 file permissions.
The packaging verifier changes a source file's modification time, rebuilds and
compares hashes. The source ZIP contains development files; the installable ZIP
contains built assets and production dependencies. Neither contains local site
data, credentials, screenshots, test tools or dependency caches.
