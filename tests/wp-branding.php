<?php
/** Read-only branding and saved integration checks on a disposable WordPress site. */
$loader = $argv[1] ?? '';
if (!$loader || !is_file($loader)) { throw new RuntimeException('Pass a disposable wp-load.php path.'); }
require $loader;
if (get_option('admincafe_test_environment') !== 'local-disposable') { throw new RuntimeException('Refusing non-disposable database.'); }
$passed = 0;
$check = static function (bool $condition, string $label) use (&$passed): void {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $passed++;
};
$check(FANDOOGH_REST_VERSION === '1.4.0', 'Renamed plugin loaded');
$check(ADMINCAFE_VERSION === FANDOOGH_REST_VERSION, 'Legacy constants reference the same implementation');
$check(class_exists('AdminCafe\\Core\\Settings'), 'Legacy class names autoload');
$check(AdminCafe\Core\Settings::all() === FandooghRest\Core\Settings::all(), 'Old and new classes share persisted settings');
foreach (['menu', 'categories', 'products', 'cart'] as $component) {
    $check(shortcode_exists('admincafe_' . $component) && shortcode_exists('fandoogh_rest_' . $component), 'Old and new shortcodes registered');
}
$check(WP_Block_Type_Registry::get_instance()->is_registered('admincafe/menu'), 'Saved Gutenberg block remains registered');
$check(\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled(), 'Real HPOS remains active');
$check(get_option('admincafe_db_version') === FANDOOGH_REST_VERSION, 'Existing schema version upgraded');
$header = get_file_data(FANDOOGH_REST_FILE, ['name' => 'Plugin Name', 'domain' => 'Text Domain']);
$check($header['name'] === 'رستوران فندوق', 'Persian plugin header');
$check($header['domain'] === 'fandoogh-rest', 'Renamed translation domain');
$oldMarkup = do_shortcode('[admincafe_menu]');
$newMarkup = do_shortcode('[fandoogh_rest_menu]');
$check($oldMarkup === $newMarkup && str_contains($newMarkup, 'data-admincafe-app="menu"'), 'Saved and renamed embeds render the same menu');
$check(str_contains(wp_styles()->registered['admincafe']->src, '/assets/fandoogh-rest.css'), 'New stylesheet is enqueued');
wp_set_current_user(1);
$check(current_user_can('admincafe_manage_settings'), 'Existing staff capabilities work');
$check(FandooghRest\Localization\Language::staffText('Fandoogh Rest') === 'رستوران فندوق', 'Persian panel branding');
echo "WordPress branding checks passed: $passed.\n";
