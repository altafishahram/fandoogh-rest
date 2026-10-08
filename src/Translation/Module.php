<?php
namespace AdminCafe\Translation;
defined('ABSPATH') || exit;
final class Module
{
    public function register(): void
    {
        add_action('init',[Queue::class,'install'],0);
        add_action('init',static function (): void {
            if (!wp_next_scheduled('admincafe_translation_recover')) {
                wp_schedule_event(time()+600,'hourly','admincafe_translation_recover');
            }
        }
        );
        add_action('admincafe_translation_recover',[Queue::class,'recover']);
        add_action('admincafe_translation_work',[Queue::class,'work']);
        add_action('admincafe_translation_scan',[Queue::class,'scan'],10,2);
        add_action('admincafe_translation_manual_input',static function ($scope,$id,$patch): void {
            self::safe(static fn()=>Source::manual($scope,(int)$id,$patch));
        }
        ,10,3);
        $product=static function ($p): void {
            self::safe(static fn()=>Queue::enqueue('product',$p->get_id()));
        }
        ;
        add_action('woocommerce_after_product_object_save',$product);
        add_action('woocommerce_after_product_variation_object_save',$product);
        foreach (['created_product_cat','edited_product_cat'] as $hook) {
            add_action($hook,static function ($id): void {
                self::safe(static fn()=>Queue::enqueue('category',(int)$id));
            }
            );
        }
        add_action('updated_option',static function ($name): void {
            if ($name==='admincafe_settings') {
                self::safe(static fn()=>Queue::enqueue('settings',0));
            }
        }
        );
        foreach (['post','term'] as $type) {
            add_filter('update_'.$type.'_metadata',static function ($check,$id,$key,$value) use ($type) {
                if ($key==='_admincafe_translations'&&!Source::$applying&&is_array($value)) {
                    $scope=$type==='term'?'category':'product'; $old=(array)get_metadata($type,$id,$key,true); $patch=[];
                    foreach (array_unique(array_merge(array_keys($old),array_keys($value))) as $lang) {
                        foreach (array_unique(array_merge(array_keys((array)($old[$lang]??[])),array_keys((array)($value[$lang]??[])))) as $field) {
                            if (($old[$lang][$field]??'')!==($value[$lang][$field]??'')) {
                                $patch[$lang][$field]=$value[$lang][$field]??'';
                            }
                        }
                    }
                    self::safe(static fn()=>Source::manual($scope,(int)$id,$patch));
                }
                return $check;
            }
            ,10,4);
        }
        add_action('rest_api_init',[$this,'routes']);
    }
    public static function safe(callable $action): void {
        if (Source::$applying) {
            return;
        }
        try {
            $action();
        }
        catch (\Throwable $error) {
            /* Saving menu content must remain available when translation storage fails. */
        }
    }
    public static function permission($request): bool|\WP_Error
    {
        if (!current_user_can('admincafe_manage_settings')||!wp_verify_nonce((string)$request->get_header('X-WP-Nonce'),'wp_rest')) {
            return new \WP_Error('admincafe_translation_forbidden',__('Translation management is not permitted.', 'admincafe'),['status'=>403]);
        }
        return true;
    }
    public function routes(): void
    {
        $permission=[self::class,'permission'];
        register_rest_route('admincafe/v1','/manage/translation/settings',[
        ['methods'=>'GET','permission_callback'=>$permission,'callback'=>static fn()=>Config::read()],
        ['methods'=>'POST','permission_callback'=>$permission,'callback'=>static function ($r) {
            $input=$r->get_json_params(); return is_array($input)?Config::update($input):new \WP_Error('admincafe_translation_input',__('Invalid translation settings.', 'admincafe'),['status'=>400]);
        }
        ],
        ]);
        register_rest_route('admincafe/v1','/manage/translation/status',['methods'=>'GET','permission_callback'=>$permission,'callback'=>static fn()=>Queue::status()]);
        register_rest_route('admincafe/v1','/manage/translation/run',['methods'=>'POST','permission_callback'=>$permission,'callback'=>static fn()=>Queue::run()]);
        register_rest_route('admincafe/v1','/manage/translation/retry',['methods'=>'POST','permission_callback'=>$permission,'callback'=>static function ($r) {
            $ids=$r->get_param('ids')??[]; if (!is_array($ids)||count($ids)>100||array_filter($ids,static fn($id)=>!is_int($id)||$id<1)) {
                return new \WP_Error('admincafe_translation_input',__('Invalid translation settings.', 'admincafe'),['status'=>400]);
            }
            return Queue::retry($ids);
        }
        ]);
    }
    public static function deactivate(): void
    {
        foreach (['admincafe_translation_work','admincafe_translation_scan','admincafe_translation_recover'] as $hook) {
            wp_unschedule_hook($hook);
            if (function_exists('as_unschedule_all_actions')) {
                as_unschedule_all_actions($hook,[],'admincafe-translation');
            }
        }
    }
}
