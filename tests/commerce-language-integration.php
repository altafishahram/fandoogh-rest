<?php

/** Run only against a disposable WordPress: php tests/commerce-language-integration.php /path/wp-load.php */
$loader = $argv[1] ?? '';
if (!$loader || !is_file($loader)) {
    fwrite(STDERR, "Pass the disposable WordPress wp-load.php path.\n");
    exit(1);
}
$_SERVER['HTTP_HOST'] = '127.0.0.1:8093';
$_SERVER['REQUEST_URI'] = '/checkout/';
require $loader;
if (get_option('admincafe_test_environment') !== 'local-disposable') {
    fwrite(STDERR, "Disposable database marker required.\n");
    exit(1);
}

use AdminCafe\Commerce\Checkout;
use AdminCafe\Commerce\Orders;
use AdminCafe\Core\Settings;
use AdminCafe\Localization\Language;
use AdminCafe\Menu\Catalog;
use AdminCafe\Tables\Tables;

add_filter('pre_wp_mail', '__return_true');
$checks = 0;
$check = static function (bool $condition, string $label) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $checks++;
};
$settingsBefore = Settings::all();
$sessionBefore = WC()->session ? WC()->session->get('admincafe_language') : null;
$products = [];
$orders = [];
$table = null;
$cartKey = null;
$simpleCartKey = null;
$checkout = new Checkout();
try {
    wp_set_current_user(1);
    Settings::update(['enabled_languages' => ['fa', 'en', 'zh', 'tr'], 'default_language' => 'fa', 'pickup_enabled' => true, 'delivery_enabled' => true, 'dine_in_enabled' => true, 'ordering_paused' => false, 'menu_category_ids' => []]);
    if (!WC()->session) {
        wc_load_cart();
    }
    $saved = Catalog::saveProduct(['name' => 'قهوه آزمون زبان', 'description' => 'شرح قهوه', 'short_description' => 'خلاصه قهوه', 'price' => '25', 'status' => 'publish', 'available' => true, 'visible' => true, 'translations' => ['en' => ['name' => 'Language coffee', 'description' => 'Fresh coffee', 'short_description' => 'Coffee summary'], 'zh' => ['name' => '语言咖啡', 'description' => '新鲜咖啡', 'short_description' => '咖啡简介'], 'tr' => ['name' => 'Dil kahvesi', 'description' => 'Taze kahve', 'short_description' => 'Kahve özeti']]]);
    $check(!is_wp_error($saved), 'Real translated Woo product saves');
    $product = wc_get_product($saved['id']);
    $products[] = $product;
    $simpleCartKey = WC()->cart->add_to_cart($product->get_id(), 1);
    $names = ['fa' => 'قهوه آزمون زبان', 'en' => 'Language coffee', 'zh' => '语言咖啡', 'tr' => 'Dil kahvesi'];
    foreach ($names as $language => $name) {
        $result = Checkout::choose('pickup', $language);
        $check(!is_wp_error($result) && str_contains($result['url'], 'lang=' . $language), 'Checkout carries language ' . $language);
        $check(Language::resolve() === $language, 'Woo session language persists ' . $language);
        $hydration = \Automattic\WooCommerce\Blocks\Package::container()->get(\Automattic\WooCommerce\Blocks\Domain\Services\Hydration::class);
        $preload = $hydration->get_rest_api_response_data('/wc/store/v1/cart');
        $preloadItem = array_values(array_filter($preload['body']['items'], static fn ($row) => $row['id'] === $product->get_id()))[0];
        $check($preloadItem['name'] === $name, 'Actual native Blocks preload translates name ' . $language);
        foreach (['description', 'short_description'] as $field) {
            $check(trim(wp_strip_all_tags($preloadItem[$field])) === Catalog::localizedText($product, $field, $language), 'Actual native Blocks preload translates ' . $field . ' ' . $language);
        }
        $order = new WC_Order();
        $itemId = $order->add_product($product, 1);
        $checkout->decorate($order);
        $order->save();
        $orders[] = $order;
        $item = $order->get_item($itemId);
        $check($order->get_meta('_admincafe_language') === $language, 'Order records language ' . $language);
        $check($item->get_meta('_admincafe_localized_name') === $name, 'Line item snapshot ' . $language);
        $check($item->get_name() === 'قهوه آزمون زبان', 'Canonical item name preserved ' . $language);
        $check($checkout->orderItemName($item->get_name(), $item) === $name, 'Customer receipt snapshot ' . $language);
        $check(Orders::detail($order)['items'][0]['name'] === 'قهوه آزمون زبان', 'Manager canonical label ' . $language);
        $response = new WP_REST_Response(['items' => [['id' => $product->get_id(), 'name' => 'قهوه آزمون زبان', 'prices' => ['price' => '2500']]]]);
        $checkout->storeNames($response, rest_get_server(), new WP_REST_Request('GET', '/wc/store/v1/cart'));
        $check($response->get_data()['items'][0]['name'] === $name && $response->get_data()['items'][0]['prices']['price'] === '2500', 'Blocks cart display without price mutation ' . $language);
        $response = new WP_REST_Response(['items' => [['id' => $itemId, 'name' => $item->get_name()]]]);
        $checkout->storeNames($response, rest_get_server(), new WP_REST_Request('GET', '/wc/store/v1/order/' . $order->get_id()));
        $check($response->get_data()['items'][0]['name'] === $name, 'Blocks order response snapshots ' . $language);
    }
    $check(is_wp_error(Checkout::choose('pickup', ['en'])), 'Reject object language at checkout');
    $check($product->get_name() === 'قهوه آزمون زبان' && $product->get_description() === 'شرح قهوه', 'Blocks preload never changes canonical product content');
    $check(is_wp_error(Orders::create(['language' => 'invalid'])), 'Reject invalid table request language');
    $parent = new WC_Product_Variable();
    $parent->set_name('قهوه اندازه‌دار');
    $parent->set_status('publish');
    $attribute = new WC_Product_Attribute();
    $attribute->set_name('size');
    $attribute->set_options(['بزرگ']);
    $attribute->set_variation(true);
    $parent->set_attributes([$attribute]);
    $parent->save();
    $products[] = $parent;
    $variant = new WC_Product_Variation();
    $variant->set_parent_id($parent->get_id());
    $variant->set_status('publish');
    $variant->set_regular_price('27');
    $variant->set_attributes(['size' => 'بزرگ']);
    $variant->update_meta_data('_admincafe_translations', ['en' => ['name' => 'Coffee - large']]);
    $variant->save();
    $products[] = $variant;
    WC_Product_Variable::sync($parent->get_id());
    Language::remember('en');
    $cartKey = WC()->cart->add_to_cart($parent->get_id(), 1, $variant->get_id(), ['attribute_size' => 'بزرگ']);
    $check((bool) $cartKey, 'Real translated variation enters Woo cart');
    $customData = static function ($rows): array {
        $rows[] = ['key' => 'Preparation note', 'value' => 'Less milk'];
        return $rows;
    };
    add_filter('woocommerce_get_item_data', $customData, 5);
    $_SERVER['REQUEST_URI'] = '/wp-json/wc/store/v1/cart';
    $request = new WP_REST_Request('GET', '/wc/store/v1/cart');
    $response = rest_do_request($request);
    $response = apply_filters('rest_post_dispatch', $response, rest_get_server(), $request);
    $row = array_values(array_filter($response->get_data()['items'], static fn ($row) => $row['id'] === $variant->get_id()))[0];
    $check($row['name'] === 'Coffee - large' && $row['variation'] === [], 'Real Blocks cart hides untranslated redundant variant options');
    $check(count($row['item_data']) === 1 && $row['item_data'][0]['key'] === 'Preparation note', 'Real Blocks cart keeps custom third-party metadata');
    $formatted = wc_get_formatted_cart_item_data(WC()->cart->get_cart_item($cartKey), true);
    $check(str_contains($formatted, 'Preparation note') && !str_contains($formatted, 'بزرگ'), 'Classic variation details preserve custom data without Persian redundant options');
    remove_filter('woocommerce_get_item_data', $customData, 5);
    $_SERVER['REQUEST_URI'] = '/checkout/';
    $order = new WC_Order();
    $itemId = $order->add_product($variant, 1);
    $item = $order->get_item($itemId, false);
    $item->add_meta_data('Preparation note', 'Less milk');
    $checkout->decorate($order);
    $order->save();
    $orders[] = $order;
    $item = $order->get_item($itemId);
    $formatted = $item->get_all_formatted_meta_data();
    $keys = array_map(static fn ($meta) => $meta->key, $formatted);
    $check(in_array('Preparation note', $keys, true) && !in_array('size', $keys, true), 'Real receipt attributes suppressed while custom order metadata survives');
    $table = Tables::save(['label' => 'Commerce language test', 'mode' => 'order', 'enabled' => true]);
    $input = ['language' => 'zh', 'table_token' => $table['token'], 'request_id' => 'lang-integration-' . bin2hex(random_bytes(8)), 'items' => [['product_id' => $product->get_id(), 'quantity' => 1]]];
    $result = Orders::create($input);
    $check(!is_wp_error($result), 'Table creates real Chinese-language order');
    $order = wc_get_order($result['id']);
    $orders[] = $order;
    $check($order->get_meta('_admincafe_language') === 'zh', 'Table language persists');
    $check(array_values($order->get_items())[0]->get_meta('_admincafe_localized_name') === '语言咖啡', 'Table item keeps Chinese snapshot');
    $product->update_meta_data('_admincafe_translations', ['zh' => ['name' => 'Changed after order']]);
    $product->save();
    $item = array_values($order->get_items())[0];
    $check($checkout->orderItemName($item->get_name(), $item) === '语言咖啡', 'Receipt survives later menu translation edits');
    $_SERVER['REQUEST_URI'] = '/cafe-panel/';
    $check(!Language::isCustomerRequest(), 'Panel is isolated from customer locale');
    $check($checkout->orderItemName($item->get_name(), $item) === 'قهوه آزمون زبان', 'Panel retains canonical order name');
    $_SERVER['REQUEST_URI'] = '/wp-json/admincafe/v1/manage/orders';
    $check(!Language::isCustomerRequest(), 'Management REST is isolated from customer locale');
    echo "Commerce language integration: {$checks} assertions passed.\n";
} finally {
    if ($cartKey && WC()->cart) {
        WC()->cart->remove_cart_item($cartKey);
    }
    if ($simpleCartKey && WC()->cart) {
        WC()->cart->remove_cart_item($simpleCartKey);
    }
    foreach ($orders as $order) {
        wc_release_stock_for_order($order);
        wc_increase_stock_levels($order->get_id());
        $order->delete(true);
    }
    foreach (array_reverse($products) as $product) {
        $product->delete(true);
    }
    if ($table && !is_wp_error($table)) {
        Tables::delete($table['id']);
    }
    Settings::update($settingsBefore);
    if (WC()->session) {
        WC()->session->set('admincafe_language', $sessionBefore);
        WC()->session->set('admincafe_channel', null);
    }
}
