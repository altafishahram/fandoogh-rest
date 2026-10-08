<?php
/** Portable development sources, without local databases, credentials or tools. */
$root = dirname(__DIR__);
preg_match('/^ \* Version: ([0-9.]+)$/m', file_get_contents($root . '/admincafe.php'), $version);
if (empty($version[1])) { throw new RuntimeException('Plugin version is missing.'); }
$target = $root . '/dist/admincafe-source-' . $version[1] . '.zip';
if (!class_exists(ZipArchive::class)) {
    throw new RuntimeException('PHP ZIP extension is required.');
}
$entries = ['admincafe.php', 'uninstall.php', 'composer.json', 'composer.lock', 'readme.txt', 'README.fa.md', 'LICENSE', '.gitignore', 'src', 'templates', 'public', 'resources', 'languages', 'docs', 'scripts', 'frontend/src', 'frontend/test', 'frontend/package.json', 'frontend/package-lock.json', 'frontend/vite.config.js', 'frontend/tailwind.config.js', 'frontend/postcss.config.js', 'frontend/index.html', 'frontend/panel.html', 'frontend/README.md', 'frontend/FONT-LICENSE.txt', 'tests/backend.php', 'tests/commerce.php', 'tests/integration.php', 'tests/multilingual-integration.php', 'tests/commerce-language-integration.php', 'tests/translation.php', 'tests/appearance.php', 'tests/wp-appearance.php', 'tests/wp-translation.php', 'tests/wp-translation-security.php', 'tests/localization.py', 'tests/customer_localization.py'];
if (!is_dir(dirname($target))) {
    mkdir(dirname($target), 0755, true);
}
$zip = new ZipArchive();
if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Cannot create source archive.');
}
foreach ($entries as $entry) {
    $path = $root . '/' . $entry;
    if (is_file($path)) {
        $zip->addFile($path, 'admincafe/' . $entry);
    } elseif (is_dir($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                $zip->addFile($file->getPathname(), 'admincafe/' . $relative);
            }
        }
    }
}
$zip->addFromString('admincafe/SOURCE.txt', "Development source archive. For WordPress installation use admincafe-" . $version[1] . ".zip.\nRun Composer install, npm ci/build and the staging/package scripts as documented in docs/ARCHITECTURE.md.\nNo local test credentials, databases, tools, node_modules or vendor dependencies are included here.\n");
$zip->close();
echo 'Source archive: ' . $target . ' | ' . filesize($target) . " bytes\n";
