<?php
// Run with `php tests/commerce.php`; tests exercise server rules without WordPress.
namespace AdminCafe\Core {
    final class Settings { public static array $data=[]; public static function get($key) { return self::$data[$key]??false; } }
    final class Security { public static function sessionKey() { return 'test-session'; } }
}
namespace AdminCafe\Tables { final class Tables { public static function context($token) { return ['token'=>$token,'can_order'=>true]; } } }
namespace AdminCafe\Localization {
    final class Language {
        public static string $selected='fa'; public static bool $customer=true;
        public static array $codes=['fa','en','zh','tr'];
        public static function enabled() { return self::$codes; }
        public static function resolve($language=null) { return $language??self::$selected; }
        public static function remember($language) { self::$selected=$language; return true; }
        public static function isCustomerRequest() { return self::$customer; }
    }
}
namespace AdminCafe\Menu {
    final class Catalog {
        public static function menu($language=null): array { return ['products'=>[['id'=>1],['id'=>2]],'categories'=>[]]; }
        public static function localizedName($product,$language) { return $language.':'.$product->get_name(); }
        public static function localizedText($product,$field,$language) { return $language.':'.$field; }
    }
}
namespace {
    function __($message, $domain = '') { return $message; }
    class WP_Error { public function __construct(public string $code,public string $message,public array $data=[]) {} }
    function absint($v) { return abs((int)$v); }
    function is_wp_error($v) { return $v instanceof WP_Error; }
    function sanitize_text_field($v) { return trim(strip_tags((string)$v)); }
    function wp_strip_all_tags($v) { return strip_tags((string)$v); }
    function esc_html($v) { return htmlspecialchars($v,ENT_QUOTES); }
    function wp_kses_post($v) { return $v; }
    function wc_format_content($v) { return $v; }
    function wc_attribute_label($key,$product=null) { return ucfirst($key); }
    function taxonomy_exists($name) { return false; }
    function wp_json_encode($v) { return json_encode($v); }
    function apply_filters($name,$value) { return $value; }
    function wp_get_current_user() { return (object)['roles'=>['admincafe_staff']]; }
    function current_user_can($cap) { return false; }
    function get_current_user_id() { return 10; }
    function get_woocommerce_currency_symbol($currency='') { return '$'; }
    function get_option($key) { return $GLOBALS['options'][$key]??false; }
    function update_option($key,$value,$autoload=null) { $GLOBALS['options'][$key]=$value; }
    function add_option($key,$value,$deprecated='',$autoload=false) { if (isset($GLOBALS['options'][$key])) return false; $GLOBALS['options'][$key]=$value; return true; }
    function wp_cache_delete($key,$group='') {}
    function wc_get_orders($args) { return $GLOBALS['recovery_orders']??[]; }
    function wc_get_order($id) { return $GLOBALS['test_order']??false; }
    function WC() { return $GLOBALS['wc']; }
    function do_action($hook,...$args) { if ($GLOBALS['throw_notification']??false) throw new RuntimeException('Notification failure'); }
    function wc_get_logger() { return new class { public function error($message,$context) { $GLOBALS['logged_errors'][]=$message; } }; }
    class WC_Order {
        public array $meta=['_admincafe_channel'=>'pickup','_admincafe_stage'=>'awaiting_approval'];
        public string $status='pending'; public string $method='stripe'; public bool $paid=false; public int $saves=0;
        public function get_meta($key) { return $this->meta[$key]??''; }
        public function update_meta_data($key,$value) { $this->meta[$key]=$value; }
        public function delete_meta_data($key) { unset($this->meta[$key]); }
        public function get_id() { return 42; }
        public function get_order_number() { return '42'; }
        public function get_total() { return '10'; }
        public function get_currency() { return 'USD'; }
        public function get_status() { return $this->status; }
        public function get_payment_method() { return $this->method; }
        public function is_paid() { return $this->paid; }
        public array $items=[];
        public function get_items() { return $this->items; }
        public function get_date_created() { return null; }
        public function get_date_paid() { return $this->paid ? new DateTime() : null; }
        public function save() { $this->saves++; }
        public function __call($name,$args) { return ''; }
    }
    class WP_REST_Request implements ArrayAccess {
        public function __construct(public array $data=[]) {}
        public function get_json_params() { return $this->data; }
        public function get_route() { return $this->data['route']??''; }
        public function offsetExists($offset):bool { return isset($this->data[$offset]); }
        public function offsetGet($offset):mixed { return $this->data[$offset]??null; }
        public function offsetSet($offset,$value):void { $this->data[$offset]=$value; }
        public function offsetUnset($offset):void { unset($this->data[$offset]); }
    }
    class WP_REST_Response { public function __construct(public $data) {} public function header($key,$value) {} public function get_data() { return $this->data; } public function set_data($data) { $this->data=$data; } }
    class WC_Product {}
    class WC_Order_Item_Product {
        public array $meta=[];
        public function __construct(public $product=null) {}
        public function update_meta_data($key,$value) { $this->meta[$key]=$value; }
        public function delete_meta_data($key) { unset($this->meta[$key]); }
        public function get_meta($key) { return $this->meta[$key]??''; }
        public function meta_exists($key) { return array_key_exists($key,$this->meta); }
        public function get_product() { return $this->product; }
        public function get_name() { return $this->product->get_name(); }
        public function get_id() { return 77; }
        public function get_quantity() { return 1; }
        public function get_total() { return '10'; }
    }
    $wpdb=new class {
        public string $options='wp_options'; public int $deletes=0;
        public function prepare($sql,...$args) { return $args; }
        public function query($args) {
            [$key,$expected]=$args;
            if (isset($GLOBALS['options'][$key]) && (string)$GLOBALS['options'][$key]===$expected) { unset($GLOBALS['options'][$key]); $this->deletes++; return 1; }
            return 0;
        }
    };
    final class TestProduct extends WC_Product {
        public function __construct(public int $id,public string $type='simple',public int $parent=0,public bool $stock=true,public int $available=10) {}
        public function get_id() { return $this->id; }
        public function get_name() { return 'Canonical '.$this->id; }
        public array $meta=[]; public array $attributes=[];
        public function get_meta($key) { return $this->meta[$key]??''; }
        public function get_attributes() { return $this->attributes; }
        public function get_parent_id() { return $this->parent; }
        public function is_type($type) { return $this->type===$type; }
        public function is_purchasable() { return true; }
        public function is_in_stock() { return $this->stock; }
        public function get_price() { return '0'; }
        public function is_sold_individually() { return false; }
        public function has_enough_stock($q) { return $q<=$this->available; }
    }
    $products=[1=>new TestProduct(1),2=>new TestProduct(2,'variable'),3=>new TestProduct(3,'variation',2),4=>new TestProduct(4,'variation',99)];
    function wc_get_product($id) { return $GLOBALS['products'][$id]??null; }
    require __DIR__.'/../src/Commerce/Orders.php';
    require __DIR__.'/../src/Commerce/Checkout.php';
    require __DIR__.'/../src/Rest/Ordering.php';
    $checks=0;
    function check($ok,$message) { $GLOBALS['checks']++; if (!$ok) throw new \RuntimeException($message); }
    use AdminCafe\Commerce\Orders;
    use AdminCafe\Commerce\Checkout;
    check(!is_wp_error(Orders::validateItems([['product_id'=>1,'quantity'=>2]])),'Published zero-price product is orderable');
    check(is_wp_error(Orders::validateItems([['product_id'=>99,'quantity'=>1]])),'Hidden products rejected');
    check(is_wp_error(Orders::validateItems([['product_id'=>1,'quantity'=>1.5]])),'Fractional quantity rejected');
    check(is_wp_error(Orders::validateItems([['product_id'=>1,'quantity'=>-2]])),'Negative quantity rejected');
    check(is_wp_error(Orders::validateItems([['product_id'=>1,'quantity'=>6],['product_id'=>1,'quantity'=>6]])),'Duplicate lines cannot bypass stock');
    check(is_wp_error(Orders::validateItems([['product_id'=>2,'quantity'=>1]])),'Variable parent requires a choice');
    check(!is_wp_error(Orders::validateItems([['product_id'=>2,'variation_id'=>3,'quantity'=>1]])),'Matching variation accepted');
    check(is_wp_error(Orders::validateItems([['product_id'=>2,'variation_id'=>4,'quantity'=>1]])),'Cross-parent variation rejected');
    $products[1]->stock=false;
    check(is_wp_error(Orders::validateItems([['product_id'=>1,'quantity'=>1]])),'Unavailable items rejected');
    check(!in_array('preparing',Orders::transitions()['awaiting_approval'],true),'Kitchen requires staff approval');
    check(Orders::transitions()['cancelled']===[],'Cancelled requests cannot resume');
    check(is_wp_error(Checkout::allowed('pickup')),'Pickup disabled by default');
    \AdminCafe\Core\Settings::$data=['pickup_enabled'=>true];
    check(Checkout::allowed('pickup')===true,'Enabled pickup allowed');
    \AdminCafe\Core\Settings::$data['ordering_paused']=true;
    check(is_wp_error(Checkout::allowed('pickup')),'Pause authoritative at checkout');
    check(is_wp_error(Checkout::allowed('invalid')),'Unknown channel rejected');
    $wc=(object)['session'=>new class {
        public array $data=[];
        public function get($key,$default='') { return $this->data[$key]??$default; }
        public function set($key,$value) { $this->data[$key]=$value; }
    },'cart'=>new class {
        public array $items=[['product_id'=>1,'variation_id'=>0,'quantity'=>1]];
        public function get_cart() { return $this->items; }
    }];
    \AdminCafe\Core\Settings::$data=[];
    check(is_wp_error(Checkout::cartError()),'Visible menu products cannot bypass ordering switches through direct checkout');
    $wc->cart->items=[['product_id'=>99,'variation_id'=>0,'quantity'=>1]];
    check(Checkout::cartError()===true,'Unrelated ordinary Woo product checkout remains available');
    $wc->cart->items=[['product_id'=>1,'variation_id'=>0,'quantity'=>1]];
    $products[1]->stock=true;
    $wc->session->data['admincafe_channel']='pickup';
    \AdminCafe\Core\Settings::$data=['pickup_enabled'=>true];
    check(Checkout::cartError()===true,'Selected enabled pickup cart allowed');
    $test_order=new WC_Order();
    $checkout=new Checkout();
    $checkout->decorate($test_order);
    check($test_order->get_meta('_admincafe_stage')==='awaiting_approval','Online checkout waits for approval');
    check(is_wp_error(Orders::update($test_order,['stage'=>'accepted'])),'Unpaid online gateway cannot reach kitchen');
    $test_order->method='cod';
    check(!is_wp_error(Orders::update($test_order,['stage'=>'accepted'])),'Permitted offline gateway may be accepted');
    $test_order->meta['_admincafe_stage']='awaiting_approval'; $test_order->method='stripe'; $test_order->paid=true;
    check(!is_wp_error(Orders::update($test_order,['stage'=>'accepted'])),'Paid online order may be accepted');
    $throw_notification=true;
    $checkout->placed(42);
    check($test_order->get_meta('_admincafe_created_event')===true && count($logged_errors)===1,'Notification failure cannot fail persisted checkout');
    $checkout->placed(42);
    check(count($logged_errors)===1,'Checkout notification event remains deduplicated');
    $throw_notification=false;
    \AdminCafe\Core\Settings::$data=['dine_in_enabled'=>true];
    $input=['request_id'=>'request-1234567890','table_token'=>'table-token','items'=>[['product_id'=>1,'quantity'=>1]]];
    $scope=hash('sha256','test-session|table-token|'.$input['request_id']);
    $fingerprint=hash('sha256',wp_json_encode([$input['items'],'','']));
    $marker='admincafe_request_'.$scope; $token=str_repeat('a',64);
    $options[$marker]=['fingerprint'=>$fingerprint,'created'=>time()-120,'token'=>$token];
    $recovery_orders=[];
    check(Orders::create($input)->code==='request_recovery','Uncertain old idempotency requests never create duplicate orders');
    $test_order->meta['_admincafe_request_scope']=$scope;
    $test_order->meta['_admincafe_request_fingerprint']=$fingerprint;
    $test_order->meta['_admincafe_request_complete']=true;
    $test_order->meta['_admincafe_tracking_hash']=hash('sha256',$token);
    $recovery_orders=[$test_order];
    check(Orders::create($input)['tracking_token']===$token,'Persisted complete request recovers original tracking token');
    $retryLanguage=$input; $retryLanguage['language']='en';
    check(Orders::create($retryLanguage)['tracking_token']===$token,'Display language change does not duplicate or conflict with financial order');
    \AdminCafe\Localization\Language::remember('fa');
    check($options[$marker]['order_id']===42,'Recovered request mapping persisted');
    $changed=$input; $changed['items'][0]['quantity']=2;
    check(Orders::create($changed)->code==='request_conflict','Idempotency request cannot change payload');
    $ordering=new \AdminCafe\Rest\Ordering();
    $options['admincafe_order_lock_42']=time();
    check(is_wp_error($ordering->update(new WP_REST_Request(['id'=>42]))),'Active order lock denies concurrent mutation');
    $options['admincafe_order_lock_42']=time()-600;
    check($ordering->update(new WP_REST_Request(['id'=>42])) instanceof WP_REST_Response,'Expired order lock recovers');
    check(!isset($options['admincafe_order_lock_42']) && $wpdb->deletes===2,'Stale and own locks removed with compare-and-delete');
    check(is_wp_error(Checkout::language(['en'])),'Reject malformed language object');
    check(is_wp_error(Checkout::language('en<script>')),'Reject unrecognized customer language');
    check(Checkout::language('zh')==='zh','Accept enabled Chinese language');
    \AdminCafe\Localization\Language::$codes=['fa','en'];
    check(is_wp_error(Checkout::language('tr')),'Reject disabled supported language');
    \AdminCafe\Localization\Language::$codes=['fa','en','zh','tr'];
    $wc->session->data['admincafe_channel']='pickup';
    \AdminCafe\Localization\Language::remember('tr');
    $line=new WC_Order_Item_Product($products[1]);
    $checkout->lineItem($line,'cart-key',['data'=>$products[1]],$test_order);
    check($line->get_meta('_admincafe_localized_name')==='tr:Canonical 1','Classic checkout snapshots localized line name');
    check($line->get_name()==='Canonical 1','Translation snapshot preserves canonical Woo product name');
    $test_order->items=[$line]; $checkout->decorate($test_order);
    check($test_order->get_meta('_admincafe_language')==='tr','Checkout stores customer language');
    check(Orders::detail($test_order)['items'][0]['name']==='Canonical 1','Management uses canonical snapshot name');
    check($checkout->cartItemName('Canonical 1',['data'=>$products[1]],'key')==='tr:Canonical 1','Classic cart uses chosen language');
    check($checkout->orderItemName('Canonical 1',$line)==='tr:Canonical 1','Customer order uses immutable translation snapshot');
    $response=new WP_REST_Response(['items'=>[['id'=>1,'name'=>'Canonical 1','prices'=>['price'=>'10']]]]);
    $checkout->storeNames($response,null,new WP_REST_Request(['route'=>'/wc/store/v1/cart']));
    check($response->data['items'][0]['name']==='tr:Canonical 1' && $response->data['items'][0]['prices']['price']==='10','Blocks cart translates display name and preserves prices');
    $products[3]->meta['_admincafe_translations']=['tr'=>['name'=>'Coffee - large']];
    $products[3]->attributes=['size'=>'بزرگ'];
    $variantLine=new WC_Order_Item_Product($products[3]);
    Orders::snapshotItem($variantLine,$products[3],'tr');
    $data=$checkout->cartItemData([['key'=>'Size','value'=>'بزرگ'],['key'=>'Preparation note','value'=>'Less milk']],['data'=>$products[3]]);
    check(count($data)===1 && $data[0]['key']==='Preparation note','Translated variant suppresses only redundant canonical attribute rows');
    $meta=$checkout->orderItemMeta([1=>(object)['key'=>'size'],2=>(object)['key'=>'Preparation note']],$variantLine);
    check(count($meta)===1 && isset($meta[2]),'Customer order variant metadata keeps unrelated custom fields');
    $response=new WP_REST_Response(['items'=>[['id'=>3,'name'=>'Canonical 3','variation'=>[['attribute'=>'Size','value'=>'بزرگ']],'item_data'=>[['key'=>'Preparation note','value'=>'Less milk']]]]]);
    $checkout->storeNames($response,null,new WP_REST_Request(['route'=>'/wc/store/v1/cart']));
    check($response->data['items'][0]['variation']===[] && count($response->data['items'][0]['item_data'])===1,'Blocks translated variant hides redundant option rows and keeps custom data');
    Orders::snapshotItem($variantLine,$products[3],'fa');
    check($variantLine->get_meta('_admincafe_variant_keys')==='','Switching checkout draft back to Persian clears outdated suppression snapshot');
    \AdminCafe\Localization\Language::$customer=false;
    check($checkout->cartItemName('Canonical 1',['data'=>$products[1]],'key')==='Canonical 1','Admin context does not translate customer cart names');
    check($checkout->orderItemName('Canonical 1',$line)==='Canonical 1','Panel or notification context preserves canonical order names');
    echo "Commerce rules: {$checks} assertions passed.\n";
}
