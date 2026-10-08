<?php
/** Standalone provider/configuration regressions. No HTTP requests or database. */
namespace FandooghRest\Translation {
    final class Queue { public static int $characters=0; public static bool $allow=true; public static function reserve(int $n): bool { self::$characters+=$n; return self::$allow; } }
}
namespace FandooghRest\Core { final class Settings { public static function get($key,$fallback=null) { return $fallback; } } }
namespace {
    require __DIR__ . '/branch-double.php';
    define('ABSPATH',__DIR__.'/');
    class WP_Error {
        public function __construct(private string $code,private string $message,private array $data=[]) {}
        public function get_error_code() { return $this->code; }
        public function get_error_data() { return $this->data; }
    }
    function __($text,$domain='') { return $text; }
    function get_option($key,$fallback=false) { return $GLOBALS['options'][$key]??$fallback; }
    function update_option($key,$value,$autoload=null) { $GLOBALS['options'][$key]=$value; return true; }
    function wp_salt($scheme) { return 'disposable-test-salt-only'; }
    function wp_json_encode($value,$flags=0) { return json_encode($value,$flags); }
    function is_wp_error($value) { return $value instanceof WP_Error; }
    function sanitize_textarea_field($text) { return trim(strip_tags($text)); }
    function wp_strip_all_tags($text) { return strip_tags($text); }
    function wp_remote_retrieve_response_code($response) { return $response['code']; }
    function wp_remote_retrieve_body($response) { return $response['body']; }
    function wp_remote_post($url,$args) { $GLOBALS['calls'][]=[$url,$args]; return ($GLOBALS['http'])($url,$args); }
    function get_post_meta($id,$key,$single=true) { return $GLOBALS['meta'][$id][$key]??[]; }
    function update_post_meta($id,$key,$value) { $GLOBALS['meta'][$id][$key]=$value; }
    function get_term_meta($id,$key,$single=true) { return get_post_meta($id,$key,$single); }
    function update_term_meta($id,$key,$value) { update_post_meta($id,$key,$value); }
    require __DIR__.'/../src/Translation/Config.php';
    require __DIR__.'/../src/Translation/Provider.php';
    require __DIR__.'/../src/Translation/GoogleProvider.php';
    require __DIR__.'/../src/Translation/Source.php';
    use FandooghRest\Translation\Config;
    use FandooghRest\Translation\GoogleProvider;
    use FandooghRest\Translation\Queue;
    use FandooghRest\Translation\Source;
    $checks=0;
    function check(bool $condition,string $label): void { global $checks; if (!$condition) { throw new RuntimeException('FAIL '.$label); } $checks++; }
    check(Config::read()['configured']===false,'unconfigured default');
    foreach ([['enabled'=>'true'],['daily_character_limit'=>0],['daily_character_limit'=>10000001],['daily_character_limit'=>'10'],['target_languages'=>['fa']],['target_languages'=>[]],['api_key'=>[]],['api_key'=>'bad key'],['unknown'=>true]] as $input) { check(is_wp_error(Config::update($input)),'strict configuration validation'); }
    $key=str_repeat('safe_fake_key_',3);
    $config=Config::update(['api_key'=>$key,'enabled'=>true]);
    check(!is_wp_error($config)&&$config['configured'],'encrypted credential enabled');
    check(Config::credential()===$key,'credential round trip');
    check(!str_contains(json_encode(Config::raw()),$key),'ciphertext only in private option');
    check(!str_contains(json_encode(Config::read()),$key)&&!isset(Config::read()['secret']),'redacted response');
    $version=Config::read()['version']; Config::update(['api_key'=>'']);
    check(Config::read()['version']===$version,'blank key preserves version');
    Config::update(['daily_character_limit'=>3]); check(Config::read()['version']!==$version,'configuration version invalidates old jobs');
    check(is_wp_error(Config::update(['remove_key'=>true])),'cannot keep enabled without credential');
    Config::update(['enabled'=>false,'remove_key'=>true]); check(!Config::read()['configured'],'credential removed');
    foreach ([[],['iv'=>[]],['iv'=>'x','tag'=>'y','data'=>'z']] as $bad) { $GLOBALS['options'][Config::OPTION]['secret']=base64_encode(json_encode($bad)); check(Config::credential()==='','malformed ciphertext fails closed'); }
    Config::update(['api_key'=>$key,'enabled'=>true,'daily_character_limit'=>100000]);
    $GLOBALS['http']=static fn($u,$a)=>['code'=>200,'body'=>json_encode(['data'=>['translations'=>[['translatedText'=>'&lt;b&gt;Hello&lt;/b&gt; &amp; welcome']]]])];
    $provider=new GoogleProvider(); $result=$provider->translate(['سلام'],'zh');
    check($result===['Hello & welcome'],'decoded entities are sanitized');
    [$url,$args]=$GLOBALS['calls'][0]; $body=json_decode($args['body'],true);
    check($url==='https://translation.googleapis.com/language/translate/v2','fixed provider endpoint');
    check(!str_contains($url,$key)&&$args['headers']['X-Goog-Api-Key']===$key,'credential only in header');
    check($body['source']==='fa'&&$body['target']==='zh-CN'&&$body['format']==='text'&&$body['model']==='nmt','official translation wire contract');
    check($args['sslverify']&&$args['redirection']===0&&$args['limit_response_size']===262144,'bounded verified HTTPS');
    check(Queue::$characters===4,'Unicode codepoint budget');
    foreach ([['data'=>['translations'=>[]]],['data'=>['translations'=>[['translatedText'=>[]]]]],['data'=>['translations'=>[['translatedText'=>'a'],['translatedText'=>'b']]]]] as $bad) { $GLOBALS['http']=static fn()=>['code'=>200,'body'=>json_encode($bad)]; check(is_wp_error($provider->translate(['سلام'],'en')),'strict provider response'); }
    foreach ([[429,true],[503,true],[403,false],[400,false]] as [$code,$retry]) { $GLOBALS['http']=static fn()=>['code'=>$code,'body'=>'secret error '.$key]; $error=$provider->translate(['سلام'],'tr'); check(is_wp_error($error)&&$error->get_error_data()['retryable']===$retry,'safe retry classification'); }
    $GLOBALS['http']=static fn()=>new WP_Error('network',$key); check($provider->translate(['سلام'],'en')->get_error_code()==='translation_transient','transport messages are discarded');
    Queue::$allow=false; $count=count($GLOBALS['calls']); check($provider->translate(['سلام'],'en')->get_error_code()==='translation_budget'&&count($GLOBALS['calls'])===$count,'budget prevents outbound call'); Queue::$allow=true;
    $GLOBALS['http']=static function($url,$args) { $q=json_decode($args['body'],true)['q'][0]; check(mb_strlen($q)<=4000&&strlen($args['body'])<100000,'bounded Unicode request chunk'); return ['code'=>200,'body'=>json_encode(['data'=>['translations'=>[['translatedText'=>trim($q)]]]])]; };
    $text=str_repeat('سلام ',1000).'آخر'; check($provider->translate([$text],'en')===[$text],'word boundaries and whitespace preserved');
    $GLOBALS['meta'][1][Source::PROVENANCE]=['en'=>['name'=>hash('sha256','Auto')]];
    Source::manual('product',1,['en'=>['name'=>'Auto']]); check(empty($GLOBALS['meta'][1][Source::MANUAL]),'unchanged generated map stays automatic');
    Source::manual('product',1,['en'=>['name'=>'Manual','description'=>'']]);
    check(!empty($GLOBALS['meta'][1][Source::MANUAL]['en']['name']),'changed target protected');
    check(!empty($GLOBALS['meta'][1][Source::MANUAL]['en']['description']),'explicit clear tombstone protected');
    check(!Source::eligible('product',1,['translations'=>['en'=>['description'=>'']]],'en','description'),'clear remains unavailable for refill');
    check(!Source::eligible('product',2,['translations'=>['en'=>['name'=>'Existing human']]],'en','name'),'existing translations protected without provenance');
    check(Source::eligible('product',2,['translations'=>[]],'tr','name'),'missing target eligible');
    echo 'translation: '.$checks." assertions passed; HTTP fully mocked.\n";
}
