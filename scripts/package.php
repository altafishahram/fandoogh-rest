<?php
/** Build an installable ZIP from an explicit allowlist. */
require __DIR__ . '/archive.php';
$root = dirname(__DIR__);
$version = fr_package_version($root);
$required = ['fandoogh-rest.php', 'assets/menu.js', 'assets/panel.js', 'assets/fandoogh-rest.css', 'assets/builder-block.js', 'assets/icon-192.png', 'assets/icon-512.png', 'assets/FONT-LICENSE.txt', 'resources/ui-strings.php', 'resources/customer-strings.json', 'resources/customer-server-strings.json', 'vendor/autoload.php', 'languages/fandoogh-rest-fa_IR.mo', 'languages/fandoogh-rest-zh_CN.mo', 'languages/fandoogh-rest-tr_TR.mo', 'languages/checkout/woocommerce/woocommerce-fa_IR.mo', 'languages/checkout/woocommerce/woocommerce-zh_CN.mo', 'languages/checkout/woocommerce/woocommerce-tr_TR.mo', 'languages/checkout/core/zh_CN.mo', 'languages/checkout/core/tr_TR.mo', 'public/sw.js', 'README.fa.md', 'LICENSE'];
foreach ($required as $entry) {
    if (!is_file($root . '/' . $entry)) {
        throw new RuntimeException('Required build artifact missing: ' . $entry);
    }
}
$entries = ['fandoogh-rest.php', 'admincafe.php', 'uninstall.php', 'composer.json', 'composer.lock', 'readme.txt', 'README.md', 'README.fa.md', 'CHANGELOG.md', 'LICENSE', 'src', 'templates', 'assets', 'public', 'resources', 'languages', 'docs', 'vendor'];
$files = array_filter(fr_package_files($root, $entries), static fn ($relative) => !str_ends_with($relative, '.py'));
$target = $root . '/dist/fandoogh-rest-' . $version . '.zip';
fr_write_archive($root, $files, $target);
echo 'Package: ' . $target . ' | SHA256 ' . hash_file('sha256', $target) . PHP_EOL;
