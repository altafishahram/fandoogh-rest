# Fandoogh Rest — رستوران فندوق

A Persian-first WordPress/WooCommerce plugin for restaurant menus and operations.
WooCommerce owns products, inventory, orders and payments. The plugin adds a
customer menu, staff panel, tables, localized content, imports and notifications.

**Version: 1.4.0.** Multi-branch menus and operations share one
WooCommerce store, payment gateway and currency. Each branch has independent
products, categories, tables, customer translations and appearance.
See [multi-branch setup and boundaries](docs/MULTIBRANCH-PLAN.md).

- [Persian documentation](README.fa.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Development and Git workflow](docs/DEVELOPMENT.md)
- [Release notes and compatibility](CHANGELOG.md)
- [Security reporting](SECURITY.md)
- [Historical validation](docs/VALIDATION.md)
- [Multi-branch validation](docs/VALIDATION-1.4.0.md)

## Install

Use the installable `fandoogh-rest-1.4.0.zip`, not GitHub's source-code ZIP.
Upload it through WordPress Plugins → Add New → Upload Plugin. PHP 8.2+, WordPress
6.5+ and WooCommerce 9.0+ are declared minimums; tested environments are recorded
separately. These declarations do not imply every minimum-version combination
has been tested. See the migration instructions before replacing an existing
AdminCafe installation.

## Build

See [development instructions](docs/DEVELOPMENT.md) for locked dependencies,
tests, integration setup and release packaging. Source excludes credentials,
local WordPress installations, dependencies and generated frontend bundles.

Licensed under GPL-2.0-or-later. Bundled dependencies retain their licenses.
