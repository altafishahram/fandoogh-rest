<?php
/** Run only against disposable WordPress: php tests/wp-branches-commerce.php /path/wp-load.php */
$loader = $argv[1] ?? '';
if (!$loader || !is_file($loader)) { fwrite(STDERR, "Pass disposable wp-load.php.\n"); exit(1); }
$_SERVER['HTTP_HOST'] = '127.0.0.1:8093';
$_SERVER['REQUEST_URI'] = '/checkout/';
define('WP_HTTP_BLOCK_EXTERNAL', true);
require $loader;
if (get_option('admincafe_test_environment') !== 'local-disposable') { throw new RuntimeException('Disposable database marker required.'); }

use FandooghRest\Branches\Branches;
use FandooghRest\Commerce\Checkout;
use FandooghRest\Commerce\Orders;
use FandooghRest\Core\Settings;
use FandooghRest\Menu\Catalog;
use FandooghRest\Notifications\Notifications;
use FandooghRest\Reports\Reports;
use FandooghRest\Rest\Ordering;
use FandooghRest\Tables\Tables;

add_filter('pre_wp_mail', '__return_true');
$checks = 0;
$check = static function ($condition, string $label) use (&$checks): void {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
};
$saved = [];
foreach (['fandoogh_branches', 'admincafe_tables', 'admincafe_settings'] as $name) { $saved[$name] = get_option($name); }
$priorUser = get_current_user_id();
$priorBranch = Branches::current();
$productIds = $orderIds = $userIds = [];
$session = [];
$checkout = new Checkout();
$ordering = new Ordering();
$notifications = new Notifications();
global $wpdb;
try {
    wp_set_current_user(1);
    if (!WC()->session || !WC()->cart) { wc_load_cart(); }
    foreach (['admincafe_channel', 'admincafe_branch_id', 'admincafe_language', 'store_api_draft_order', 'order_awaiting_payment'] as $name) { $session[$name] = WC()->session->get($name); }
    WC()->session->set('store_api_draft_order', null); WC()->session->set('order_awaiting_payment', null);
    WC()->cart->empty_cart();
    Branches::setCurrent(1);
    $second = Branches::save(['name' => 'Commerce second branch', 'slug' => 'commerce-' . time()]);
    $check(!is_wp_error($second), 'Second branch is created');
    $bid = $second['id'];
    $products = $tables = [];
    foreach ([1, $bid] as $branchId) {
        Branches::setCurrent($branchId);
        Settings::update(['pickup_enabled' => true, 'delivery_enabled' => true, 'dine_in_enabled' => true, 'ordering_paused' => false, 'table_default_mode' => 'order', 'menu_category_ids' => [], 'restaurant_address' => 'Branch address ' . $branchId]);
        $data = Catalog::saveProduct(['name' => 'Commerce branch ' . $branchId, 'price' => '25', 'status' => 'publish', 'available' => true, 'visible' => true]);
        $check(!is_wp_error($data), 'Independent branch product saves');
        $product = wc_get_product($data['id']);
        $product->set_manage_stock(true); $product->set_stock_quantity(20); $product->save();
        $products[$branchId] = $product;
        $productIds[] = $product->get_id();
        $tables[$branchId] = Tables::save(['label' => 'Test table ' . $branchId]);
        $check(!is_wp_error($tables[$branchId]), 'Independent branch QR saves');
    }
    Branches::setCurrent(1);
    $check(count(array_filter(Tables::all(), static fn($t) => $t['id'] === $tables[$bid]['id'])) === 0, 'Table list hides other branch');
    $check(is_wp_error(Tables::delete($tables[$bid]['id'])), 'Direct foreign table deletion denied');
    Tables::save(['label' => 'Updated test table'], $tables[1]['id']);
    $check(Tables::findByToken($tables[$bid]['token']) !== null, 'Saving one branch retains the other QR');
    $check(Tables::context($tables[$bid]['token'])['branch_id'] === $bid, 'QR resolves actual branch globally');
    Settings::update(['dine_in_enabled' => false, 'table_default_mode' => 'menu']);
    $check(Tables::context($tables[$bid]['token'])['can_order'] && !Tables::context($tables[1]['token'])['can_order'], 'QR uses independent owning-branch ordering settings');
    $bootstrap = new WP_REST_Request('GET', '/admincafe/v1/bootstrap');
    $bootstrap->set_param('table', $tables[$bid]['token']);
    $boot = $ordering->bootstrap($bootstrap);
    $check(!is_wp_error($boot) && $boot->get_data()['branch_id'] === $bid, 'QR bootstrap selects table branch');
    $check(in_array($products[$bid]->get_id(), array_column($boot->get_data()['products'], 'id'), true) && !in_array($products[1]->get_id(), array_column($boot->get_data()['products'], 'id'), true), 'QR menu contains only branch products');
    $bootstrap->set_param('branch_id', 1);
    $check(is_wp_error($ordering->bootstrap($bootstrap)), 'QR and supplied branch conflict rejected');
    $check(is_wp_error($ordering->checkout(new WP_REST_Request('POST', '/admincafe/v1/checkout'))), 'Public checkout requires branch identity');

    Checkout::choose('pickup', 'fa', 1);
    $key = WC()->cart->add_to_cart($products[1]->get_id(), 1);
    $check((bool) $key, 'Classic cart accepts own branch');
    $check(!$checkout->addValidation(true, $products[$bid]->get_id(), 1), 'Classic mixed add blocked');
    $check(is_wp_error(Checkout::choose('pickup', 'fa', $bid)), 'Cross-branch choice requires explicit consent');
    $check(WC()->cart->get_cart_contents_count() === 1, 'Branch conflict preserves cart');
    $check(!is_wp_error(Checkout::choose('pickup', 'fa', $bid, true)) && WC()->cart->is_empty(), 'Consented replacement clears previous cart');
    $key = WC()->cart->add_to_cart($products[$bid]->get_id(), 1);
    $check((bool) $key && Checkout::cartBranch() === $bid, 'Second branch native cart carries identity');
    $_REQUEST['branch_id'] = 1;
    $errors = new WP_Error(); $checkout->classicValidation([], $errors);
    $check($errors->has_errors(), 'Stale classic checkout tab is rejected');
    unset($_REQUEST['branch_id']);
    $order = new WC_Order(); $order->add_product($products[$bid], 1); $checkout->decorate($order); $order->save(); $orderIds[] = $order->get_id();
    $check(Branches::orderBranch($order) === $bid && $order->get_meta('_fandoogh_branch_address') === 'Branch address ' . $bid, 'Native checkout records immutable branch address');
    Branches::runFor($bid, static fn() => Settings::update(['restaurant_address' => 'Changed branch address']));
    $checkout->decorate($order);
    $check($order->get_meta('_fandoogh_branch_address') === 'Branch address ' . $bid, 'Branch snapshot survives repeated decoration');
    $products[$bid]->update_meta_data('_fandoogh_branch_id', 1); $products[$bid]->save();
    $check(is_wp_error(Checkout::cartError()), 'Product reassignment invalidates old cart');
    $check(!$checkout->quantityValidation(true, $key, WC()->cart->get_cart_item($key), 2), 'Quantity update rejects reassigned cart');
    $products[$bid]->update_meta_data('_fandoogh_branch_id', $bid); $products[$bid]->save();

    // Dispatch actual Woo Store API routes with its native nonce and controller.
    $dispatch = static function (string $path, array $params): WP_REST_Response {
        $request = new WP_REST_Request('POST', '/wc/store/v1/' . $path);
        $request->set_header('Nonce', wp_create_nonce('wc_store_api'));
        foreach ($params as $name => $value) { $request->set_param($name, $value); }
        return rest_do_request($request);
    };
    $result = $dispatch('cart/add-item', ['id' => $products[1]->get_id(), 'quantity' => 1, 'branch_id' => $bid]);
    $check($result->get_status() >= 400, 'Actual Store API mixed add is rejected');
    $result = $dispatch('cart/update-item', ['key' => $key, 'quantity' => 2, 'branch_id' => 1]);
    $check($result->get_status() >= 400 && WC()->cart->get_cart_item($key)['quantity'] === 1, 'Actual Store API stale-tab update leaves quantity unchanged');
    $result = $dispatch('checkout', ['branch_id' => 1]);
    $check($result->get_status() >= 400, 'Actual Store API checkout rejects foreign branch');
    WC()->cart->empty_cart(); Checkout::choose('pickup', 'fa', $bid);
    $result = $dispatch('cart/add-item', ['id' => $products[$bid]->get_id(), 'quantity' => 1, 'branch_id' => $bid]);
    $check($result->get_status() < 400, 'Actual Store API own-branch add succeeds');
    $storeItem = array_values(WC()->cart->get_cart())[0];
    $check($storeItem['_fandoogh_branch_id'] === $bid && Checkout::cartBranch() === $bid, 'Store API cart persists branch identity');
    $products[$bid]->update_meta_data('_fandoogh_branch_id', 1); $products[$bid]->save();
    $result = $dispatch('cart/update-item', ['key' => $storeItem['key'], 'quantity' => 3, 'branch_id' => $bid]);
    $check($result->get_status() >= 400 && WC()->cart->get_cart_item($storeItem['key'])['quantity'] === 1, 'Actual Store API update rejects product reassignment');
    $result = $dispatch('checkout', ['branch_id' => $bid]);
    $check($result->get_status() >= 400, 'Actual Store API checkout rejects product reassignment');
    $products[$bid]->update_meta_data('_fandoogh_branch_id', $bid); $products[$bid]->save();

    $oldDraft = new WC_Order(); $oldDraft->set_status('checkout-draft'); $oldDraft->add_product($products[1], 1);
    Branches::runFor(1, static fn() => Orders::snapshotBranch($oldDraft)); $oldDraft->save(); $orderIds[] = $oldDraft->get_id();
    WC()->session->set('store_api_draft_order', $oldDraft->get_id());
    $result = $dispatch('checkout', ['branch_id' => $bid]);
    $oldDraft = wc_get_order($oldDraft->get_id());
    $check($result->get_status() >= 400 && Branches::orderBranch($oldDraft) === 1 && array_values($oldDraft->get_items())[0]->get_product_id() === $products[1]->get_id(), 'Foreign existing draft is rejected before Woo overwrites line items');
    Checkout::choose('pickup', 'fa', $bid);
    $check(!WC()->session->get('store_api_draft_order') && wc_get_order($oldDraft->get_id()), 'New branch checkout detaches old draft without deleting its order');

    Branches::setCurrent(1);
    $input = ['table_token' => $tables[$bid]['token'], 'items' => [['product_id' => $products[$bid]->get_id(), 'quantity' => 2]], 'request_id' => 'branch-commerce-' . bin2hex(random_bytes(8)), 'language' => 'fa'];
    $bad = $input; $bad['branch_id'] = 1;
    $check(is_wp_error(Orders::create($bad)), 'Dine-in QR branch conflict rejected');
    $bad = $input; $bad['items'][] = ['product_id' => $products[1]->get_id(), 'quantity' => 1];
    $check(is_wp_error(Orders::create($bad)), 'Dine-in mixed products rejected');
    $created = Orders::create($input);
    $check(!is_wp_error($created) && $created['branch_id'] === $bid, 'Dine-in QR creates order in actual branch');
    $orderIds[] = $created['id']; $tableOrder = wc_get_order($created['id']);
    $check(wc_get_product($products[$bid]->get_id())->get_stock_quantity() === 18 && wc_get_product($products[1]->get_id())->get_stock_quantity() === 20, 'Native stock changes only ordered branch product');
    $request = new WP_REST_Request('GET', '/admincafe/v1/manage/orders/' . $created['id']); $request->set_param('id', $created['id']);
    $check(is_wp_error($ordering->detail($request)), 'Direct foreign management order ID denied even for central active branch');
    $check(is_wp_error(Orders::update($tableOrder, ['stage' => 'cancelled'])), 'Direct foreign order update denied');
    Branches::setCurrent($bid);
    $changed = Orders::update($tableOrder, ['stage' => 'cancelled']);
    $check(!is_wp_error($changed) && wc_get_product($products[$bid]->get_id())->get_stock_quantity() === 20, 'Native unpaid cancellation restores correct branch stock');
    $check(Reports::summary(1)['order_count'] >= 2, 'Branch report includes own fixture orders');
    $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}admincafe_events WHERE order_id=%d", $created['id']), ARRAY_A);
    $check($event && (int) $event['branch_id'] === $bid, 'Durable notification stores order branch');
    $uid = wp_insert_user(['user_login' => 'branch-commerce-' . time(), 'user_pass' => wp_generate_password(), 'role' => 'admincafe_cashier']);
    $check(!is_wp_error($uid), 'Scoped cashier fixture creates'); $userIds[] = $uid;
    update_user_meta($uid, Branches::USER_META, [1]); wp_set_current_user($uid); Branches::setCurrent(1);
    $events = $notifications->listing(new WP_REST_Request('GET', '/admincafe/v1/manage/notifications'));
    $check(!in_array((int) $event['id'], array_column($events['events'], 'id'), true), 'Foreign events absent from cashier list');
    $read = new WP_REST_Request('POST', '/admincafe/v1/manage/notifications/read'); $read->set_param('ids', [(int) $event['id']]); $notifications->markRead($read);
    $check(!(int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}admincafe_event_reads WHERE event_id=%d AND user_id=%d", $event['id'], $uid)), 'Foreign event cannot be marked read');
    $deviceKey = hash('sha256', 'branch-worker-test');
    $encode = static fn($value) => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    $device = ['subscription' => ['endpoint' => 'https://fcm.googleapis.com/fcm/send/branch-test', 'keys' => ['p256dh' => $encode(chr(4) . str_repeat('x', 64)), 'auth' => $encode(str_repeat('x', 16))]], 'channels' => ['table'], 'last_error' => 'untouched'];
    update_user_meta($uid, '_admincafe_push_devices', [$deviceKey => $device]);
    $notifications->deliver((int) $event['id'], $uid, $deviceKey);
    $check(get_user_meta($uid, '_admincafe_push_devices', true)[$deviceKey]['last_error'] === 'untouched', 'Queued push worker rejects event after branch authorization check');
    Branches::setCurrent($bid);
    $check(is_wp_error(Orders::create(['branch_id' => $bid, 'items' => $input['items']], true)), 'Cashier cannot manually create order in unassigned branch');
    wp_set_current_user(1);
    Branches::runFor($bid, static fn() => Settings::update(['pickup_enabled' => false]));
    $check(is_wp_error(Checkout::choose('pickup', 'fa', $bid)), 'Server rejects disabled branch channel');
    echo "Branch commerce integration: {$checks} checks passed.\n";
} finally {
    wp_set_current_user(1); unset($_REQUEST['branch_id']);
    if (WC()->cart) { WC()->cart->empty_cart(); }
    foreach ($orderIds as $id) {
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}admincafe_event_reads WHERE event_id IN (SELECT id FROM {$wpdb->prefix}admincafe_events WHERE order_id=%d)", $id));
        $wpdb->delete($wpdb->prefix . 'admincafe_events', ['order_id' => $id]);
        $order = wc_get_order($id);
        if ($order) {
            $scope = $order->get_meta('_admincafe_request_scope');
            if ($scope) { delete_option('admincafe_request_' . $scope); }
            $order->delete(true);
        }
    }
    foreach ($productIds as $id) { wp_delete_post($id, true); }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($userIds as $id) { wp_delete_user($id); }
    foreach ($saved as $name => $value) { $value === false ? delete_option($name) : update_option($name, $value, false); }
    foreach ($session as $name => $value) { WC()->session->set($name, $value); }
    Branches::setCurrent($priorBranch); wp_set_current_user($priorUser);
}
