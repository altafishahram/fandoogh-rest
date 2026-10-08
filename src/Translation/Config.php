<?php
namespace FandooghRest\Translation;
defined('ABSPATH') || exit;
final class Config
{
    public const OPTION = 'admincafe_translation_config';
    public static function raw(): array
    {
        $defaults=['enabled'=>false,'daily_character_limit'=>100000,'target_languages'=>['en','zh','tr'],'version'=>'initial','secret'=>''];
        $stored=get_option(self::OPTION,[]);
        if (!is_array($stored)) {
            return $defaults;
        }
        foreach ($defaults as $key=>$value) {
            if (isset($stored[$key])&&gettype($stored[$key])===gettype($value)) {
                $defaults[$key]=$stored[$key];
            }
        }
        $defaults['target_languages']=array_values(array_unique(array_filter($defaults['target_languages'],static fn($v)=>is_string($v)&&in_array($v,['en','zh','tr'],true))));
        if ($defaults['daily_character_limit']<1||$defaults['daily_character_limit']>10000000) {
            $defaults['daily_character_limit']=100000;
        }
        return $defaults;
    }
    public static function credential(): string
    {
        if (defined('FANDOOGH_REST_GOOGLE_TRANSLATE_API_KEY')) {
            return (string)FANDOOGH_REST_GOOGLE_TRANSLATE_API_KEY;
        }
        if (!function_exists('openssl_decrypt')) {
            return '';
        }
        $secret=self::raw()['secret'];
        if (!is_string($secret)) {
            return '';
        }
        $decoded=base64_decode($secret,true);
        if ($decoded===false) {
            return '';
        }
        $data = json_decode($decoded, true);
        if (!is_array($data)) {
            return '';
        }
        foreach (['data','iv','tag'] as $key) {
            if (!is_string($data[$key]??null)) {
                return '';
            }
        }
        $cipher=base64_decode($data['data'],true);
        $iv=base64_decode($data['iv'],true);
        $tag=base64_decode($data['tag'],true);
        if ($cipher===false||$iv===false||$tag===false||strlen($iv)!==12||strlen($tag)!==16) {
            return '';
        }
        $value = openssl_decrypt($cipher, 'aes-256-gcm', hash('sha256', wp_salt('auth'), true), OPENSSL_RAW_DATA, $iv, $tag);
        return is_string($value) ? $value : '';
    }
    public static function read(): array
    {
        $c=self::raw();
        unset($c['secret']);
        return $c+['configured'=>self::credential() !== '', 'provider'=>'google', 'credential_source'=>defined('FANDOOGH_REST_GOOGLE_TRANSLATE_API_KEY') ? 'constant' : (self::credential() !== '' ? 'stored':'none')];
    }
    public static function targets(): array {
        return array_values(array_intersect(self::raw()['target_languages'], (array)\FandooghRest\Core\Settings::get('enabled_languages', ['fa','en','zh','tr'])));
    }
    public static function update(array $input): array|\WP_Error
    {
        $c=self::raw();
        $before=$c;
        foreach ($input as $key=>$value) {
            if (!in_array($key,['enabled','daily_character_limit','target_languages','api_key','remove_key'],true)) {
                return self::invalid();
            }
            if (in_array($key,['enabled','remove_key'],true) && !is_bool($value)) {
                return self::invalid();
            }
            if ($key==='daily_character_limit' && (!is_int($value)||$value<1||$value>10000000)) {
                return self::invalid();
            }
            if ($key==='target_languages' && (!is_array($value)||!$value||count($value)>3||array_filter($value,static fn($v)=>!is_string($v)||!in_array($v,['en','zh','tr'],true)))) {
                return self::invalid();
            }
            if ($key==='api_key' && (!is_string($value)||($value!==''&&!preg_match('/^[A-Za-z0-9_-]{20,256}$/D',$value)))) {
                return self::invalid();
            }
            if (in_array($key,['enabled','daily_character_limit','target_languages'],true)) {
                $c[$key]=$value;
            }
        }
        if (defined('FANDOOGH_REST_GOOGLE_TRANSLATE_API_KEY') && (!empty($input['remove_key'])||!empty($input['api_key']))) {
            return self::invalid();
        }
        if (!empty($input['remove_key'])) {
            $c['secret']='';
        }
        if (!empty($input['api_key'])) {
            if (!function_exists('openssl_encrypt')) {
                return self::invalid();
            }
            $iv=random_bytes(12);
            $tag='';
            $cipher=openssl_encrypt($input['api_key'],'aes-256-gcm',hash('sha256',wp_salt('auth'),true),OPENSSL_RAW_DATA,$iv,$tag);
            if ($cipher===false) {
                return self::invalid();
            }
            $c['secret']=base64_encode(wp_json_encode(['iv'=>base64_encode($iv),'tag'=>base64_encode($tag),'data'=>base64_encode($cipher)]));
        }
        if ($c['enabled'] && !(defined('FANDOOGH_REST_GOOGLE_TRANSLATE_API_KEY') ? (string)FANDOOGH_REST_GOOGLE_TRANSLATE_API_KEY : (!empty($input['api_key'])?$input['api_key']:(!empty($input['remove_key'])?'':self::credential())))) {
            return self::invalid();
        }
        if ($c['enabled']&&!array_intersect($c['target_languages'],(array)\FandooghRest\Core\Settings::get('enabled_languages',['fa','en','zh','tr']))) {
            return self::invalid();
        }
        if ($c!==$before) {
            $c['version']=bin2hex(random_bytes(16));
            update_option(self::OPTION,$c,false);
        }
        return self::read();
    }
    private static function invalid(): \WP_Error {
        return new \WP_Error('admincafe_translation_config',__('Invalid translation settings.', 'fandoogh-rest'),['status'=>400]);
    }
}
