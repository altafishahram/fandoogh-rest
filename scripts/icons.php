<?php
$root = dirname(__DIR__);
function ac_coordinates(array $values): array {
    return array_map(static fn($value): int => (int) round($value), $values);
}
foreach ([192,512] as $size) {
    $image = imagecreatetruecolor($size, $size);
    $background = imagecolorallocate($image, 250, 248, 245);
    $accent = imagecolorallocate($image, 190, 111, 71);
    $cream = imagecolorallocate($image, 255, 247, 235);
    imagefill($image, 0, 0, $background);
    imagefilledellipse($image, ...[...ac_coordinates([$size / 2, $size / 2, $size * .80, $size * .80]), $accent]);
    imagefilledellipse($image, ...[...ac_coordinates([$size * .68, $size * .51, $size * .24, $size * .28]), $cream]);
    imagefilledellipse($image, ...[...ac_coordinates([$size * .68, $size * .51, $size * .14, $size * .18]), $accent]);
    imagefilledrectangle($image, ...[...ac_coordinates([$size * .31, $size * .36, $size * .62, $size * .55]), $cream]);
    imagefilledellipse($image, ...[...ac_coordinates([$size * .465, $size * .55, $size * .31, $size * .25]), $cream]);
    imagefilledellipse($image, ...[...ac_coordinates([$size * .465, $size * .365, $size * .31, $size * .08]), $background]);
    imagefilledellipse($image, ...[...ac_coordinates([$size * .465, $size * .72, $size * .46, $size * .035]), $cream]);
    imagesetthickness($image, max(2, (int) ($size * .015)));
    imagearc($image, ...[...ac_coordinates([$size * .43, $size * .24, $size * .08, $size * .12]), -80, 80, $cream]);
    imagearc($image, ...[...ac_coordinates([$size * .53, $size * .22, $size * .08, $size * .12]), -80, 80, $cream]);
    imagepng($image, $root . '/assets/icon-' . $size . '.png');
    imagedestroy($image);
}
echo "PWA icons generated.\n";
