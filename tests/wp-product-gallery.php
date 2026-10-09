<?php
/** Real Woo product gallery CRUD, public data and branch isolation. */
define('WP_HTTP_BLOCK_EXTERNAL', true);
$_SERVER['HTTP_HOST'] = '127.0.0.1:8093';
$_SERVER['REQUEST_URI'] = '/wp-json/admincafe/v1/bootstrap';
$loader = $argv[1] ?? '';
if (!$loader || !is_file($loader)) { fwrite(STDERR, "Pass disposable wp-load.php.\n"); exit(1); }
require $loader;
if (get_option('admincafe_test_environment') !== 'local-disposable') { throw new RuntimeException('Disposable marker required.'); }

use FandooghRest\Branches\Branches;
use FandooghRest\Menu\Catalog;

$checks = 0;
$check = static function ($ok, $message) use (&$checks): void { if (!$ok) { throw new RuntimeException('FAIL: ' . $message); } $checks++; };
$priorUser = get_current_user_id();
$priorBranch = Branches::current();
$attachments = [];
$productId = $user = 0;
try {
    $user = wp_insert_user(['user_login' => 'gallery-test-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'administrator']);
    wp_set_current_user($user);
    Branches::setCurrent(Branches::defaultId());
    foreach (['Primary', 'Side', 'Detail', 'Foreign'] as $name) {
        $id = wp_insert_attachment(['post_title' => $name, 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit']);
        $attachments[] = $id;
        update_post_meta($id, '_wp_attached_file', 'gallery-test-' . $id . '.jpg');
        wp_update_attachment_metadata($id, ['width' => 1200, 'height' => 800, 'file' => 'gallery-test-' . $id . '.jpg', 'sizes' => []]);
        update_post_meta($id, '_wp_attachment_image_alt', $name . ' food');
        update_post_meta($id, Branches::META, $name === 'Foreign' ? 999999 : Branches::current());
    }
    [$primary, $side, $detail, $foreign] = $attachments;
    $saved = Catalog::saveProduct(['name' => 'Gallery test food', 'price' => '10', 'image_id' => $primary, 'gallery_image_ids' => [$side, $detail]]);
    $check(!is_wp_error($saved), 'Create real Woo product with gallery');
    $productId = $saved['id'];
    $check(wc_get_product($productId)->get_gallery_image_ids() === [$side, $detail], 'Woo stores ordered gallery IDs');
    $check(array_column($saved['images'], 'id') === [$primary, $side, $detail], 'Public gallery contains primary then side images');
    $image = $saved['images'][0];
    $source = wp_get_attachment_image_src($primary, 'large');
    $check($image['alt'] === 'Primary food' && $image['width'] === (int) $source[1] && $image['height'] === (int) $source[2]
        && $image['width'] > 0 && $image['width'] <= 1200 && $image['height'] > 0 && $image['height'] <= 800
        && abs($image['width'] / $image['height'] - 1.5) < 0.01, 'Public gallery carries dimensions of its selected large image and preserves aspect ratio');
    $updated = Catalog::saveProduct(['gallery_image_ids' => [$detail, $side]], $productId);
    $check(array_column($updated['images'], 'id') === [$primary, $detail, $side], 'Reorder reaches public gallery');
    Catalog::saveProduct(['price' => '11'], $productId);
    $check(wc_get_product($productId)->get_gallery_image_ids() === [$detail, $side], 'Unrelated update retains gallery');
    $shortened = Catalog::saveProduct(['gallery_image_ids' => [$detail]], $productId);
    $check(!is_wp_error($shortened) && $shortened['gallery_image_ids'] === [$detail]
        && array_column($shortened['images'], 'id') === [$primary, $detail]
        && wc_get_product($productId)->get_gallery_image_ids() === [$detail], 'Shorter gallery replaces old IDs in both response and cached product');
    foreach ([[$foreign], [$side, $side], [(string) $side], [0], null] as $invalid) {
        $result = Catalog::saveProduct(['name' => 'Forbidden change', 'gallery_image_ids' => $invalid], $productId);
        $check(is_wp_error($result) && wc_get_product($productId)->get_name() === 'Gallery test food', 'Invalid or foreign image rejected atomically');
    }
    $product = wc_get_product($productId);
    $product->set_gallery_image_ids([$side, $primary, $side, 999999999]);
    $check(array_column(Catalog::serialize($product)['images'], 'id') === [$primary, $side], 'Historic duplicate and invalid media omitted publicly');
    $cleared = Catalog::saveProduct(['gallery_image_ids' => []], $productId);
    $check(!is_wp_error($cleared), 'Clearing gallery succeeds: ' . (is_wp_error($cleared) ? $cleared->get_error_code() . ' ' . $cleared->get_error_message() : 'OK'));
    $check(array_column($cleared['images'], 'id') === [$primary], 'Clearing gallery preserves primary: ' . wp_json_encode([
        'expected_primary' => $primary, 'image_id' => $cleared['image_id'], 'ids' => array_column($cleared['images'], 'id'),
        'gallery' => $cleared['gallery_image_ids'], 'stored_primary' => get_post_meta($productId, '_thumbnail_id', true),
        'stored_gallery' => get_post_meta($productId, '_product_image_gallery', true), 'primary_is_image' => wp_attachment_is_image($primary),
        'source' => wp_get_attachment_image_src($primary, 'large'),
    ]));
    wp_set_current_user(0);
    $request = new WP_REST_Request('PATCH', '/admincafe/v1/manage/products/' . $productId);
    $request->set_header('Content-Type', 'application/json');
    $request->set_body(wp_json_encode(['gallery_image_ids' => [$side]]));
    $check(rest_do_request($request)->get_status() >= 400 && wc_get_product($productId)->get_gallery_image_ids() === [], 'Anonymous gallery write denied');
    echo "WordPress gallery tests: {$checks} passed\n";
} finally {
    wp_set_current_user($priorUser);
    Branches::setCurrent($priorBranch);
    if ($productId) { wp_delete_post($productId, true); }
    foreach ($attachments as $id) { wp_delete_attachment($id, true); }
    if ($user && !is_wp_error($user)) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user($user); }
}
