<?php
/** Build an installable ZIP with deterministic, explicitly allowed paths. */
$root = dirname(__DIR__);
preg_match('/^ \* Version: ([0-9.]+)$/m', file_get_contents($root . '/admincafe.php'), $version);
if (empty($version[1])) { throw new RuntimeException('Plugin version is missing.'); }
$target = $root . '/dist/admincafe-' . $version[1] . '.zip';
if (!class_exists(ZipArchive::class)) {
    fwrite(STDERR, "PHP ZIP extension is required.\n");
    exit(1);
}
foreach (['assets/menu.js', 'assets/panel.js', 'assets/admincafe.css', 'assets/builder-block.js', 'assets/icon-192.png', 'assets/icon-512.png', 'assets/FONT-LICENSE.txt', 'resources/ui-strings.php', 'resources/customer-strings.json', 'resources/customer-server-strings.json', 'vendor/autoload.php', 'languages/admincafe-fa_IR.mo', 'languages/admincafe-zh_CN.mo', 'languages/admincafe-tr_TR.mo', 'languages/checkout/woocommerce/woocommerce-fa_IR.mo', 'languages/checkout/woocommerce/woocommerce-zh_CN.mo', 'languages/checkout/woocommerce/woocommerce-tr_TR.mo', 'languages/checkout/core/zh_CN.mo', 'languages/checkout/core/tr_TR.mo', 'public/sw.js', 'README.fa.md', 'LICENSE'] as $required) {
    if (!is_file($root . '/' . $required)) {
        fwrite(STDERR, "Required build artifact is missing: $required\n");
        exit(1);
    }
}
if (!is_dir(dirname($target))) {
    mkdir(dirname($target), 0755, true);
}
$zip = new ZipArchive();
$zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE);
foreach (['admincafe.php','uninstall.php','composer.json','composer.lock','readme.txt','README.fa.md','LICENSE','src','templates','assets','public','resources','languages','docs'] as $entry) {
    $path = $root . '/' . $entry;
    if (!file_exists($path)) {
        continue;
    }
    if (is_file($path)) {
        $zip->addFile($path, 'admincafe/' . $entry);
        continue;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        if (preg_match('/\.(php|js|css|json|svg|png|jpg|woff2?|ttf|otf|txt|md|po|mo|pot)$/i', $relative)) {
            $zip->addFile($file->getPathname(), 'admincafe/' . $relative);
        }
    }
}
// Production Composer dependencies, including licenses; exclude their developer executables.
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/vendor', FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->isFile()) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        $zip->addFile($file->getPathname(), 'admincafe/' . $relative);
    }
}
$zip->close();
$check = new ZipArchive();
$check->open($target);
foreach (['admincafe/admincafe.php','admincafe/assets/menu.js','admincafe/vendor/autoload.php','admincafe/public/sw.js'] as $entry) {
    if ($check->locateName($entry) === false) {
        throw new RuntimeException('Invalid package: ' . $entry);
    }
}
echo 'Package: ' . $target . ' | ' . $check->numFiles . ' files | ' . filesize($target) . " bytes\n";
$check->close();
