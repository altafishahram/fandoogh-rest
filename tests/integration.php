<?php
/** Run only against a disposable WordPress database: php tests/integration.php /absolute/wp-load.php */
if (!defined('ABSPATH')) {
    $loader = $argv[1] ?? '';
    if (!$loader || !is_file($loader)) {
        fwrite(STDERR, "Pass the disposable WordPress wp-load.php path.\n");
        exit(1);
    }
    require $loader;
}
if (get_option('admincafe_test_environment') !== 'local-disposable') {
    fwrite(STDERR, "Refusing to change a database without admincafe_test_environment=local-disposable.\n");
    exit(1);
}

use FandooghRest\Core\Settings;
use FandooghRest\Core\Security;
use FandooghRest\Menu\Catalog;
use FandooghRest\Tables\Tables;
use FandooghRest\Commerce\Orders;
use FandooghRest\Commerce\Checkout;
use FandooghRest\Import\Reader;
use FandooghRest\Import\Importer;
use FandooghRest\Notifications\Notifications;

$passed = 0;
function ac_check(bool $condition, string $label): void {
    global $passed;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $passed++;
    echo "PASS: $label\n";
}
function ac_request(string $method, string $path, array $body = []): WP_REST_Request {
    $r = new WP_REST_Request($method, '/admincafe/v1' . $path);
    $r->set_header('Content-Type', 'application/json');
    $r->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    $r->set_body(wp_json_encode($body));
    return $r;
}
wp_set_current_user(1);
ac_check(current_user_can('admincafe_manage_settings'), 'Activation installs admin capabilities');
ac_check(class_exists(Minishlink\WebPush\WebPush::class), 'Bundled Web Push dependency loads');
ac_check(\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled(), 'Test environment uses real HPOS');
Settings::update(['restaurant_name' => 'کافه آرام', 'tagline' => 'یک مکث کوچک، یک حال خوب', 'dine_in_enabled' => false, 'pickup_enabled' => true, 'delivery_enabled' => true, 'ordering_paused' => false]);
ac_check(is_wp_error(Settings::update(['accent' => 'javascript:alert(1)'])), 'Reject malicious appearance value');
ac_check(is_wp_error(Settings::update(['restaurant_name' => ['bad']])), 'Reject malformed setting object');
ac_check(is_wp_error(Settings::update(['panel_slug' => 'menu'])), 'Reject conflicting routes');

$cat = term_exists('قهوه گرم', 'product_cat') ?: wp_insert_term('قهوه گرم', 'product_cat');
$category = (int) (is_array($cat) ? $cat['term_id'] : $cat);
$foods = [
    ['لاته وانیل', 'اسپرسو، شیر مخملی و وانیل طبیعی', '145000'],
    ['اسپرسو', 'عصاره‌ای غلیظ و خوش‌عطر از دانه‌های تازه برشته', '85000'],
    ['کاپوچینو', 'تعادل دلپذیر قهوه و فوم شیر', '125000'],
    ['آیس آمریکانو', 'اسپرسوی دبل روی یخ، ساده و باطراوت', '110000'],
    ['چیزکیک سن سباستین', 'بافت نرم و خامه‌ای با رویه کاراملی', '165000'],
    ['صبحانه کافه', 'تخم‌مرغ، نان تازه، پنیر و سبزیجات', '245000'],
];
$products = [];
foreach ($foods as $i => [$name, $description, $price]) {
    $sku = 'AC-LOCAL-' . $i;
    $product = Catalog::saveProduct(['name' => $name, 'description' => $description, 'price' => $price, 'sku' => $sku, 'category_ids' => [$category], 'available' => true, 'visible' => true], wc_get_product_id_by_sku($sku));
    ac_check(!is_wp_error($product), 'Actual Woo product save ' . $i);
    $products[] = $product;
}
ac_check(count(Catalog::menu()['products']) >= 6, 'Real catalog returns published products');
ac_check(is_wp_error(Settings::update(['currency_code' => 'INVALID'])), 'Reject unsupported real Woo currency');
$currencySettings = Settings::update(['currency_code' => 'IRT']);
ac_check(!is_wp_error($currencySettings) && get_woocommerce_currency() === 'IRT' && Settings::get('currency_code') === 'IRT', 'Panel currency updates the Woo source of truth');
ac_check(wc_get_product($products[0]['id'])->get_price() === '145000', 'Changing monetary currency does not convert prices');
$table = Tables::save(['label' => 'میز ۰۳', 'mode' => 'order', 'enabled' => true]);
ac_check(!is_wp_error($table), 'Create stable table QR identity');
ac_check(!Tables::context($table['token'])['can_order'], 'Global disabled table mode overrides table');
Settings::update(['dine_in_enabled' => true]);
ac_check(Tables::context($table['token'])['can_order'], 'Global plus table mode enables orders');
$menuOnly = Tables::save(['label' => 'میز فقط منو', 'mode' => 'menu', 'enabled' => true]);
ac_check(!Tables::context($menuOnly['token'])['can_order'], 'Menu-only QR never accepts table orders');

$csrf = Security::token();
$r = ac_request('POST', '/orders/table');
ac_check(is_wp_error(Security::publicPermission($r)), 'Public order requires session CSRF');
$r->set_header('X-AdminCafe-Token', $csrf);
ac_check(Security::publicPermission($r) === true, 'Session CSRF accepted');
$r->set_header('Origin', 'https://attacker.example');
ac_check(is_wp_error(Security::publicPermission($r)), 'Cross-origin order denied');
$payload = ['table_token' => $table['token'], 'items' => [['product_id' => $products[0]['id'], 'quantity' => 2]], 'request_id' => bin2hex(random_bytes(16)), 'name' => 'مهمان آزمایشی', 'note' => 'بدون شکر'];
$order = Orders::create($payload);
ac_check(!is_wp_error($order), 'Create actual table order');
$again = Orders::create($payload);
ac_check(!is_wp_error($again) && $again['id'] === $order['id'] && $again['tracking_token'] === $order['tracking_token'], 'Real database idempotent retry');
$woo = wc_get_order($order['id']);
ac_check($woo->get_meta('_admincafe_stage') === 'awaiting_approval' && !$woo->is_paid(), 'Kitchen stage independent of settlement');
ac_check((float) $woo->get_total() === 290000.0, 'Server computes price rather than trusting client');
ac_check(is_wp_error(Orders::update($woo, ['stage' => 'preparing'])), 'Cannot prepare before staff acceptance');
ac_check(!is_wp_error(Orders::update($woo, ['stage' => 'accepted'])), 'Staff accepts order');
ac_check(!is_wp_error(Orders::update($woo, ['stage' => 'preparing'])), 'Accepted order reaches kitchen');
ac_check(!isset(Orders::detail($woo)['tracking_token']), 'Manager response excludes tracking credential');
$woo->set_address(['first_name' => 'گیرنده', 'last_name' => 'آزمایشی', 'address_1' => 'خیابان آزمایشی', 'city' => 'تهران', 'country' => 'IR'], 'shipping');
$woo->save();
ac_check(str_contains(Orders::detail(wc_get_order($woo->get_id()))['address'], 'خیابان آزمایشی'), 'Actual Woo shipping address reaches panel without HTML');
$historicalCurrency = $woo->get_currency();
$woo->set_currency('USD');
ac_check(Orders::publicOrder($woo)['currency_symbol'] === '$', 'Order currency entity decoded for browser text');
$woo->set_currency($historicalCurrency);
$invalid = $payload;
$invalid['table_token'] = $menuOnly['token'];
$invalid['request_id'] = bin2hex(random_bytes(16));
ac_check(is_wp_error(Orders::create($invalid)), 'Menu-only QR enforced during real order creation');

$notify = new Notifications();
global $wpdb;
$countBefore = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}admincafe_events");
$notify->newOrder($order['id']);
$notify->newOrder($order['id']);
$countAfter = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}admincafe_events");
ac_check($countBefore === $countAfter, 'Repeated Woo callback does not duplicate new-order notification');
$events = $notify->listing(ac_request('GET', '/manage/notifications'));
ac_check(count($events['events']) > 0 && $events['unread'] > 0, 'Durable order notification visible');
$mark = ac_request('POST', '/manage/notifications/read', ['ids' => [$events['events'][0]['id']]]);
ac_check(!is_wp_error($notify->markRead($mark)), 'Notification read acknowledgement saved');
ac_check(is_wp_error(Notifications::validateSubscription(['endpoint' => 'https://127.0.0.1/secret', 'keys' => []])), 'Push endpoint SSRF blocked');

ac_check(Reader::price('۱۴۵٬۰۰۰ تومان') === '145000', 'Persian import price normalized');
ac_check(Reader::price('0') === '0', 'Zero is not blank during import');
ac_check(Reader::price('ناموجود') === null, 'Unavailable price recognized');
ac_check(is_wp_error(Reader::price('-25')), 'Negative import price rejected');
$runtime = dirname(__FILE__) . '/runtime';
wp_mkdir_p($runtime);
$csv = $runtime . '/menu.csv';
file_put_contents($csv, "\xEF\xBB\xBFنام,قیمت,کد\nقهوه,\"۱۴۵٬۰۰۰\",AC-TEST\nکیک,ناموجود,AC-CAKE\n");
$parsed = Reader::read($csv, 'csv');
ac_check(!is_wp_error($parsed) && count($parsed['rows']) === 2, 'Actual UTF-8 CSV read');
$xlsx = $runtime . '/menu.xlsx';
$zip = new ZipArchive();
$zip->open($xlsx, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Menu" sheetId="1" r:id="rId1"/></sheets></workbook>');
$zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/sheet1.xml"/></Relationships>');
$zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>نام</t></is></c><c r="B1" t="inlineStr"><is><t>قیمت</t></is></c></row><row r="2"><c r="A2" t="inlineStr"><is><t>لاته</t></is></c><c r="B2"><v>145000</v></c></row><row r="3"><c r="A3" t="inlineStr"><is><t>فرمول</t></is></c><c r="B3"><f>1+1</f><v>2</v></c></row></sheetData></worksheet>');
$zip->close();
$sheet = Reader::read($xlsx, 'xlsx');
ac_check(!is_wp_error($sheet) && $sheet['rows'][0][1] === '145000', 'Actual XLSX read');
ac_check($sheet['rows'][1][1] === '', 'XLSX formulas and cached values ignored');

$token = bin2hex(random_bytes(24));
$key = 'ac_import_' . hash('sha256', $token);
set_transient($key, ['user' => 1, 'cursor' => 0, 'columns' => ['name','price','sku'], 'rows' => [['لاته وانیل','ناموجود','AC-LOCAL-0']], 'result' => ['created' => 0,'updated' => 0,'skipped' => 0,'errors' => []]], 1800);
$import = (new Importer())->apply(ac_request('POST', '/manage/import/apply', ['token' => $token,'mapping' => ['name' => 'name','price' => 'price','sku' => 'sku'],'mode' => 'upsert']));
ac_check(!is_wp_error($import) && $import['done'] && $import['updated'] === 1, 'Actual database import completes');
$updated = wc_get_product($products[0]['id']);
ac_check($updated->get_price() === '145000' && !$updated->is_in_stock(), 'Unavailable import preserves previous price');
Catalog::saveProduct(['available' => true], $products[0]['id']);

WC()->cart->empty_cart();
WC()->cart->add_to_cart($products[0]['id']);
WC()->session->set('admincafe_channel', '');
$errors = new WP_Error();
(new Checkout())->classicValidation([], $errors);
ac_check($errors->has_errors(), 'Direct Woo checkout cannot bypass channel selection');
ac_check(!is_wp_error(Checkout::choose('pickup')) && !(new Checkout())->needsShipping(true), 'Pickup suppresses shipping only in this session');
ac_check(!is_wp_error(Checkout::choose('delivery')) && (new Checkout())->needsShipping(true), 'Delivery restores shipping');
Settings::update(['ordering_paused' => true]);
ac_check(is_wp_error(Checkout::choose('pickup')), 'Pause blocks checkout channel server-side');
Settings::update(['ordering_paused' => false]);
WC()->cart->empty_cart();
WC()->session->set('admincafe_channel', '');

$manager = rest_do_request(ac_request('GET', '/manage/bootstrap'));
ac_check($manager->get_status() === 200 && isset($manager->get_data()['user']['roles']), 'Actual management REST bootstrap');
ac_check(in_array('IRT', array_column($manager->get_data()['currencies'], 'code'), true), 'Real REST currencies include Toman');
$builderPage = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Fandoogh Rest builder smoke', 'post_content' => '[admincafe_menu mode="menu"]']);
Settings::update(['menu_page_id' => $builderPage]);
$pageRequest = apply_filters('request', ['admincafe_route' => 'menu']);
ac_check(($pageRequest['page_id'] ?? 0) === $builderPage && !isset($pageRequest['admincafe_route']), 'Custom menu destination resolves through the WordPress page query');
ac_check(str_contains(do_shortcode(get_post_field('post_content', $builderPage)), 'data-admincafe-app="menu"'), 'Actual builder shortcode mounts the menu app');
ac_check(str_contains(render_block(['blockName' => 'admincafe/menu', 'attrs' => ['component' => 'categories'], 'innerBlocks' => [], 'innerHTML' => '', 'innerContent' => []]), '&quot;component&quot;:&quot;categories&quot;'), 'Actual Gutenberg dynamic block renders shared categories');
wp_update_post(['ID' => $builderPage, 'post_status' => 'draft']);
ac_check((apply_filters('request', ['admincafe_route' => 'menu'])['admincafe_route'] ?? '') === 'menu', 'Unpublished builder destination falls back to the standalone menu');
Settings::update(['menu_page_id' => 0]);
wp_set_current_user(0);
$denied = rest_do_request(new WP_REST_Request('GET', '/admincafe/v1/manage/products'));
ac_check($denied->get_status() >= 400, 'Guest cannot read management data');
echo "Integration: $passed checks passed; WP " . get_bloginfo('version') . ', Woo ' . WC_VERSION . "; real HPOS via SQLite test database.\n";
