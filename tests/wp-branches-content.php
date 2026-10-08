<?php
/** Cross-branch content, embeds, imports and asynchronous translation regression. */
define('WP_HTTP_BLOCK_EXTERNAL', true);
$_SERVER['HTTP_HOST'] = '127.0.0.1:8093';
$_SERVER['REQUEST_URI'] = '/wp-json/admincafe/v1/bootstrap';
$loader = $argv[1] ?? '';
if (!$loader || !is_file($loader)) { fwrite(STDERR, "Pass disposable wp-load.php.\n"); exit(1); }
require $loader;
if (get_option('admincafe_test_environment') !== 'local-disposable') { throw new RuntimeException('Disposable marker required.'); }
add_filter('pre_wp_mail', '__return_true');

use FandooghRest\Branches\Branches;
use FandooghRest\Core\Settings;
use FandooghRest\Menu\Catalog;
use FandooghRest\Integrations\Builders;
use FandooghRest\Translation\Config;
use FandooghRest\Translation\Queue;
use FandooghRest\Translation\Source;
use FandooghRest\Translation\Provider;

$checks = 0;
$check = static function ($ok, $message) use (&$checks): void { if (!$ok) { throw new RuntimeException('FAIL: ' . $message); } $checks++; };
$priorUser = get_current_user_id(); $priorBranch = Branches::current();
$options = [];
foreach (['fandoogh_branches', 'admincafe_settings', Config::OPTION, 'admincafe_translation_usage'] as $key) { $options[$key] = get_option($key, null); }
$users = $products = $categories = $jobs = $privateOptions = [];
$call = static function ($method, $path, $branch, $body = null) {
    $r = new WP_REST_Request($method, '/admincafe/v1/' . $path);
    $r->set_query_params(['branch_id' => $branch]);
    if (get_current_user_id()) { $r->set_header('X-WP-Nonce', wp_create_nonce('wp_rest')); }
    if ($body !== null) { $r->set_header('Content-Type', 'application/json'); $r->set_body(wp_json_encode($body)); }
    return rest_do_request($r);
};
$provider = static fn() => new class implements Provider {
    public function translate(array $texts, string $target): array|WP_Error { return array_map(static fn($text) => $target . ':' . $text, $texts); }
};
global $wpdb;
$firstJob = (int) $wpdb->get_var('SELECT COALESCE(MAX(id),0) FROM ' . Queue::table());
try {
    $admin = wp_insert_user(['user_login' => 'content-admin-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'administrator']); $users[] = $admin;
    wp_set_current_user($admin);
    $b = Branches::save(['name' => 'Content branch', 'slug' => 'content-' . wp_generate_uuid4()]);
    $check(!is_wp_error($b), 'New content branch'); $bid = $b['id'];
    $manager = wp_insert_user(['user_login' => 'content-manager-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'admincafe_manager']); $users[] = $manager;
    update_user_meta($manager, Branches::USER_META, [$bid]);
    $name = 'Independent category ' . wp_generate_uuid4();
    foreach ([1, $bid] as $branch) {
        $term = $call('POST', 'manage/categories', $branch, ['name' => $name]);
        $check($term->get_status() === 200, 'Same category name accepted independently');
        $categories[$branch] = $term->get_data()['id'];
        Branches::setCurrent($branch);
        $saved = Catalog::saveProduct(['name' => 'Food ' . $branch, 'price' => (string) (100 * $branch), 'category_ids' => [$categories[$branch]], 'translations' => ['en' => ['name' => 'Food EN ' . $branch]]]);
        $check(!is_wp_error($saved), 'Branch product with independent category'); $products[$branch] = $saved['id'];
        Settings::update(['menu_category_ids' => [], 'menu_theme' => $branch === 1 ? 'cafe' : 'garden', 'custom_css' => ['card' => 'color: #123456;']]);
    }
    $check($categories[1] !== $categories[$bid], 'Category identity independent');
    Branches::setCurrent($bid);
    $check(is_wp_error(Catalog::saveProduct(['category_ids' => [$categories[1]]], $products[$bid])), 'Reject foreign category before product write');
    $check(is_wp_error(Catalog::saveProduct(['name' => 'Hijacked'], $products[1])), 'Reject foreign product write');
    wp_set_current_user($manager);
    foreach (['GET', 'PATCH', 'DELETE'] as $method) {
        $check($call($method, 'manage/products/' . $products[1], $bid, $method === 'PATCH' ? ['price' => '1'] : null)->get_status() === 404, 'Foreign product ID denied: ' . $method);
    }
    $check($call('PATCH', 'manage/categories/' . $categories[1], $bid, ['name' => 'Hijacked'])->get_status() === 404, 'Foreign category ID denied');
    $check($call('POST', 'manage/reorder', $bid, ['category_id' => $categories[$bid], 'ids' => [$products[1]]])->get_status() === 400, 'Foreign reorder denied');
    $check($call('GET', 'manage/products', $bid)->get_status() === 200, 'Own management catalog allowed');
    $ownIds = array_column($call('GET', 'manage/products', $bid)->get_data(), 'id');
    $check(in_array($products[$bid], $ownIds, true) && !in_array($products[1], $ownIds, true), 'Management catalog isolated');

    // A preview token is bound to both its uploader and branch, even for a multi-branch admin.
    wp_set_current_user($admin);
    $token = bin2hex(random_bytes(24)); $key = 'ac_import_' . hash('sha256', $token);
    set_transient($key, ['user' => $admin, 'branch_id' => 1, 'columns' => ['Name'], 'rows' => [['Food']], 'cursor' => 0, 'result' => []], 60);
    $check($call('POST', 'manage/import/apply', $bid, ['token' => $token, 'mapping' => ['name' => 'Name']])->get_status() === 410, 'Foreign import preview token denied');
    delete_transient($key);

    wp_set_current_user(0);
    foreach (['fa', 'en', 'zh', 'tr'] as $lang) {
        $r = new WP_REST_Request('GET', '/admincafe/v1/bootstrap'); $r->set_query_params(['branch_id' => $bid, 'lang' => $lang]);
        $boot = rest_do_request($r);
        $check($boot->get_status() === 200, 'Guest branch bootstrap: ' . $lang);
        $ids = array_column($boot->get_data()['products'], 'id');
        $check(in_array($products[$bid], $ids, true) && !in_array($products[1], $ids, true), 'Guest content isolation: ' . $lang);
        $check(str_contains($boot->get_data()['settings']['menu_custom_css'], 'data-fandoogh-branch="' . $bid . '"'), 'Branch-scoped public CSS: ' . $lang);
    }
    $builders = new Builders();
    foreach ([1, $bid] as $branch) {
        $html = $builders->shortcode(['branch' => (string) $branch]);
        preg_match('/data-admincafe-config="([^"]+)"/', $html, $match);
        $config = json_decode(html_entity_decode($match[1] ?? '', ENT_QUOTES, 'UTF-8'), true);
        $check($config['branchId'] === $branch && str_contains($html, 'data-fandoogh-branch="' . $branch . '"'), 'Embed branch identity');
        $check(str_contains($config['appearance']['menu_custom_css'], 'data-fandoogh-branch="' . $branch . '"'), 'Embed CSS does not cross branch');
    }
    $check(!str_contains($builders->shortcode(['branch' => 'missing-branch']), 'data-admincafe-config'), 'Invalid embed fails closed');

    wp_set_current_user($admin); Branches::setCurrent($bid);
    $config = Config::update(['api_key' => str_repeat('fake_test_key_', 3), 'enabled' => true]);
    $check(!is_wp_error($config), 'Shared test translation provider configured');
    add_filter('admincafe_translation_provider', $provider);
    Settings::update(['restaurant_name' => 'Translate second branch', 'content_translations' => ['en' => ['restaurant_name' => ''], 'zh' => ['restaurant_name' => ''], 'tr' => ['restaurant_name' => '']]]);
    // Explicit empty editor entries are manual overrides; clear only this fixture's metadata.
    foreach ([Source::MANUAL, Source::PROVENANCE, '_admincafe_translation_sources'] as $meta) { $privateOptions[] = Source::optionKey($meta); delete_option(Source::optionKey($meta)); }
    $defaultTranslations = Branches::runFor(1, static fn() => Settings::get('content_translations'));
    $job = Queue::enqueue('settings', $bid);
    if (!$job) { $job = (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . Queue::table() . ' WHERE branch_id=%d AND scope=%s ORDER BY id DESC LIMIT 1', $bid, 'settings')); }
    $check($job > 0, 'Translation job retains branch');
    Branches::setCurrent(1);
    for ($i = 0; $i < 5; $i++) { Queue::work($job); }
    $check(Branches::current() === 1, 'Worker restores caller branch');
    $translated = Branches::runFor($bid, static fn() => Settings::get('content_translations'));
    $check(($translated['en']['restaurant_name'] ?? '') === 'en:Translate second branch', 'Worker translates correct branch settings');
    $check(Settings::get('content_translations') === $defaultTranslations, 'Worker leaves default translations intact');
    $status = Branches::runFor($bid, static fn() => Queue::status());
    $check(in_array($job, array_map('intval', array_column($status['jobs'], 'id')), true), 'Own translation job visible');
    $check(!in_array($job, array_map('intval', array_column(Queue::status()['jobs'], 'id')), true), 'Other branch translation job hidden');
    wp_set_current_user($manager);
    $check($call('POST', 'manage/translation/settings', $bid, ['remove_key' => true])->get_status() === 403, 'Branch manager cannot change shared provider');
    echo "PASS $checks branch content checks\n";
} finally {
    remove_filter('admincafe_translation_provider', $provider);
    $wpdb->query($wpdb->prepare('DELETE FROM ' . Queue::table() . ' WHERE id>%d', $firstJob));
    foreach ($privateOptions as $key) { delete_option($key); }
    foreach ($products as $id) { $p = wc_get_product($id); if ($p) { $p->delete(true); } }
    foreach ($categories as $id) { wp_delete_term($id, 'product_cat'); }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($users as $id) { if (!is_wp_error($id)) { wp_delete_user($id); } }
    foreach ($options as $key => $value) { if ($value === null) { delete_option($key); } else { update_option($key, $value, false); } }
    wp_set_current_user($priorUser); Branches::setCurrent($priorBranch);
}
