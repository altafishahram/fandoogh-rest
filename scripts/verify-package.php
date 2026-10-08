<?php
/** Inspect package contents and verify rebuilds ignore filesystem timestamps. */
require __DIR__ . '/archive.php';
$root = dirname(__DIR__);
$version = fr_package_version($root);
$required = ['fandoogh-rest/fandoogh-rest.php', 'fandoogh-rest/assets/menu.js', 'fandoogh-rest/assets/panel.js', 'fandoogh-rest/assets/fandoogh-rest.css', 'fandoogh-rest/vendor/autoload.php', 'fandoogh-rest/public/sw.js'];
foreach (['fandoogh-rest-' . $version, 'fandoogh-rest-source-' . $version] as $base) {
    $zip = new ZipArchive();
    $path = $root . '/dist/' . $base . '.zip';
    if ($zip->open($path) !== true) { throw new RuntimeException('Missing archive: ' . $path); }
    $source = str_contains($base, '-source-');
    if (!$source) {
        foreach ($required as $entry) {
            if ($zip->locateName($entry) === false) { throw new RuntimeException('Missing package entry: ' . $entry); }
        }
    }
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (!str_starts_with($name, 'fandoogh-rest/') || str_contains($name, '..')
            || preg_match('~(?:^|/)(?:\.git|\.tools|node_modules|__pycache__|runtime|\.env(?:\.[^/]*)?|auth\.json|wp-config[^/]*\.php)(?:/|$)|\.(?:log|sqlite3?|db|sql|pem|key|p12|pfx)$~i', $name)
            || (!$source && preg_match('~^fandoogh-rest/(?:frontend|tests|scripts|\.github)/~', $name))) {
            throw new RuntimeException('Forbidden package entry: ' . $name);
        }
        $names[] = $name;
    }
    $sorted = $names;
    sort($sorted, SORT_STRING);
    if ($names !== $sorted || count($names) !== count(array_unique($names))) { throw new RuntimeException('Unstable or duplicate archive entries.'); }
    $zip->close();
}
$input = $root . '/fandoogh-rest.php';
$originalMtime = filemtime($input);
$before = [];
foreach (['package.php', 'source-package.php'] as $script) {
    $base = $script === 'package.php' ? 'fandoogh-rest-' : 'fandoogh-rest-source-';
    $before[$script] = hash_file('sha256', $root . '/dist/' . $base . $version . '.zip');
}
try {
    touch($input, 1700000000);
    foreach ($before as $script => $hash) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/' . $script), $status);
        $base = $script === 'package.php' ? 'fandoogh-rest-' : 'fandoogh-rest-source-';
        if ($status !== 0 || hash_file('sha256', $root . '/dist/' . $base . $version . '.zip') !== $hash) { throw new RuntimeException('Archive rebuild changed bytes: ' . $script); }
    }
} finally { touch($input, $originalMtime); }
echo "Package contents and deterministic rebuild verified.\n";
