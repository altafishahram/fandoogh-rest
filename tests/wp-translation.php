<?php
/** Disposable WordPress worker regressions. Every HTTP request is blocked. */
$_SERVER['HTTP_HOST']='127.0.0.1:8093'; $_SERVER['REQUEST_URI']='/';
if (!defined('ABSPATH')) { require $argv[1]??__DIR__.'/../.tools/site/wordpress/wp-load.php'; }
if (get_option('admincafe_test_environment')!=='local-disposable') { throw new RuntimeException('Disposable environment required.'); }
use AdminCafe\Translation\Config;
use AdminCafe\Translation\Provider;
use AdminCafe\Translation\Queue;
use AdminCafe\Translation\Source;
use AdminCafe\Core\Settings;
$passed=0;
function ac_translation_check(bool $value,string $label): void { global $passed; if (!$value) { throw new RuntimeException('FAIL after '.$passed.': '.$label); } $passed++; }
$block=static fn()=>new WP_Error('blocked','Test blocks all HTTP.'); add_filter('pre_http_request',$block,PHP_INT_MAX);
add_filter('pre_wp_mail','__return_true');
$options=[];
foreach ([Config::OPTION,'admincafe_settings','admincafe_translation_usage','admincafe_translation_provenance','admincafe_translation_sources','admincafe_translation_manual'] as $key) { $options[$key]=get_option($key,null); }
$products=[]; $terms=[]; $jobs=[]; $calls=[];
$mock=new class implements Provider {
    public array $calls=[];
    public function translate(array $texts,string $target): array|WP_Error { $this->calls[]=[$texts,$target]; return array_map(static fn($text)=>$target.':'.$text,$texts); }
};
$filter=static fn()=>$mock; add_filter('admincafe_translation_provider',$filter);
try {
    Source::$applying=true;
    $settings=Settings::all(); $settings['enabled_languages']=['fa','en','zh','tr']; $settings['menu_category_ids']=[]; update_option('admincafe_settings',$settings,false);
    $term=wp_insert_term('دسته آزمایشی '.wp_generate_password(6,false),'product_cat'); $category=(int)$term['term_id']; $terms[]=$category;
    $p=new WC_Product_Simple(); $p->set_name('قهوه آزمایشی'); $p->set_description('توضیح آزمایشی'); $p->set_short_description('کوتاه'); $p->set_status('publish'); $p->set_regular_price('250'); $p->set_category_ids([$category]); $p->save(); $products[]=$p->get_id();
    Source::$applying=false;
    Config::update(['enabled'=>true,'api_key'=>str_repeat('mock_key_',5),'target_languages'=>['en','zh','tr']]);
    Queue::install();
    $job=Queue::enqueue('product',$p->get_id()); $jobs[]=$job;
    ac_translation_check($job>0,'published menu item queued');
    ac_translation_check(Queue::enqueue('product',$p->get_id())===0,'same generation deduped');
    Queue::work($job); ac_translation_check(count($mock->calls)===1,'one language per action checkpoint');
    $translations=get_post_meta($p->get_id(),'_admincafe_translations',true);
    ac_translation_check(isset($translations['en']['name'])&&!isset($translations['zh']),'first language output committed durably');
    Queue::work($job); Queue::work($job);
    $translations=get_post_meta($p->get_id(),'_admincafe_translations',true);
    foreach (['en','zh','tr'] as $lang) { ac_translation_check($translations[$lang]['name']===$lang.':قهوه آزمایشی','generated target '.$lang); }
    ac_translation_check(wc_get_product($p->get_id())->get_regular_price()==='250','price unchanged');
    Source::manual('product',$p->get_id(),$translations);
    ac_translation_check(!Source::metadata('product',$p->get_id(),Source::MANUAL),'unchanged editor map keeps automatic provenance');
    $p=wc_get_product($p->get_id()); $p->set_regular_price('300'); $p->save();
    ac_translation_check(Queue::enqueue('product',$p->get_id())===0,'price-only save does not enqueue generation');
    $before=count($mock->calls); $p=wc_get_product($p->get_id()); $p->set_description('توضیح جدید'); $p->save();
    global $wpdb; $job=(int)$wpdb->get_var($wpdb->prepare('SELECT MAX(id) FROM '.Queue::table().' WHERE item_id=%d',$p->get_id())); $jobs[]=$job;
    Queue::work($job); Queue::work($job); Queue::work($job);
    ac_translation_check(count($mock->calls)===$before+3,'changed source regenerates each enabled language');
    foreach (array_slice($mock->calls,$before) as [$texts,$lang]) { ac_translation_check($texts===['توضیح جدید'],'only changed FA field sent'); }
    $translations=get_post_meta($p->get_id(),'_admincafe_translations',true); $translations['en']['name']='Human correction'; unset($translations['zh']['name']);
    update_post_meta($p->get_id(),'_admincafe_translations',$translations); Source::manual('product',$p->get_id(),['en'=>['name'=>'Human correction'],'zh'=>['name'=>'']]);
    $p=wc_get_product($p->get_id()); $p->set_name('نام جدید'); $p->save();
    $job=(int)$wpdb->get_var($wpdb->prepare('SELECT MAX(id) FROM '.Queue::table().' WHERE item_id=%d',$p->get_id())); $jobs[]=$job; Queue::work($job); Queue::work($job); Queue::work($job);
    $translations=get_post_meta($p->get_id(),'_admincafe_translations',true);
    ac_translation_check($translations['en']['name']==='Human correction','manual correction survives source edits');
    ac_translation_check(empty($translations['zh']['name']),'explicit blank remains cleared');
    ac_translation_check($translations['tr']['name']==='tr:نام جدید','unprotected target updates');
    $p=wc_get_product($p->get_id()); $p->set_description(''); $p->save();
    $job=(int)$wpdb->get_var($wpdb->prepare('SELECT MAX(id) FROM '.Queue::table().' WHERE item_id=%d',$p->get_id())); $jobs[]=$job; Queue::work($job); Queue::work($job); Queue::work($job);
    $translations=get_post_meta($p->get_id(),'_admincafe_translations',true);
    foreach (['en','zh','tr'] as $lang) { ac_translation_check(($translations[$lang]['description']??null)==='','cleared canonical field removes automatic target '.$lang); }
    $job=Queue::enqueue('category',$category); $jobs[]=$job; Queue::work($job); Queue::work($job); Queue::work($job);
    ac_translation_check(count((array)get_term_meta($category,'_admincafe_translations',true))===3,'category targets generated');
    Source::$applying=true;
    // This fixture owns the restaurant translation state; prior integration suites may leave manual intent.
    foreach (['admincafe_translation_provenance','admincafe_translation_manual','admincafe_translation_sources'] as $key) { delete_option($key); }
    $settings=Settings::all(); $settings['content_translations']=[]; $settings['restaurant_name']='رستوران آزمایشی'; update_option('admincafe_settings',$settings,false); Source::$applying=false;
    $job=Queue::enqueue('settings',0); $jobs[]=$job; Queue::work($job); Queue::work($job); Queue::work($job);
    ac_translation_check(Settings::get('content_translations')['en']['restaurant_name']==='en:رستوران آزمایشی','restaurant fields generated');
    ac_translation_check(isset(Settings::get('content_translations')['en']['messages']['closed']),'nested messages rebuilt');
    $p=wc_get_product($p->get_id()); $p->set_status('draft'); $p->save(); ac_translation_check(Queue::enqueue('product',$p->get_id())===0,'draft nonmenu excluded');
    $p->set_status('publish'); $p->update_meta_data('_admincafe_visible','no'); $p->save(); ac_translation_check(Queue::enqueue('product',$p->get_id())===0,'hidden menu excluded');
    $status=Queue::status(); ac_translation_check(!str_contains(wp_json_encode($status),'mock_key_'),'status never exposes key');
    $lease=Queue::lock('test-lease'); ac_translation_check(is_string($lease),'atomic lease acquired');
    Queue::unlock('test-lease','wrong-owner'); ac_translation_check(Queue::lock('test-lease')===null,'another owner cannot release lease');
    Queue::unlock('test-lease',$lease); $lease=Queue::lock('test-lease'); ac_translation_check(is_string($lease),'owned lease release permits reacquire'); Queue::unlock('test-lease',$lease);
    Config::update(['daily_character_limit'=>2]); update_option('admincafe_translation_usage',['date'=>'2000-01-01','characters'=>999],false);
    ac_translation_check(Queue::reserve(2),'daily quota resets on UTC day boundary'); ac_translation_check(!Queue::reserve(1),'atomic quota blocks excess');
    Config::update(['daily_character_limit'=>100000,'target_languages'=>['en']]);
    Source::$applying=true; $extra=new WC_Product_Simple(); $extra->set_name('غذای جدید'); $extra->set_status('publish'); $extra->save(); $products[]=$extra->get_id(); Source::$applying=false;
    $failure=new class implements Provider { public function translate(array $texts,string $target): array|WP_Error { return new WP_Error('translation_transient','Unsafe external text never stored',['retryable'=>true]); } };
    remove_filter('admincafe_translation_provider',$filter); $failureFilter=static fn()=>$failure; add_filter('admincafe_translation_provider',$failureFilter);
    $job=Queue::enqueue('product',$extra->get_id()); $jobs[]=$job; Queue::work($job); Queue::work($job); Queue::work($job);
    $row=$wpdb->get_row($wpdb->prepare('SELECT status,attempts,error FROM '.Queue::table().' WHERE id=%d',$job),ARRAY_A);
    ac_translation_check($row['status']==='failed'&&(int)$row['attempts']===3,'transient retry bounded to three attempts');
    ac_translation_check(!str_contains(wp_json_encode(Queue::status()),'Unsafe external'),'external failure message redacted');
    $wpdb->update(Queue::table(),['status'=>'running','attempts'=>2,'updated_at'=>gmdate('Y-m-d H:i:s',time()-1000)],['id'=>$job]); Queue::recover();
    ac_translation_check($wpdb->get_var($wpdb->prepare('SELECT status FROM '.Queue::table().' WHERE id=%d',$job))==='queued','stuck worker recovered');
    $wpdb->update(Queue::table(),['status'=>'running','attempts'=>3,'updated_at'=>gmdate('Y-m-d H:i:s',time()-1000)],['id'=>$job]); Queue::recover();
    ac_translation_check($wpdb->get_var($wpdb->prepare('SELECT status FROM '.Queue::table().' WHERE id=%d',$job))==='failed','stuck worker attempts capped');
    Config::update(['daily_character_limit'=>99999]); $retry=Queue::retry([$job]);
    ac_translation_check($retry['queued']===1,'retry renews stale configuration generation');
    $renewed=(int)$wpdb->get_var($wpdb->prepare('SELECT MAX(id) FROM '.Queue::table().' WHERE item_id=%d',$extra->get_id())); $jobs[]=$renewed;
    ac_translation_check($renewed!==$job,'renewed job has separate durable identity');
    remove_filter('admincafe_translation_provider',$failureFilter);
    $oversized=new class implements Provider { public function translate(array $texts,string $target): array|WP_Error { return array_map(static fn()=>str_repeat('a',251),$texts); } };
    $lengthFilter=static fn()=>$oversized; add_filter('admincafe_translation_provider',$lengthFilter); Queue::work($renewed);
    ac_translation_check($wpdb->get_var($wpdb->prepare('SELECT error FROM '.Queue::table().' WHERE id=%d',$renewed))==='length','automatic names obey editor limits');
    ac_translation_check(!get_post_meta($extra->get_id(),'_admincafe_translations',true),'invalid response never stored');
    remove_filter('admincafe_translation_provider',$lengthFilter);
    echo 'wp-translation: '.$passed." assertions passed; HTTP blocked and provider mocked.\n";
} finally {
    Source::$applying=true; Config::update(['enabled'=>false]);
    foreach ($products as $id) { $p=wc_get_product($id); if ($p) { $p->delete(true); } }
    foreach ($terms as $id) { wp_delete_term($id,'product_cat'); }
    foreach ($options as $key=>$value) { if ($value===null) { delete_option($key); } else { update_option($key,$value,false); } }
    foreach (array_unique($jobs) as $id) { if ($id) { $wpdb->delete(Queue::table(),['id'=>$id]); wp_clear_scheduled_hook('admincafe_translation_work',[$id]); if (function_exists('as_unschedule_all_actions')) { as_unschedule_all_actions('admincafe_translation_work',[$id],'admincafe-translation'); } } }
    Source::$applying=false; remove_filter('admincafe_translation_provider',$filter);
    // Keep outbound HTTP blocked through WordPress shutdown and async scheduler dispatch.
}
