<?php
/** Development sources without site data, credentials, dependencies or generated bundles. */
require __DIR__ . '/archive.php';
$root = dirname(__DIR__);
$version = fr_package_version($root);
$entries = ['fandoogh-rest.php', 'admincafe.php', 'uninstall.php', 'composer.json', 'composer.lock', 'readme.txt', 'README.md', 'README.fa.md', 'LICENSE', 'CHANGELOG.md', 'CONTRIBUTING.md', 'SECURITY.md', '.gitignore', '.gitattributes', '.editorconfig', '.github', 'src', 'templates', 'public', 'resources', 'languages', 'docs', 'scripts', 'tests', 'frontend/src', 'frontend/test', 'frontend/scripts', 'frontend/package.json', 'frontend/package-lock.json', 'frontend/vite.config.js', 'frontend/tailwind.config.js', 'frontend/postcss.config.js', 'frontend/index.html', 'frontend/panel.html', 'frontend/README.md', 'frontend/FONT-LICENSE.txt'];
$target = $root . '/dist/fandoogh-rest-source-' . $version . '.zip';
fr_write_archive($root, fr_package_files($root, $entries), $target);
echo 'Source archive: ' . $target . ' | SHA256 ' . hash_file('sha256', $target) . PHP_EOL;
