<?php
/** Disposable local WordPress/Woo multilingual regression: php this-file path/to/wp-load.php */
$_SERVER['HTTP_HOST'] = '127.0.0.1:8093';
$_SERVER['REQUEST_URI'] = '/';
if (!defined('ABSPATH')) {
    $loader = $argv[1] ?? '';
    if (!$loader || !is_file($loader)) { fwrite(STDERR, "Pass a disposable wp-load.php path.\n"); exit(1); }
    require $loader;
}
if (get_option('admincafe_test_environment') !== 'local-disposable') { fwrite(STDERR, "Disposable environment marker required.\n"); exit(1); }
if (!class_exists(\FandooghRest\Localization\Language::class)) { fwrite(STDERR, "Sync the multilingual plugin source before running.\n"); exit(1); }
set_exception_handler(static function (Throwable $error): void { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); });
add_filter('pre_wp_mail', '__return_true');
use FandooghRest\Core\Settings;
use FandooghRest\Core\Security;
use FandooghRest\Menu\Catalog;
use FandooghRest\Localization\Language;
use FandooghRest\Commerce\Orders;
use FandooghRest\Tables\Tables;
$passed = 0;
function ac_ml_check(bool $ok, string $label): void {
    global $passed;
    if (!$ok) { throw new RuntimeException('FAIL after ' . $passed . ' checks: ' . $label); }
    $passed++;
}
function ac_ml_request(string $method, string $path, array $body = [], array $params = []): WP_REST_Request {
    $_SERVER['REQUEST_URI'] = '/wp-json/admincafe/v1' . $path;
    $request = new WP_REST_Request($method, '/admincafe/v1' . $path);
    if (in_array($method, ['POST', 'PATCH'], true)) {
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode($body));
    }
    foreach ($params as $key => $value) { $request->set_param($key, $value); }
    if (is_user_logged_in()) { $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest')); }
    return $request;
}
function ac_ml_find(array $rows, int $id): array {
    foreach ($rows as $row) { if ((int) $row['id'] === $id) { return $row; } }
    throw new RuntimeException('Fixture missing from response: ' . $id);
}
wp_set_current_user(1);
$settings = Settings::update(['enabled_languages' => ['fa', 'en', 'zh', 'tr'], 'default_language' => 'fa', 'dine_in_enabled' => true, 'pickup_enabled' => true, 'delivery_enabled' => true, 'ordering_paused' => false,
    'content_translations' => ['en' => ['restaurant_name' => 'Aram Cafe', 'tagline' => 'Coffee and calm', 'messages' => ['order_received' => 'Your order is awaiting staff approval.']], 'zh' => ['restaurant_name' => '安然咖啡馆', 'tagline' => '咖啡与宁静'], 'tr' => ['restaurant_name' => 'Aram Kafe', 'tagline' => 'Kahve ve huzur']]]);
ac_ml_check(!is_wp_error($settings), 'Settings translations saved through real validation');
$settings_before = Settings::all();
ac_ml_check(is_wp_error(Settings::update(['content_translations' => ['fa' => ['restaurant_name' => 'Invalid']]])) && Settings::all() === $settings_before, 'Canonical Persian translation map is rejected without settings mutation');
ac_ml_check(is_wp_error(Settings::update(['content_translations' => ['en' => ['restaurant_name' => ['Invalid']]]])), 'Malformed settings translation rejected');
ac_ml_check(is_wp_error(Settings::update(['content_translations' => ['en' => ['messages' => ['order_received' => ['Invalid']]]]])), 'Nested customer message type rejected');
ac_ml_check(is_wp_error(Settings::update(['content_translations' => ['de' => ['restaurant_name' => 'Invalid language']]])), 'Unknown settings translation language rejected');
ac_ml_check(is_wp_error(Settings::update(['content_translations' => ['zh' => ['restaurant_name' => str_repeat('a', 301)]]])), 'Settings translation length bound enforced');
ac_ml_check(is_wp_error(Settings::update(['enabled_languages' => ['fa', 'xx']])), 'Unknown enabled customer language rejected');
ac_ml_check(is_wp_error(Settings::update(['enabled_languages' => ['fa', ['en']]])), 'Nested enabled language type rejected');
ac_ml_check(is_wp_error(Settings::update(['enabled_languages' => ['en'], 'default_language' => 'fa'])), 'Default customer language must be enabled');
$disabled = Settings::update(['enabled_languages' => ['fa', 'en'], 'default_language' => 'fa']);
ac_ml_check(!is_wp_error($disabled) && Language::enabled() === ['fa', 'en'] && Settings::get('enabled_languages') === ['fa', 'en'], 'Enabled language lists replace defaults without reintroducing disabled entries');
ac_ml_check(is_wp_error(Settings::update(['default_language' => 'zh'])) && Settings::get('default_language') === 'fa', 'Disabled default language rejected without mutation');
Settings::update(['enabled_languages' => ['fa', 'en', 'zh', 'tr'], 'default_language' => 'fa']);
$product_id = wc_get_product_id_by_sku('AC-LOCAL-0');
if (!$product_id) {
    $seed = Catalog::saveProduct(['name' => 'لاته وانیل', 'description' => 'اسپرسو و شیر', 'price' => '145000', 'sku' => 'AC-LOCAL-0', 'available' => true, 'visible' => true]);
    if (is_wp_error($seed)) { throw new RuntimeException($seed->get_error_message()); }
    $product_id = $seed['id'];
}
$product = wc_get_product($product_id);
$canonical_name = $product->get_name(); $canonical_description = wp_strip_all_tags($product->get_description());
$term = term_exists('قهوه گرم', 'product_cat') ?: wp_insert_term('قهوه گرم', 'product_cat');
$category_id = (int) (is_array($term) ? $term['term_id'] : $term);
$saved = Catalog::saveProduct(['category_ids' => array_unique(array_merge($product->get_category_ids(), [$category_id])), 'available' => true, 'visible' => true,
    'translations' => ['en' => ['name' => 'Vanilla latte', 'description' => 'Espresso, milk, and vanilla'], 'zh' => ['name' => '香草拿铁', 'description' => '浓缩咖啡、牛奶和香草'], 'tr' => ['name' => 'Vanilyalı latte', 'description' => 'Espresso, süt ve vanilya']]], $product_id);
ac_ml_check(!is_wp_error($saved), 'Real Woo product translations saved');
$product = wc_get_product($product_id); $canonical_price = $product->get_price();
ac_ml_check($product->get_name() === $canonical_name && wp_strip_all_tags($product->get_description()) === $canonical_description, 'Translation save leaves Persian Woo fields canonical');
$category_request = ac_ml_request('PATCH', '/manage/categories/' . $category_id, ['translations' => ['en' => ['name' => 'Hot coffee'], 'zh' => ['name' => '热咖啡'], 'tr' => ['name' => 'Sıcak kahve']]]);
$category_response = rest_do_request($category_request);
ac_ml_check($category_response->get_status() === 200, 'Category translation REST save succeeds');
$variable_id = wc_get_product_id_by_sku('AC-MULTI-VARIABLE');
$variable = $variable_id ? wc_get_product($variable_id) : new WC_Product_Variable();
$variable->set_name('لاته ویژه'); $variable->set_status('publish'); $variable->set_sku('AC-MULTI-VARIABLE'); $variable->set_category_ids([$category_id]);
$attribute = new WC_Product_Attribute(); $attribute->set_name('size'); $attribute->set_options(['بزرگ']); $attribute->set_variation(true); $attribute->set_visible(true);
$variable->set_attributes([$attribute]); $variable_id = $variable->save();
$variation_id = wc_get_product_id_by_sku('AC-MULTI-VARIANT');
$variation = $variation_id ? wc_get_product($variation_id) : new WC_Product_Variation();
$variation->set_parent_id($variable_id); $variation->set_status('publish'); $variation->set_sku('AC-MULTI-VARIANT'); $variation->set_attributes(['size' => 'بزرگ']); $variation->set_regular_price('175000'); $variation->set_price('175000'); $variation->set_stock_status('instock');
$variation_id = $variation->save(); WC_Product_Variable::sync($variable_id); wc_delete_product_transients($variable_id);
$variant_response = rest_do_request(ac_ml_request('PATCH', '/manage/products/' . $variable_id, ['translations' => ['en' => ['name' => 'Special latte'], 'zh' => ['name' => '特色拿铁'], 'tr' => ['name' => 'Özel latte']],
    'variation_translations' => [$variation_id => ['en' => ['name' => 'Special latte - Large'], 'zh' => ['name' => '特色拿铁 - 大杯'], 'tr' => ['name' => 'Özel latte - Büyük']]] ]));
ac_ml_check($variant_response->get_status() === 200, 'Real existing variable choice translation saved via parent REST editor');
$unchanged = wc_get_product($product_id)->get_name();
$bad = rest_do_request(ac_ml_request('PATCH', '/manage/products/' . $product_id, ['name' => 'Should not change', 'translations' => ['en' => ['name' => ['Invalid']]] ]));
ac_ml_check($bad->get_status() === 400 && wc_get_product($product_id)->get_name() === $unchanged, 'Malformed product map cannot partially mutate real Woo product');
$bad_variant = rest_do_request(ac_ml_request('PATCH', '/manage/products/' . $variable_id, ['name' => 'Should not change', 'variation_translations' => [$product_id => ['en' => ['name' => 'Bad ownership']]] ]));
ac_ml_check($bad_variant->get_status() === 400 && wc_get_product($variable_id)->get_name() === 'لاته ویژه', 'Cross-product variation translation ownership enforced');
$bad_category = rest_do_request(ac_ml_request('PATCH', '/manage/categories/' . $category_id, ['name' => 'Should not change', 'translations' => ['en' => ['description' => 'Invalid category field']]]));
ac_ml_check($bad_category->get_status() === 400 && get_term($category_id, 'product_cat')->name === 'قهوه گرم', 'Malformed category map cannot mutate canonical category');
$table = Tables::save(['label' => 'میز ترجمه آزمایشی', 'enabled' => true, 'mode' => 'order']);
delete_transient('ac_rate_' . hash('sha256', 'table_order:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')));
$expected_names = ['fa' => $canonical_name, 'en' => 'Vanilla latte', 'zh' => '香草拿铁', 'tr' => 'Vanilyalı latte'];
$expected_restaurant = ['fa' => Settings::get('restaurant_name'), 'en' => 'Aram Cafe', 'zh' => '安然咖啡馆', 'tr' => 'Aram Kafe'];
$bootstrap_ids = null;
wp_set_current_user(0);
foreach (['fa', 'en', 'zh', 'tr'] as $language) {
    $response = rest_do_request(ac_ml_request('GET', '/bootstrap', [], ['lang' => $language, 'table' => $table['token']]));
    ac_ml_check($response->get_status() === 200, 'Public REST bootstrap accepts ' . $language);
    $data = $response->get_data(); $row = ac_ml_find($data['products'], $product_id); $variant_parent = ac_ml_find($data['products'], $variable_id);
    ac_ml_check($data['language'] === $language && $data['direction'] === ($language === 'fa' ? 'rtl' : 'ltr'), 'Correct public language and direction ' . $language);
    ac_ml_check(get_locale() === Language::locale($language), 'WordPress locale hooks follow explicit public language ' . $language);
    $unavailable = __('An item is unavailable.', 'fandoogh-rest');
    ac_ml_check($language === 'en' ? $unavailable === 'An item is unavailable.' : $unavailable !== 'An item is unavailable.', 'Customer backend gettext follows selected language ' . $language);
    ac_ml_check($row['name'] === $expected_names[$language] && $row['price'] === $canonical_price, 'Localized content with same canonical ID/price ' . $language);
    ac_ml_check($data['settings']['restaurant_name'] === $expected_restaurant[$language], 'Restaurant content translated ' . $language);
    ac_ml_check(count($variant_parent['variations']) === 1 && $variant_parent['variations'][0]['id'] === $variation_id, 'Same existing Woo variant identity ' . $language);
    $ids = array_column($data['products'], 'id');
    ac_ml_check($bootstrap_ids === null || $bootstrap_ids === $ids, 'Catalog IDs invariant across locale ' . $language); $bootstrap_ids = $ids;
    ac_ml_check(isset($data['strings']['سبد شما']) && ($language === 'fa' || $data['strings']['سبد شما'] !== 'سبد شما'), 'Customer UI cart label translated ' . $language);
    ac_ml_check(!isset($data['settings']['content_translations']) && !isset($data['settings']['panel_slug']), 'Public settings redact management-only values ' . $language);
    ac_ml_check(str_contains($response->get_headers()['Cache-Control'] ?? '', 'no-store'), 'Locale/session bootstrap is not cached ' . $language);
    $request_id = bin2hex(random_bytes(16));
    $order_request = ac_ml_request('POST', '/orders/table', ['language' => $language, 'table_token' => $table['token'], 'items' => [['product_id' => $product_id, 'quantity' => 1]], 'request_id' => $request_id]);
    $order_request->set_header('Origin', home_url()); $order_request->set_header('X-AdminCafe-Token', Security::token());
    $order_response = rest_do_request($order_request);
    ac_ml_check($order_response->get_status() === 200, 'Public table order created ' . $language);
    $order = wc_get_order($order_response->get_data()['id']); $line = current($order->get_items());
    ac_ml_check($order->get_meta('_admincafe_language') === $language && $line->get_meta('_admincafe_localized_name') === $expected_names[$language], 'HPOS translated order snapshot ' . $language);
    ac_ml_check($line->get_product_id() === $product_id && $line->get_meta('_admincafe_canonical_name') === $canonical_name, 'HPOS canonical product label and ID preserved ' . $language);
    ac_ml_check(Orders::detail($order)['items'][0]['name'] === $canonical_name, 'Staff order detail remains Persian ' . $language);
    $retry = rest_do_request($order_request);
    ac_ml_check($retry->get_status() === 200 && $retry->get_data()['id'] === $order->get_id(), 'Same language retry remains idempotent ' . $language);
}
ac_ml_check(rest_do_request(ac_ml_request('GET', '/bootstrap', [], ['lang' => ['en']]))->get_status() === 400, 'Array locale input rejected');
ac_ml_check(rest_do_request(ac_ml_request('GET', '/bootstrap', [], ['lang' => 'xx']))->get_status() === 400, 'Unsupported locale input rejected');
ac_ml_check(rest_do_request(ac_ml_request('GET', '/manage/bootstrap', [], ['lang' => 'en']))->get_status() >= 400, 'Guest cannot cross from translated public view to manager bootstrap');
wp_set_current_user(1);
$manage = rest_do_request(ac_ml_request('GET', '/manage/products/' . $product_id, [], ['lang' => 'en']));
ac_ml_check($manage->get_status() === 200 && $manage->get_data()['name'] === $canonical_name, 'Manager product view stays canonical Persian despite customer language');
ac_ml_check(isset($manage->get_data()['translations']->en), 'Manager can edit complete persisted translation maps');
$management_bootstrap = rest_do_request(ac_ml_request('GET', '/manage/bootstrap', [], ['lang' => 'tr']));
ac_ml_check($management_bootstrap->get_status() === 200 && $management_bootstrap->get_data()['settings']['restaurant_name'] === Settings::get('restaurant_name'), 'Manager restaurant settings stay Persian');
$panel_locale = get_locale(); $panel_role = __('Restaurant manager', 'fandoogh-rest');
ac_ml_check($panel_locale === 'fa_IR' && $panel_role === 'مدیر رستوران', 'Panel WordPress locale/gettext stay Persian after customer locale switch (' . $panel_locale . ', ' . $panel_role . ')');
Settings::update(['enabled_languages' => ['fa', 'en'], 'default_language' => 'fa']);
wp_set_current_user(0);
ac_ml_check(rest_do_request(ac_ml_request('GET', '/bootstrap', [], ['lang' => 'zh']))->get_status() === 400, 'Disabled supported customer language rejected');
wp_set_current_user(1);
Settings::update(['enabled_languages' => ['fa', 'en', 'zh', 'tr'], 'default_language' => 'fa']);
ac_ml_check(\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled(), 'Actual HPOS storage used');
echo "Multilingual WordPress/Woo integration: {$passed} assertions passed.\n";
