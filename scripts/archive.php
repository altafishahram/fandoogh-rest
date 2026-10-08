<?php
/** Stable archive ordering, timestamps and permissions for repeatable builds. */
function fr_package_version(string $root): string
{
    preg_match('/^ \* Version: ([0-9]+\.[0-9]+\.[0-9]+)$/m', file_get_contents($root . '/fandoogh-rest.php'), $match);
    if (empty($match[1])) { throw new RuntimeException('Plugin version is missing.'); }
    return $match[1];
}

function fr_package_files(string $root, array $entries): array
{
    $files = [];
    foreach ($entries as $entry) {
        $path = $root . '/' . $entry;
        if (is_file($path)) {
            $files[$entry] = $entry;
        } elseif (is_dir($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile() && !$file->isLink()) {
                    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                    $files[$relative] = $relative;
                }
            }
        } else { throw new RuntimeException('Required package input missing: ' . $entry); }
    }
    foreach ($files as $relative) {
        if (preg_match('~(?:^|/)(?:\.git|\.tools|node_modules|__pycache__|runtime|\.env(?:\.[^/]*)?|auth\.json|wp-config[^/]*\.php)(?:/|$)|\.(?:pyc|log|sqlite3?|db|sql|pem|key|p12|pfx|zip)$~i', $relative)) { unset($files[$relative]); }
    }
    ksort($files, SORT_STRING);
    return array_values($files);
}

function fr_write_archive(string $root, array $files, string $target): void
{
    if (!class_exists(ZipArchive::class)) { throw new RuntimeException('PHP ZIP extension is required.'); }
    if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true)) { throw new RuntimeException('Cannot create archive directory.'); }
    $timezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    try {
        $zip = new ZipArchive();
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) { throw new RuntimeException('Cannot create archive.'); }
        foreach ($files as $relative) {
            $name = 'fandoogh-rest/' . $relative;
            if (!$zip->addFile($root . '/' . $relative, $name)
                || !$zip->setMtimeName($name, 315532800)
                || !$zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, 0100644 << 16)
                || !$zip->setCompressionName($name, ZipArchive::CM_DEFLATE, 9)) {
                throw new RuntimeException('Cannot add archive input: ' . $relative);
            }
        }
        if (!$zip->close()) { throw new RuntimeException('Cannot finish archive.'); }
    } finally { date_default_timezone_set($timezone); }
}
