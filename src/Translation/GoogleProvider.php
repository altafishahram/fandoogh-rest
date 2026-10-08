<?php
namespace AdminCafe\Translation;
defined('ABSPATH') || exit;
final class GoogleProvider implements Provider
{
    public function translate(array $texts, string $target): array|\WP_Error
    {
        if (!in_array($target,['en','zh','tr'],true)||!$texts||count($texts)>128) {
            return self::error('response');
        }
        $result=[];
        foreach ($texts as $text) {
            if (!is_string($text)||mb_strlen($text)>20000) {
                return self::error('response');
            }
            $translated='';
            foreach (self::chunks($text) as $chunk) {
                if (!Queue::reserve(mb_strlen($chunk))) {
                    return self::error('budget');
                }
                $body=wp_json_encode(['q'=>[$chunk],'source'=>'fa','target'=>$target==='zh'?'zh-CN':$target,'format'=>'text','model'=>'nmt'],JSON_UNESCAPED_UNICODE);
                $response=wp_remote_post('https://translation.googleapis.com/language/translate/v2',['headers'=>['X-Goog-Api-Key'=>Config::credential(),'Content-Type'=>'application/json'],'body'=>$body,'timeout'=>8,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>262144]);
                if (is_wp_error($response)) {
                    return self::error('transient');
                }
                $code=wp_remote_retrieve_response_code($response);
                $json=json_decode(wp_remote_retrieve_body($response),true);
                if ($code!==200) {
                    $reason=$json['error']['errors'][0]['reason']??'';
                    return self::error($code===429||$code>=500||$reason==='userRateLimitExceeded' ? 'transient' : ($reason==='dailyLimitExceeded'?'budget':'credential'));
                }
                $values=$json['data']['translations']??null;
                if (!is_array($values)||count($values)!==1||!is_string($values[0]['translatedText']??null)||trim($values[0]['translatedText'])===''||mb_strlen($values[0]['translatedText'])>40000) {
                    return self::error('response');
                }
                $translated.=sanitize_textarea_field(wp_strip_all_tags(html_entity_decode($values[0]['translatedText'],ENT_QUOTES|ENT_HTML5,'UTF-8')));
                if (preg_match('/\s$/u',$chunk,$boundary)) {
                    $translated.=$boundary[0];
                }
            }
            $result[]=$translated;
        }
        return $result;
    }
    private static function chunks(string $text): array
    {
        $chunks=[];
        while (mb_strlen($text)>4000) {
            $part=mb_substr($text,0,4000);
            $cut=mb_strrpos($part,"\n");
            if ($cut===false||$cut<2000) {
                $cut=mb_strrpos($part,' ');
            }
            $length=$cut!==false&&$cut>=2000?$cut+1:4000;
            $chunks[]=mb_substr($text,0,$length);
            $text=mb_substr($text,$length);
        }
        if ($text!=='') {
            $chunks[]=$text;
        }
        return $chunks;
    }
    public static function error(string $code): \WP_Error {
        return new \WP_Error('translation_'.$code,__('Automatic translation could not be completed.', 'admincafe'),['retryable'=>$code==='transient']);
    }
}
