<?php
/** Run only against a disposable WordPress/Woo database. */
define('WP_HTTP_BLOCK_EXTERNAL', true);
$_SERVER['HTTP_HOST'] = '127.0.0.1:8093';
$_SERVER['REQUEST_URI'] = '/wp-json/admincafe/v1/manage/bootstrap';
$loader = $argv[1] ?? '';
if (!$loader || !is_file($loader)) { fwrite(STDERR, "Pass disposable wp-load.php.\n"); exit(1); }
require $loader;
if (get_option('admincafe_test_environment') !== 'local-disposable') { fwrite(STDERR, "Disposable database marker required.\n"); exit(1); }

use FandooghRest\Branches\Branches;
use FandooghRest\Core\Settings;

$count = 0;
$check = static function ($condition, $label) use (&$count) { if (!$condition) { throw new RuntimeException($label); } $count++; };
$oldUser = get_current_user_id(); $oldBranch = Branches::current(); $users = [];
$options = [];
foreach (['admincafe_settings', 'fandoogh_branches', 'fandoogh_branches_version', 'woocommerce_currency'] as $key) { $options[$key] = get_option($key, null); }
$call = static function ($method, $path, $branch = null, $body = null, $header = null) {
    $r = new WP_REST_Request($method, '/admincafe/v1/manage/' . $path);
    $r->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    if ($branch !== null) { $r->set_query_params(['branch_id' => $branch]); }
    if ($header !== null) { $r->set_header('X-Fandoogh-Branch', $header); }
    if ($body !== null) { $r->set_header('Content-Type', 'application/json'); $r->set_body(wp_json_encode($body)); }
    return rest_do_request($r);
};
try {
    $central = wp_insert_user(['user_login' => 'branch-admin-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'administrator']); $users[] = $central;
    $manager = wp_insert_user(['user_login' => 'branch-manager-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'admincafe_manager']); $users[] = $manager;
    update_user_meta($manager, Branches::USER_META, [1]);
    wp_set_current_user($central); Branches::setCurrent(1);
    $new = Branches::save(['name' => 'Second branch', 'slug' => 'test-' . wp_generate_uuid4()]);
    $check(!is_wp_error($new), 'Central creates independent branch'); $second = $new['id'];
    $check(!isset($new['settings']), 'Branch projection does not expose settings');
    $before = Settings::all()['restaurant_phone'];
    $saved = Branches::runFor($second, static fn() => Settings::update(['restaurant_phone' => 'second-only']));
    $check(!is_wp_error($saved) && Settings::all()['restaurant_phone'] === $before, 'Branch settings cannot overwrite default');
    $check(Branches::runFor($second, static fn() => Settings::get('restaurant_phone')) === 'second-only', 'Branch settings retain independent values');
    try { Branches::runFor($second, static function () { throw new RuntimeException('expected'); }); } catch (RuntimeException $e) {}
    $check(Branches::current() === 1, 'runFor restores after exception');
    wp_set_current_user($manager);
    $check($call('GET', 'bootstrap', $second)->get_status() === 403, 'Manager cannot select foreign branch');
    $check($call('GET', 'bootstrap', ['1'])->get_status() === 400, 'Reject array branch selection');
    $check($call('GET', 'bootstrap', '01')->get_status() === 400, 'Reject noncanonical branch selection');
    $check($call('GET', 'bootstrap', 1, null, (string) $second)->get_status() === 400, 'Reject conflicting query and header');
    $check(Branches::current() === 1, 'Rejected requests restore context');
    $check($call('POST', 'settings', 1, ['currency_code' => Settings::get('currency_code') === 'USD' ? 'EUR' : 'USD'])->get_status() === 403, 'Branch manager cannot change collection currency');
    $check($call('POST', 'branches', 1, ['name' => 'Forbidden branch'])->get_status() === 403, 'Branch manager cannot create branches');
    update_user_meta($manager, Branches::USER_META, [$second]);
    $bootstrap = $call('GET', 'bootstrap');
    $check($bootstrap->get_status() === 200 && $bootstrap->get_data()['branch_id'] === $second, 'Bootstrap chooses assigned branch without selection');
    $check(count($bootstrap->get_data()['branches']) === 1 && !$bootstrap->get_data()['can_manage_branches'], 'Bootstrap lists only assigned branches');
    $check(Branches::current() === 1, 'Successful REST requests restore context');
    wp_set_current_user($central);
    $check($call('PATCH', 'branches/1', 1, ['enabled' => false])->get_status() === 200, 'Central disables default branch');
    $check($call('GET', 'bootstrap', 1)->get_status() === 200, 'Central can open disabled branch operations panel');
    $check($call('GET', 'branches', 1)->get_status() === 200, 'Disabled default cannot lock out central branch control');
    $public = new WP_REST_Request('GET', '/admincafe/v1/bootstrap'); $public->set_query_params(['branch_id' => 1]);
    $check(rest_do_request($public)->get_status() === 404, 'Disabled branch customer menu remains unavailable');
    $check($call('PATCH', 'branches/1', 1, ['enabled' => true])->get_status() === 200, 'Central can reenable disabled default');
    echo "PASS $count branch core checks\n";
} finally {
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($users as $id) { if (!is_wp_error($id)) { wp_delete_user((int) $id); } }
    foreach ($options as $key => $value) { if ($value === null) { delete_option($key); } else { update_option($key, $value, false); } }
    wp_set_current_user($oldUser); Branches::setCurrent($oldBranch);
}
