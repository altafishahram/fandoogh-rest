#!/usr/bin/env bash
# GitHub Actions only: never accepts a production wp-load.php path.
set -euo pipefail
if [[ "${GITHUB_ACTIONS:-}" != "true" || -z "${RUNNER_TEMP:-}" ]]; then
  echo 'Run this setup only in the GitHub Actions disposable runner.' >&2
  exit 1
fi
root="$(pwd)"
site="${RUNNER_TEMP}/fandoogh-rest-wp"
if [[ -e "$site" ]]; then
  echo 'Disposable site path already exists; refusing to reuse it.' >&2
  exit 1
fi
mkdir -p "$site"
wp --path="$site" core download --version="${WP_VERSION:?Pinned WP_VERSION is required}"
wp --path="$site" config create --dbname=fandoogh_ci --dbuser=root --dbpass=ci-only --dbhost=127.0.0.1:3306
wp --path="$site" config set DISABLE_WP_CRON true --raw
wp --path="$site" core install --url=http://127.0.0.1:8093 --title='Disposable Fandoogh Rest CI' --admin_user=ci-admin --admin_password=ci-disposable-password --admin_email=ci@example.invalid --skip-email
mkdir -p "$site/wp-content/mu-plugins"
cat > "$site/wp-content/mu-plugins/ci-isolation.php" <<'PHP'
<?php
add_filter('pre_wp_mail', '__return_true');
PHP
wp --path="$site" plugin install woocommerce --version="${WOOCOMMERCE_VERSION:?Pinned WOOCOMMERCE_VERSION is required}" --activate
wp --path="$site" option update woocommerce_currency IRT
wp --path="$site" option update woocommerce_default_country IR
wp --path="$site" rewrite structure '/%postname%/'
wp --path="$site" option update admincafe_test_environment local-disposable
wp --path="$site" option update woocommerce_custom_orders_table_enabled yes
wp --path="$site" option update woocommerce_custom_orders_table_data_sync_enabled no
wp --path="$site" eval 'WC_Install::create_tables(); WC_Install::create_pages();'
wp --path="$site" plugin install "$root"/dist/fandoogh-rest-[0-9]*.zip --activate
wp --path="$site" core version
wp --path="$site" plugin get woocommerce --field=version
wp --path="$site" plugin get fandoogh-rest --field=version
for suite in integration multilingual-integration commerce-language-integration wp-translation wp-translation-security wp-appearance wp-branding; do
  php "$root/tests/$suite.php" "$site/wp-load.php"
done
