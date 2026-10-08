<?php
namespace FandooghRest\Translation;
defined('ABSPATH') || exit;
final class Queue
{
    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix.'admincafe_translation_jobs';
    }
    public static function install(): void
    {
        if (get_option('admincafe_translation_schema')==='1') {
            return;
        }
        global $wpdb;
        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $table=self::table();
        $charset=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $table (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            job_key varchar(64) NOT NULL,
            scope varchar(16) NOT NULL,
            item_id bigint unsigned NOT NULL,
            source_hash varchar(64) NOT NULL,
            config_version varchar(64) NOT NULL,
            status varchar(16) NOT NULL DEFAULT 'queued',
            attempts int NOT NULL DEFAULT 0,
            error varchar(40) NOT NULL DEFAULT '',
            payload longtext NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY job_key (job_key),
            KEY status (status)
        ) $charset;");
        update_option('admincafe_translation_schema','1',false);
    }
    public static function schedule(string $hook,array $args,int $delay=0): void
    {
        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action(time()+$delay,$hook,$args,'admincafe-translation',false);
        }
        elseif (!wp_next_scheduled($hook,$args)) {
            wp_schedule_single_event(time()+$delay,$hook,$args);
        }
    }
    public static function enqueue(string $scope,int $id): int
    {
        $config=Config::read();
        $source=Source::read($scope,$id);
        if (!$config['enabled']||!$config['configured']||!Config::targets()||!$source||!self::inScope($scope,$id)) {
            return 0;
        }
        self::install();
        global $wpdb;
        if ($scope!=='settings'&&!metadata_exists($scope==='category'?'term':'post',$id,'_admincafe_translations')) {
            add_metadata($scope==='category'?'term':'post',$id,'_admincafe_translations',[],true);
        }
        if ($scope==='settings') {
            add_option(Source::optionKey(Source::MANUAL),[],'',false);
        }
        elseif (!metadata_exists($scope==='category'?'term':'post',$id,Source::MANUAL)) {
            add_metadata($scope==='category'?'term':'post',$id,Source::MANUAL,[],true);
        }
        $key=hash('sha256',$scope.':'.$id.':'.$source['hash'].':'.$config['version'].':'.implode(',',Config::targets()));
        $inserted=$wpdb->query($wpdb->prepare('INSERT IGNORE INTO '.self::table().' (job_key,scope,item_id,source_hash,config_version,payload,updated_at) VALUES (%s,%s,%d,%s,%s,%s,%s)',$key,$scope,$id,$source['hash'],$config['version'],wp_json_encode(['name'=>$source['name']]),gmdate('Y-m-d H:i:s')));
        if ($inserted!==1||!$wpdb->insert_id) {
            return 0;
        }
        $job=(int)$wpdb->insert_id;
        self::schedule('admincafe_translation_work',[$job]);
        return $job;
    }
    public static function inScope(string $scope,int $id): bool
    {
        $selected=(array)\FandooghRest\Core\Settings::get('menu_category_ids',[]);
        if ($scope==='category') {
            return !$selected||in_array($id,array_map('intval',$selected),true);
        }
        if ($scope!=='product') {
            return true;
        }
        $p=wc_get_product($id);
        if (!$p||$p->get_status()!=='publish') {
            return false;
        }
        if ($p->is_type('variation')) {
            $p=wc_get_product($p->get_parent_id());
        }
        return $p&&$p->get_status()==='publish'&&in_array($p->get_type(),['simple','variable'],true)&&$p->get_meta('_admincafe_visible',true)!=='no'&&(!$selected||array_intersect($selected,$p->get_category_ids()));
    }
    public static function lock(string $name): ?string
    {
        global $wpdb;
        $key='admincafe_translation_lock_'.hash('sha256',$name);
        $token=wp_json_encode(['owner'=>bin2hex(random_bytes(16)),'time'=>time()]);
        if (add_option($key,$token,'',false)) {
            return $token;
        }
        $old=get_option($key);
        $data=json_decode((string)$old,true);
        if (is_array($data)&&($data['time']??time())<time()-300) {
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s",$key,$old));
            wp_cache_delete($key,'options');
            if (add_option($key,$token,'',false)) {
                return $token;
            }
        }
        return null;
    }
    public static function unlock(string $name,string $token): void {
        global $wpdb;
        $key='admincafe_translation_lock_'.hash('sha256',$name);
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s",$key,$token));
        wp_cache_delete($key,'options');
    }
    public static function reserve(int $characters): bool
    {
        $lock=self::lock('usage');
        if (!$lock) {
            return false;
        }
        try {
            $usage=(array)get_option('admincafe_translation_usage',[]);
            if (($usage['date']??'')!==gmdate('Y-m-d')) {
                $usage=['date'=>gmdate('Y-m-d'),'characters'=>0];
            }
            if ($usage['characters']+$characters>Config::raw()['daily_character_limit']) {
                return false;
            }
            $usage['characters']+=$characters;
            update_option('admincafe_translation_usage',$usage,false);
            return true;
        }
        finally {
            self::unlock('usage',$lock);
        }
    }
    private static function finish(int $id,string $status,string $error=''): void {
        global $wpdb;
        $wpdb->update(self::table(),['status'=>$status,'error'=>$error,'updated_at'=>gmdate('Y-m-d H:i:s')],['id'=>$id]);
    }
    public static function work(int $id): void
    {
        if (function_exists('wc_set_time_limit')) {
            wc_set_time_limit(90);
        }
        self::install();
        $lock=self::lock('job'.$id);
        if (!$lock) {
            return;
        }
        global $wpdb;
        try {
            $job=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table().' WHERE id=%d',$id),ARRAY_A);
            if (!$job||!in_array($job['status'],['queued','running'],true)) {
                return;
            }
            $scope=$job['scope'];
            $item=(int)$job['item_id'];
            $source=Source::read($scope,$item);
            $config=Config::read();
            if (!$source||!$config['enabled']||!$config['configured']||$config['version']!==$job['config_version']||$source['hash']!==$job['source_hash']||!self::inScope($scope,$item)) {
                self::finish($id,'skipped','stale');
                return;
            }
            $attempt=(int)$job['attempts']+1;
            $wpdb->update(self::table(),['status'=>'running','attempts'=>$attempt,'updated_at'=>gmdate('Y-m-d H:i:s')],['id'=>$id]);
            $outputs=[];
            $provenance=Source::metadata($scope,$item,Source::PROVENANCE);
            $sourceHashes=Source::metadata($scope,$item,'_admincafe_translation_sources');
            $provider=apply_filters('admincafe_translation_provider',new GoogleProvider());
            if (!$provider instanceof Provider) {
                self::finish($id,'failed','provider');
                return;
            }
            foreach (Config::targets() as $lang) {
                $fields=[];
                foreach ($source['fields'] as $field=>$text) {
                    if (Source::eligible($scope,$item,$source,$lang,$field)) {
                        if ($text===''&&!empty($source['translations'][$lang][$field])) {
                            $outputs[$lang][$field]='';
                        }
                        elseif ($text!==''&&(($sourceHashes[$lang][$field]??null)!==hash('sha256',$text)||empty($source['translations'][$lang][$field]))) {
                            $fields[$field]=$text;
                        }
                    }
                }
                if (!$fields) {
                    continue;
                }
                $response=$provider->translate(array_values($fields),$lang);
                if (is_wp_error($response)) {
                    $retry=(bool)($response->get_error_data()['retryable']??false)&&$attempt<3;
                    self::finish($id,$retry?'queued':'failed',in_array($response->get_error_code(),['translation_budget','translation_credential','translation_transient','translation_response'],true)?$response->get_error_code():'provider');
                    if ($retry) {
                        self::schedule('admincafe_translation_work',[$id],60*(2**($attempt-1)));
                    }
                    return;
                }
                if (count($response)!==count($fields)||array_filter($response,static fn($v)=>!is_string($v)||mb_strlen($v)>50000)) {
                    self::finish($id,'failed','response');
                    return;
                }
                foreach (array_keys($fields) as $index=>$field) {
                    $value=sanitize_textarea_field(wp_strip_all_tags($response[$index]));
                    $limit=$scope==='settings'?($field==='restaurant_address'?1000:300):($field==='name'?250:($field==='short_description'?5000:20000));
                    if ($value===''||mb_strlen($value)>$limit) {
                        self::finish($id,'failed','length');
                        return;
                    }
                    $outputs[$lang][$field]=$value;
                }
                // Checkpoint one language per action; paid outputs survive later language failures.
                break;
            }
            $current=Source::read($scope,$item);
            if (!$current||$current['hash']!==$source['hash']||Config::read()['version']!==$job['config_version']||!Config::read()['enabled']||!self::inScope($scope,$item)) {
                self::finish($id,'skipped','stale');
                return;
            }
            $changed=false;
            $translations=$current['translations'];
            foreach ($outputs as $lang=>$fields) {
                if (!in_array($lang,Config::targets(),true)) {
                    continue;
                }
                foreach ($fields as $field=>$value) {
                    if (($current['translations'][$lang][$field]??'')!==($source['translations'][$lang][$field]??'')||!Source::eligible($scope,$item,$current,$lang,$field)) {
                        continue;
                    }
                    $translations[$lang][$field]=$value;
                    $provenance[$lang][$field]=hash('sha256',$value);
                    $sourceHashes[$lang][$field]=hash('sha256',$source['fields'][$field]);
                    $changed=true;
                }
            }
            $current['config_version']=$job['config_version'];
            if ($changed&&!Source::commit($scope,$item,$current,$translations,$provenance)) {
                self::finish($id,'skipped','concurrent');
                return;
            }
            if ($changed) {
                Source::putMetadata($scope,$item,'_admincafe_translation_sources',$sourceHashes);
            }
            $more=false;
            foreach (Config::targets() as $lang) {
                foreach ($current['fields'] as $field=>$text) {
                    if ($text!==''&&Source::eligible($scope,$item,['translations'=>$translations]+$current,$lang,$field)&&($sourceHashes[$lang][$field]??null)!==hash('sha256',$text)) {
                        $more=true;
                    }
                }
            }
            if ($more&&$changed) {
                $wpdb->update(self::table(),['attempts'=>0],['id'=>$id]);
                self::finish($id,'queued');
                self::schedule('admincafe_translation_work',[$id],1);
            }
            else {
                self::finish($id,$changed?'completed':'skipped');
            }
        }
        catch (\Throwable $error) {
            self::finish($id,'failed','internal');
        }
        finally {
            self::unlock('job'.$id,$lock);
        }
    }
    public static function scan(int $page=1,string $scope='product'): void
    {
        if (!Config::read()['enabled']) {
            return;
        }
        if ($scope==='product') {
            $query=new \WP_Query(['post_type'=>['product','product_variation'],'post_status'=>'publish','posts_per_page'=>25,'paged'=>$page,'orderby'=>'ID','order'=>'ASC','fields'=>'ids','no_found_rows'=>true]);
            foreach ($query->posts as $id) {
                self::enqueue('product',(int)$id);
            }
            if (count($query->posts)===25) {
                self::schedule('admincafe_translation_scan',[$page+1,'product'],1);
            }
            else {
                self::schedule('admincafe_translation_scan',[1,'category'],1);
            }
        }
        elseif ($scope==='category') {
            $terms=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false,'number'=>25,'offset'=>($page-1)*25,'orderby'=>'term_id','order'=>'ASC']);
            if (is_wp_error($terms)) {
                return;
            }
            foreach ($terms as $term) {
                self::enqueue('category',$term->term_id);
            }
            if (count($terms)===25) {
                self::schedule('admincafe_translation_scan',[$page+1,'category'],1);
            }
            else {
                self::enqueue('settings',0);
            }
        }
    }
    public static function run(): array {
        self::schedule('admincafe_translation_scan',[1]);
        return ['queued'=>0,'scan_queued'=>true];
    }
    public static function retry(array $ids=[]): array
    {
        self::install();
        global $wpdb;
        $where=$ids?' AND id IN ('.implode(',',array_map('intval',$ids)).')':'';
        $jobs=$wpdb->get_results('SELECT id FROM '.self::table()." WHERE status='failed' $where ORDER BY id LIMIT 100",ARRAY_A);
        $count=0;
        foreach ($jobs as $job) {
            $id=(int)$job['id'];
            $row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table().' WHERE id=%d',$id),ARRAY_A);
            $source=Source::read($row['scope'],(int)$row['item_id']);
            if (!$source) {
                continue;
            }
            if ($row['config_version']!==Config::read()['version']||$source['hash']!==$row['source_hash']) {
                self::finish($id,'skipped','stale');
                if (self::enqueue($row['scope'],(int)$row['item_id'])) {
                    $count++;
                }
                continue;
            }
            $wpdb->update(self::table(),['status'=>'queued','attempts'=>0,'error'=>''],['id'=>$id]);
            self::schedule('admincafe_translation_work',[$id]);
            $count++;
        }
        return ['queued'=>$count];
    }
    public static function status(): array
    {
        self::install();
        global $wpdb;
        $counts=array_fill_keys(['queued','running','completed','failed','skipped'],0);
        foreach ($wpdb->get_results('SELECT status,COUNT(*) AS total FROM '.self::table().' GROUP BY status',ARRAY_A) as $row) {
            $counts[$row['status']]=(int)$row['total'];
        }
        $jobs=$wpdb->get_results('SELECT id,scope,item_id,status,attempts,error,updated_at,payload FROM '.self::table().' ORDER BY id DESC LIMIT 30',ARRAY_A);
        $problems=[];
        foreach ($jobs as &$job) {
            $job['name']=json_decode($job['payload'],true)['name']??'';
            unset($job['payload']);
        }
        unset($job);
        $failed=$wpdb->get_results('SELECT id,scope,item_id,error,payload FROM '.self::table()." WHERE status='failed' ORDER BY id DESC LIMIT 30",ARRAY_A);
        foreach ($failed as $row) {
            $row['name']=json_decode($row['payload'],true)['name']??'';
            unset($row['payload']);
            $row['message']=match($row['error']) {
                'translation_budget'=>__('The daily translation character limit has been reached.', 'fandoogh-rest'),
                'translation_credential'=>__('Check the translation API credential and Google project permissions.', 'fandoogh-rest'),
                'translation_transient','timeout'=>__('Translation temporarily failed. Retry this item.', 'fandoogh-rest'),
                'length','response','translation_response'=>__('The translation response did not meet the content limits.', 'fandoogh-rest'),
                default=>__('Automatic translation could not be completed.', 'fandoogh-rest'),
            }
            ;
            $problems[]=$row;
        }
        $usage=(array)get_option('admincafe_translation_usage',[]);
        if (($usage['date']??'')!==gmdate('Y-m-d')) {
            $usage=['date'=>gmdate('Y-m-d'),'characters'=>0];
        }
        return ['counts'=>$counts,'jobs'=>$jobs,'problems'=>$problems,'usage'=>$usage+['limit'=>Config::raw()['daily_character_limit']],'settings'=>Config::read()];
    }
    public static function recover(): void
    {
        self::install();
        global $wpdb;
        $rows=$wpdb->get_results($wpdb->prepare('SELECT id,status,attempts FROM '.self::table()." WHERE status IN ('queued','running') AND updated_at < %s ORDER BY id LIMIT 50",gmdate('Y-m-d H:i:s',time()-600)),ARRAY_A);
        foreach ($rows as $row) {
            if ($row['status']==='running'&&(int)$row['attempts']>=3) {
                self::finish((int)$row['id'],'failed','timeout');
                continue;
            }
            self::finish((int)$row['id'],'queued');
            self::schedule('admincafe_translation_work',[(int)$row['id']]);
        }
    }
}
