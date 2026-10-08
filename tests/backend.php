<?php
/** Standalone behavior tests: php tests/backend.php (no WordPress database needed). */
namespace FandooghRest\Core {
    final class Settings {
        public static array $values = [];
        public static function get($key, $default = null) { return self::$values[$key] ?? $default; }
    }
}
namespace {
    function __($message, $domain = '') { return $message; }
    function do_action($hook, ...$args) {}
    class WP_Error {
        public function __construct(public string $code, public string $message, public array $data = []) {}
    }
    class WP_REST_Request implements ArrayAccess {
        public function __construct(public array $data = [], public string $nonce = 'valid') {}
        public function get_json_params() { return $this->data; }
        public function get_header($name) { return $this->nonce; }
        public function offsetExists($offset): bool { return isset($this->data[$offset]); }
        public function offsetGet($offset): mixed { return $this->data[$offset] ?? null; }
        public function offsetSet($offset, $value): void { $this->data[$offset] = $value; }
        public function offsetUnset($offset): void { unset($this->data[$offset]); }
    }
    class WC_Product_Simple {
        public array $fields = ['id' => 100, 'name' => '', 'description' => '', 'short_description' => '', 'sku' => '', 'price' => '', 'regular_price' => '', 'sale_price' => '', 'status' => 'draft', 'category_ids' => [], 'image_id' => 0, 'menu_order' => 0, 'stock_status' => 'instock'];
        public array $meta = [];
        public function get_type() { return 'simple'; }
        public function __call($name, $args) {
            $field = substr($name, 4);
            if (str_starts_with($name, 'set_')) { $this->fields[$field] = $args[0]; return; }
            return $this->fields[$field] ?? '';
        }
        public function is_type($type) { return $type === 'simple'; }
        public function is_on_sale($context = '') { return $this->fields['sale_price'] !== '' && (float) $this->fields['sale_price'] < (float) $this->fields['regular_price']; }
        public function is_purchasable() { return $this->fields['price'] !== ''; }
        public function is_in_stock() { return $this->fields['stock_status'] === 'instock'; }
        public function get_meta($key) { return $this->meta[$key] ?? ''; }
        public function update_meta_data($key, $value) { $this->meta[$key] = $value; }
        public function save() { $GLOBALS['products'][$this->fields['id']] = $this; }
    }
    class WC_Product_Variable extends WC_Product_Simple {
        public function get_type() { return 'variable'; }
        public function is_type($type) { return $type === 'variable'; }
        public function get_children() { return [201]; }
        public static function sync($id) { $GLOBALS['synced'][] = $id; }
    }
    class TestVariation extends WC_Product_Simple {
        public function get_type() { return 'variation'; }
        public function is_type($type) { return $type === 'variation'; }
        public function get_parent_id() { return 200; }
    }
    $options = []; $caps = []; $logged_in = true; $products = []; $termmeta = []; $orders = [];
    function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
    function update_option($key, $value, $autoload = null) { $GLOBALS['options'][$key] = $value; return true; }
    function home_url($path) { return 'https://cafe.test' . $path; }
    function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
    function sanitize_textarea_field($value) { return trim(strip_tags((string) $value)); }
    function wp_strip_all_tags($value) { return strip_tags((string) $value); }
    function wp_kses_post($value) { return strip_tags((string) $value, '<p><strong><em>'); }
    function wc_format_decimal($value) { return $value === '' ? '' : (string) $value; }
    function wp_get_attachment_image_url($id, $size) { return $id ? 'https://cafe.test/image.jpg' : false; }
    function rest_sanitize_boolean($value) { return !in_array($value, [false, 'false', '0', 0, ''], true); }
    function is_wp_error($value) { return $value instanceof WP_Error; }
    function absint($value) { return abs((int) $value); }
    if (!function_exists('mb_strlen')) { function mb_strlen($value) { return strlen($value); } }
    function wc_get_product($id) { return $GLOBALS['products'][$id] ?? false; }
    function current_user_can($cap) { return in_array($cap, $GLOBALS['caps'], true); }
    function is_user_logged_in() { return $GLOBALS['logged_in']; }
    function wp_verify_nonce($nonce, $action) { return $nonce === 'valid'; }
    function term_exists($id, $taxonomy) { return $id === 7; }
    function update_term_meta($id, $key, $value) { $GLOBALS['termmeta'][$id][$key] = $value; }
    function get_term_meta($id, $key, $single = true) { return $GLOBALS['termmeta'][$id][$key] ?? ''; }
    function get_term($id, $taxonomy) { return $id === 7 ? (object) ['term_id' => 7, 'name' => $GLOBALS['category_name'] ?? 'قهوه', 'parent' => 0] : null; }
    function get_terms($args) { return [get_term(7, 'product_cat')]; }
    function wp_update_term($id, $taxonomy, $input) { $GLOBALS['category_name'] = $input['name']; $GLOBALS['term_updates'] = ($GLOBALS['term_updates'] ?? 0) + 1; return ['term_id' => $id]; }
    function wc_get_products($args) { return array_values(array_filter($GLOBALS['products'], static fn($p) => $p instanceof WC_Product_Simple && in_array($p->get_status(), (array) $args['status'], true) && in_array($p->get_type(), (array) $args['type'], true))); }
    function wp_timezone() { return new DateTimeZone('Asia/Tehran'); }
    function get_woocommerce_currency_symbol() { return '&#36;'; }
    function get_woocommerce_currency() { return 'USD'; }
    function wc_get_price_decimals() { return 2; }
    function wc_get_orders($args) { return (object) ['orders' => $GLOBALS['orders'], 'max_num_pages' => 1]; }
    function get_current_user_id() { return 10; }
    function get_user_by($field, $id) { return $GLOBALS['users'][$id] ?? false; }
    function is_super_admin($id) { return $id === 1; }
    require __DIR__ . '/../src/Tables/Tables.php';
    require __DIR__ . '/../src/Menu/Catalog.php';
    require __DIR__ . '/../src/Rest/Management.php';
    require __DIR__ . '/../src/Reports/Reports.php';
    $checks = 0;
    function check($condition, $message) {
        $GLOBALS['checks']++;
        if (!$condition) { throw new RuntimeException($message); }
    }
    use FandooghRest\Tables\Tables;
    use FandooghRest\Core\Settings;
    use FandooghRest\Menu\Catalog;
    use FandooghRest\Rest\Management;
    use FandooghRest\Reports\Reports;
    check(is_wp_error(Tables::save(['label' => ''])), 'Empty table label rejected');
    check(is_wp_error(Tables::save(['label' => 'A', 'mode' => 'invalid'])), 'Invalid mode rejected');
    $table = Tables::save(['label' => 'A', 'mode' => 'order']);
    check(strlen($table['token']) === 48 && $table['id'] === 1, 'Opaque token generated');
    check(Tables::findByToken($table['token'])['label'] === 'A', 'Exact lookup');
    check(Tables::findByToken(strtoupper($table['token'])) === null, 'Malformed token rejected');
    check(Tables::context($table['token'])['can_order'] === false, 'Global dine-in disable is authoritative');
    Settings::$values['dine_in_enabled'] = true;
    check(Tables::context($table['token'])['can_order'] === true, 'Enabled table allows ordering');
    Settings::$values['ordering_paused'] = true;
    check(Tables::context($table['token'])['can_order'] === false, 'Paused ordering is authoritative');
    $updated = Tables::save(['label' => 'B', 'enabled' => false], 1);
    check($updated['token'] === $table['token'], 'QR token stable after edit');
    check(is_wp_error(Tables::context($table['token'])), 'Disabled table context rejected');
    check(is_wp_error(Tables::save(['label' => 'B'], 999)), 'Unknown table rejected');
    check(Tables::delete(1)['deleted'] && Tables::all() === [], 'Delete table persists');
    check(is_wp_error(Catalog::saveProduct(['name' => 'Coffee', 'price' => -1])), 'Negative price rejected');
    check(is_wp_error(Catalog::saveProduct(['name' => 'Coffee', 'price' => ['bad']])), 'Structured price rejected');
    check(is_wp_error(Catalog::saveProduct(['name' => 'Coffee', 'price' => 'free'])), 'Non-numeric price rejected');
    check(is_wp_error(Catalog::saveProduct(['name' => 'Coffee', 'price' => '1e999'])), 'Overflow price rejected');
    check(is_wp_error(Catalog::saveProduct(['name' => 'Coffee', 'type' => 'variable'])), 'Unsupported creation rejected');
    check(is_wp_error(Catalog::saveProduct(['name' => 'Coffee'], 999)), 'Unknown product rejected');
    check(is_wp_error(Catalog::saveProduct([])), 'Creation name required');
    $free = Catalog::saveProduct(['name' => 'Water', 'price' => 0, 'description' => '<p>Fresh</p>']);
    check($free['price'] === '0' && $free['available'] && $free['status'] === 'publish', 'Zero price is sellable and new product publishes');
    check($free['description'] === 'Fresh', 'Public descriptions are plain text');
    $unavailable = Catalog::saveProduct(['available' => false], $free['id']);
    check($unavailable['price'] === '0' && !$unavailable['available'], 'Availability edit preserves price');
    $sale = Catalog::saveProduct(['regular_price' => '12', 'sale_price' => '9'], $free['id']);
    check($sale['price'] === '9', 'Active sale controls resolved price');
    $lower = Catalog::saveProduct(['price' => '8'], $free['id']);
    check($lower['price'] === '8' && $lower['sale_price'] === '', 'Inline regular price below sale clears invalid stale sale');
    $parent = new WC_Product_Variable(); $parent->fields['id'] = 200; $parent->fields['name'] = 'Latte';
    $variant = new TestVariation(); $variant->fields['id'] = 201; $variant->fields['status'] = 'publish';
    $products[200] = $parent; $products[201] = $variant;
    check(is_wp_error(Catalog::saveProduct(['name' => 'Changed', 'variations' => [['id' => 201, 'price' => -1]]], 200)), 'Nested negative price rejected');
    check($parent->fields['name'] === 'Latte', 'Entire variation list validated before parent mutation');
    check(is_wp_error(Catalog::saveProduct(['variations' => [['id' => 100, 'price' => 5]]], 200)), 'Nested cross-parent choice rejected');
    $changed_parent = Catalog::saveProduct(['variations' => [['id' => 201, 'price' => '15', 'available' => false]]], 200);
    check($changed_parent['variations'][0]['price'] === '15' && !$changed_parent['variations'][0]['available'], 'Nested existing variation price and stock saved');
    check(in_array(200, $synced, true), 'Woo variable parent synchronized');
    Catalog::saveProduct(['name' => 'Imported'], 100, 'import-row-marker');
    check($products[100]->get_meta('_admincafe_import_row') === 'import-row-marker', 'Import row marker stamped before product save');
    $before_price = $products[100]->fields['price']; $before_stock = $products[100]->fields['stock_status'];
    $translated = Catalog::saveProduct(['translations' => ['en' => ['name' => 'Coffee', 'description' => '<strong>Fresh coffee</strong>'], 'zh' => ['name' => '咖啡'], 'tr' => ['name' => 'Kahve']]], 100);
    check(Catalog::localizedName($products[100], 'en') === 'Coffee' && Catalog::localizedName($products[100], 'zh') === '咖啡' && Catalog::localizedName($products[100], 'tr') === 'Kahve', 'Product content localizes English Chinese Turkish');
    check(Catalog::localizedName($products[100], 'fa') === 'Imported', 'Persian remains canonical Woo content');
    check(Catalog::localizedText($products[100], 'description', 'en') === 'Fresh coffee', 'Translated descriptions sanitized plain text');
    check(Catalog::localizedText($products[100], 'short_description', 'en') === $products[100]->get_short_description(), 'Missing translated field falls back individually');
    check($before_price === $products[100]->fields['price'] && $before_stock === $products[100]->fields['stock_status'], 'Content translation does not alter price or stock');
    check(isset($translated['translations']->en['name']), 'Admin product serializer provides translation editor map');
    Catalog::saveProduct(['name' => 'Canonical updated'], 100);
    check(Catalog::localizedName($products[100], 'en') === 'Coffee', 'Absent translation input preserves metadata');
    Catalog::saveProduct(['translations' => ['en' => ['name' => '']]], 100);
    check(Catalog::localizedName($products[100], 'en') === 'Canonical updated' && Catalog::localizedText($products[100], 'description', 'en') === 'Fresh coffee', 'Clearing one translated field preserves other fields and falls back');
    foreach ([['fa' => ['name' => 'Bad']], ['de' => ['name' => 'Bad']], ['en' => ['price' => '9']], ['en' => ['name' => ['Bad']]], ['en' => ['name' => null]], ['en' => ['name' => str_repeat('x', 251)]], ['zh' => ['description' => str_repeat('x', 20001)]]] as $invalid) {
        $original_name = $products[100]->get_name();
        check(is_wp_error(Catalog::saveProduct(['name' => 'Should not mutate', 'translations' => $invalid], 100)) && $products[100]->get_name() === $original_name, 'Invalid nested translation rejected before mutation');
    }
    $parent->fields['status'] = 'publish'; $variant->fields['name'] = 'لاته - بزرگ';
    $translated_parent = Catalog::saveProduct(['variation_translations' => ['201' => ['en' => ['name' => 'Latte - Large'], 'zh' => ['name' => '拿铁 - 大杯']]]], 200);
    check(Catalog::localizedName($variant, 'en') === 'Latte - Large' && Catalog::localizedName($variant, 'tr') === 'لاته - بزرگ', 'Existing variation translated choice name and canonical fallback');
    check(isset($translated_parent['variations'][0]['translations']->en), 'Parent editor exposes variation translation metadata');
    Catalog::saveProduct(['variations' => [['id' => 201, 'price' => '16']], 'variation_translations' => [201 => ['tr' => ['name' => 'Latte - Büyük']]]], 200);
    check($variant->get_price() === '16' && Catalog::localizedName($variant, 'tr') === 'Latte - Büyük' && Catalog::localizedName($variant, 'en') === 'Latte - Large', 'Nested variation price and translation patches merge without losing languages');
    check(is_wp_error(Catalog::saveProduct(['name' => 'Should not mutate', 'variation_translations' => ['100' => ['en' => ['name' => 'Invalid']]]], 200)) && $parent->get_name() === 'Latte', 'Cross-parent translation input rejected before mutation');
    $bad_variants = ['201' => ['en' => ['name' => 'Valid'], 'zh' => ['name' => ['Invalid']]]];
    check(is_wp_error(Catalog::saveProduct(['name' => 'Should not mutate', 'variation_translations' => $bad_variants], 200)) && Catalog::localizedName($variant, 'en') === 'Latte - Large' && $parent->get_name() === 'Latte', 'Entire variation translation list validated before mutation');
    $category_request = new WP_REST_Request(['translations' => ['en' => ['name' => 'Coffee category'], 'zh' => ['name' => '咖啡分类']]]);
    $category_management = new Management();
    $saved_category = $category_management->saveCategory($category_request, 7);
    check(isset($saved_category['translations']->en) && Catalog::category(get_term(7, 'product_cat'), 'en')['name'] === 'Coffee category', 'Category translations saved and localized');
    $category_management->saveCategory(new WP_REST_Request(['translations' => ['zh' => ['name' => '']]]), 7);
    check(Catalog::category(get_term(7, 'product_cat'), 'zh')['name'] === 'قهوه' && Catalog::category(get_term(7, 'product_cat'), 'en')['name'] === 'Coffee category', 'Clearing category field restores Persian and preserves other translations');
    $category_before = $term_updates;
    check(is_wp_error($category_management->saveCategory(new WP_REST_Request(['name' => 'Should not mutate', 'translations' => ['en' => ['description' => 'Invalid field']]]), 7)) && $term_updates === $category_before, 'Category unknown translation field rejected before term mutation');
    $english_menu = Catalog::menu('en'); $persian_menu = Catalog::menu('fa');
    check(array_column($english_menu['products'], 'id') === array_column($persian_menu['products'], 'id') && array_column($english_menu['products'], 'price') === array_column($persian_menu['products'], 'price'), 'Localized menu preserves canonical IDs and prices');
    check($english_menu['categories'][0]['name'] === 'Coffee category' && $persian_menu['categories'][0]['name'] === 'قهوه', 'Menu localizes category names with canonical Persian');
    $caps = ['admincafe_manage_menu'];
    check(Management::permission(new WP_REST_Request(), 'admincafe_manage_menu') === true, 'Authorized management allowed');
    check(is_wp_error(Management::permission(new WP_REST_Request([], 'bad'), 'admincafe_manage_menu')), 'Nonce is required');
    check(is_wp_error(Management::permission(new WP_REST_Request(), 'admincafe_manage_staff')), 'Fine-grained capability enforced');
    $logged_in = false;
    check(is_wp_error(Management::permission(new WP_REST_Request(), 'admincafe_manage_menu')), 'Guests denied');
    $logged_in = true;
    $products[1] = new class { public function get_category_ids() { return [7]; } };
    $products[2] = new class { public function get_category_ids() { return [8]; } };
    $management = new Management();
    check($management->reorder(new WP_REST_Request(['category_id' => 7, 'ids' => [1, 1]]))['ids'] === [1], 'Reorder deduplicates IDs');
    check(is_wp_error($management->reorder(new WP_REST_Request(['category_id' => 7, 'ids' => [2]]))), 'Cross-category reorder rejected');
    check($termmeta[7]['_admincafe_product_order'] === [1], 'Category-specific ordering persisted');
    $users = [1 => (object) ['ID' => 1, 'roles' => ['administrator']], 10 => (object) ['ID' => 10, 'roles' => ['admincafe_manager']], 11 => (object) ['ID' => 11, 'roles' => ['admincafe_manager']]];
    check(is_wp_error($management->saveStaff(new WP_REST_Request(['role' => 'administrator']))), 'Administrator assignment denied');
    check(is_wp_error($management->saveStaff(new WP_REST_Request(['role' => 'admincafe_manager']))), 'Manager escalation denied');
    check(is_wp_error($management->saveStaff(new WP_REST_Request(['role' => 'admincafe_staff']), 1)), 'Admin account protected');
    check(is_wp_error($management->saveStaff(new WP_REST_Request(['role' => 'admincafe_staff']), 10)), 'Self demotion denied');
    check(is_wp_error($management->saveStaff(new WP_REST_Request(['role' => 'admincafe_staff']), 11)), 'Manager account protected from panel manager');
    $orders = [new class {
        public function get_meta($key) { return $key === '_admincafe_channel' ? 'table' : 'awaiting_approval'; }
        public function get_status() { return 'processing'; }
        public function get_date_created() { return new DateTime('now', wp_timezone()); }
        public function is_paid() { return true; }
        public function get_date_paid() { return new DateTime(); }
        public function get_currency() { return 'USD'; }
        public function get_total() { return '100.00'; }
        public function get_total_refunded() { return '20.00'; }
        public function get_items() { return []; }
    }, new class {
        public function get_meta($key) { return ''; }
    }];
    $report = Reports::summary(7);
    check($report['revenue'] === 80.0, 'Report subtracts refunded revenue');
    check($report['order_count'] === 1 && $report['pending_count'] === 1, 'Report only counts Fandoogh Rest orders');
    check(count($report['daily']) === 7 && $report['currency_symbol'] === '$', 'Daily calendar and decoded currency');
    $orders = [new class {
        public function get_meta($key) { return $key === '_admincafe_channel' ? 'table' : 'cancelled'; }
        public function get_status() { return 'cancelled'; }
        public function get_date_created() { return new DateTime('now', wp_timezone()); }
        public function is_paid() { return false; }
        public function get_date_paid() { return new DateTime(); }
        public function get_currency() { return 'USD'; }
        public function get_total() { return '80.00'; }
        public function get_total_refunded() { return '0.00'; }
        public function get_items() { return []; }
    }];
    check(Reports::summary()['revenue'] === 80.0, 'Historic payment remains revenue after financial cancellation without refund');
    echo "Backend behavior tests: {$checks} passed\n";
}
