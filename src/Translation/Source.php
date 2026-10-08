<?php
namespace FandooghRest\Translation;
defined('ABSPATH') || exit;
use FandooghRest\Branches\Branches;
final class Source
{
    public const PROVENANCE='_admincafe_translation_provenance';
    public const MANUAL='_admincafe_translation_manual';
    public static bool $applying=false;
    public static function read(string $scope,int $id): ?array
    {
        if ($scope === 'settings' && $id > 0 && Branches::current() !== $id) {
            return Branches::runFor($id, static fn(): ?array => self::read($scope, $id));
        }
        if ($scope==='product') {
            $p=wc_get_product($id);
            if (!$p||$p->get_status()==='trash') {
                return null;
            }
            $fields=['name'=>$p->get_name('edit'),'description'=>$p->get_description('edit'),'short_description'=>$p->get_short_description('edit')];
            $translations=(array)get_post_meta($id,'_admincafe_translations',true);
        }
        elseif ($scope==='category') {
            $t=get_term($id,'product_cat');
            if (!$t||is_wp_error($t)) {
                return null;
            }
            $fields=['name'=>$t->name];
            $translations=(array)get_term_meta($id,'_admincafe_translations',true);
        }
        elseif ($scope==='settings') {
            $s=\FandooghRest\Core\Settings::all();
            $fields=[];
            foreach (['restaurant_name','tagline','restaurant_address','hours_text'] as $key) {
                $fields[$key]=(string)$s[$key];
            }
            foreach (['unavailable','closed','order_received'] as $key) {
                $fields['messages.'.$key]=(string)($s['messages'][$key]??'');
            }
            $translations=[];
            foreach ((array)$s['content_translations'] as $lang=>$map) {
                $translations[$lang]=self::flatten((array)$map);
            }
        }
        else {
            return null;
        }
        $fields=array_map(static fn($v)=>trim(wp_strip_all_tags($v)),$fields);
        return ['fields'=>$fields,'translations'=>$translations,'hash'=>hash('sha256',wp_json_encode($fields)),'name'=>$fields['name']??$fields['restaurant_name']??'','manual'=>self::metadata($scope,$id,self::MANUAL)];
    }
    public static function flatten(array $map): array {
        $out=$map;
        unset($out['messages']);
        foreach ((array)($map['messages']??[]) as $key=>$v) {
            $out['messages.'.$key]=$v;
        }
        return $out;
    }
    public static function optionKey(string $key): string {
        return 'admincafe_translation_'.str_replace('_admincafe_translation_','',$key) . (Branches::current() === Branches::defaultId() ? '' : '_branch_' . Branches::current());
    }
    public static function metadata(string $scope,int $id,string $key): array {
        return (array)($scope==='settings'?get_option(self::optionKey($key),[]):($scope==='category'?get_term_meta($id,$key,true):get_post_meta($id,$key,true)));
    }
    public static function putMetadata(string $scope,int $id,string $key,array $value): void {
        if ($scope==='settings') {
            update_option(self::optionKey($key),$value,false);
        }
        elseif ($scope==='category') {
            update_term_meta($id,$key,$value);
        }
        else {
            update_post_meta($id,$key,$value);
        }
    }
    public static function manual(string $scope,int $id,array $patch): void
    {
        if (self::$applying) {
            return;
        }
        $provenance=self::metadata($scope,$id,self::PROVENANCE);
        $manual=self::metadata($scope,$id,self::MANUAL);
        foreach ($patch as $lang=>$fields) {
            if (!in_array($lang,['en','zh','tr'],true)||!is_array($fields)) {
                continue;
            }
            foreach (self::flatten($fields) as $field=>$value) {
                if (!is_string($value)) {
                    continue;
                }
                $hash=hash('sha256',sanitize_textarea_field(wp_strip_all_tags($value)));
                if ($value===''||($provenance[$lang][$field]??null)!==$hash) {
                    $manual[$lang][$field]=true;
                }
            }
        }
        self::putMetadata($scope,$id,self::MANUAL,$manual);
    }
    public static function eligible(string $scope,int $id,array $source,string $lang,string $field): bool
    {
        if (!empty(self::metadata($scope,$id,self::MANUAL)[$lang][$field])) {
            return false;
        }
        $existing=$source['translations'][$lang][$field]??'';
        return $existing===''||(self::metadata($scope,$id,self::PROVENANCE)[$lang][$field]??null)===hash('sha256',$existing);
    }
    /** Compare-and-swap the complete translation map; a concurrent manual write wins. */
    public static function commit(string $scope,int $id,array $before,array $translations,array $provenance): bool
    {
        global $wpdb;
        $transaction=true;
        if ($wpdb->query('START TRANSACTION')===false) {
            return false;
        }
        $locked=$wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name IN ('admincafe_settings','admincafe_translation_config','fandoogh_branches') FOR UPDATE");
        if ($wpdb->last_error) {
            $wpdb->query('ROLLBACK');
            return false;
        }
        wp_cache_delete('fandoogh_branches','options');
        wp_cache_delete('admincafe_settings','options');
        wp_cache_delete(Config::OPTION,'options');
        wp_cache_delete('alloptions','options');
        if ($scope!=='settings') {
            $table=$scope==='category'?$wpdb->terms:$wpdb->posts;
            $column=$scope==='category'?'term_id':'ID';
            $wpdb->get_var($wpdb->prepare("SELECT $column FROM $table WHERE $column=%d FOR UPDATE",$id));
            if ($wpdb->last_error) {
                $wpdb->query('ROLLBACK');
                return false;
            }
            if ($scope==='category') {
                clean_term_cache($id,'product_cat');
            }
            else {
                clean_post_cache($id);
            }
        }
        $ok=false;
        self::$applying=true;
        try {
            if ((self::read($scope,$id)['hash']??null)!==$before['hash']) {
                return false;
            }
            if (isset($before['config_version'])&&(Config::read()['version']!==$before['config_version']||!Config::read()['enabled']||!Queue::inScope($scope,$id))) {
                return false;
            }
            $expectedManual=$before['manual']??self::metadata($scope,$id,self::MANUAL);
            if (self::metadata($scope,$id,self::MANUAL)!==$expectedManual) {
                return false;
            }
            if ($scope==='settings') {
                global $wpdb;
                $option = Branches::current() === Branches::defaultId() ? 'admincafe_settings' : 'fandoogh_branches';
                $old=get_option($option,[]);
                $next=$old;
                $nested=[];
                foreach ($translations as $lang=>$fields) {
                    foreach ($fields as $field=>$v) {
                        if (str_starts_with($field,'messages.')) {
                            $nested[$lang]['messages'][substr($field,9)]=$v;
                        }
                        else {
                            $nested[$lang][$field]=$v;
                        }
                    }
                }
                if (self::read($scope,$id)['translations']!==$before['translations']) {
                    return false;
                }
                if ($option === 'admincafe_settings') { $next['content_translations']=$nested; }
                else {
                    $index = null;
                    foreach ($next as $key => $branch) { if ((int) ($branch['id'] ?? 0) === Branches::current()) { $index = $key; break; } }
                    if ($index === null) { return false; }
                    $next[$index]['settings']['content_translations']=$nested;
                }
                $ok=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s AND NOT EXISTS (SELECT 1 FROM (SELECT option_value FROM {$wpdb->options} WHERE option_name=%s LIMIT 1) AS ac_manual WHERE option_value<>%s)",maybe_serialize($next),$option,maybe_serialize($old),self::optionKey(self::MANUAL),maybe_serialize($expectedManual)))===1;
                wp_cache_delete($option,'options');
                wp_cache_delete('alloptions','options');
            }
            else {
                $kind=$scope==='category'?'term':'post';
                $key='_admincafe_translations';
                $old=get_metadata($kind,$id,$key,true);
                if ((array)$old!==$before['translations']) {
                    return false;
                }
                global $wpdb;
                $table=$kind==='term'?$wpdb->termmeta:$wpdb->postmeta;
                $column=$kind==='term'?'term_id':'post_id';
                // WordPress update_metadata ignores an empty previous value. An explicit SQL predicate is required.
                // LIMIT prevents MySQL from merging the derived table into the UPDATE target (error 1093).
                $ok=$wpdb->query($wpdb->prepare("UPDATE $table SET meta_value=%s WHERE $column=%d AND meta_key=%s AND meta_value=%s AND NOT EXISTS (SELECT 1 FROM (SELECT meta_value FROM $table WHERE $column=%d AND meta_key=%s LIMIT 1000) AS ac_manual WHERE meta_value<>%s)",maybe_serialize($translations),$id,$key,maybe_serialize($old),$id,self::MANUAL,maybe_serialize($expectedManual)))>0;
                wp_cache_delete($id,$kind.'_meta');
            }
            if ($ok) {
                self::putMetadata($scope,$id,self::PROVENANCE,$provenance);
            }
            return (bool)$ok;
        }
        finally {
            if ($transaction) {
                $wpdb->query($ok?'COMMIT':'ROLLBACK');
            }
            self::$applying=false;
        }
    }
}
