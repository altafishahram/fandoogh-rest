<?php
namespace RMM\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Installer {

    public static function activate() {
        try {
            self::create_tables();
        } catch (\Throwable $e) {
            error_log('RMM Activation Table Error: ' . $e->getMessage());
        }

        try {
            self::register_roles();
        } catch (\Throwable $e) {
            error_log('RMM Activation Role Error: ' . $e->getMessage());
        }

        try {
            self::migrate_role_capabilities();
        } catch (\Throwable $e) {
            error_log('RMM Activation Role Migration Error: ' . $e->getMessage());
        }

        try {
            self::init_default_settings();
        } catch (\Throwable $e) {
            error_log('RMM Activation Settings Error: ' . $e->getMessage());
        }

        try {
            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules(false);
            }
        } catch (\Throwable $e) {
            error_log('RMM Activation Rewrite Error: ' . $e->getMessage());
        }
    }

    public static function deactivate() {
        try {
            if (function_exists('flush_rewrite_rules')) {
                flush_rewrite_rules(false);
            }
        } catch (\Throwable $e) {
            // Silence
        }
    }

    /**
     * Create custom database tables using dbDelta
     */
    public static function create_tables() {
        global $wpdb;
        if (!isset($wpdb) || !$wpdb) {
            return;
        }

        try {
            $table_name = $wpdb->prefix . 'rmm_branch_inventory';
            $charset_collate = $wpdb->get_charset_collate();

            // Note: Two spaces between PRIMARY KEY and definition as required by dbDelta
            $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                product_id bigint(20) NOT NULL,
                branch_id bigint(20) NOT NULL,
                regular_price decimal(10,2) DEFAULT NULL,
                sale_price decimal(10,2) DEFAULT NULL,
                stock_status varchar(20) NOT NULL DEFAULT 'instock',
                is_available tinyint(1) NOT NULL DEFAULT 1,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY product_branch (product_id, branch_id),
                KEY idx_branch (branch_id),
                KEY idx_product (product_id)
            ) {$charset_collate};";

            if (!function_exists('dbDelta')) {
                if (defined('ABSPATH') && file_exists(ABSPATH . 'wp-admin/includes/upgrade.php')) {
                    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                }
            }

            if (function_exists('dbDelta')) {
                dbDelta($sql);
            } else {
                $wpdb->query($sql);
            }

            update_option('rmm_db_version', '1.0.0');
        } catch (\Throwable $e) {
            error_log('RMM Table creation error: ' . $e->getMessage());
        }
    }

    /**
     * Register specialized roles for restaurant operations
     */
    public static function register_roles() {
        try {
            // Restaurant Manager
            add_role(
                'restaurant_manager',
                __('مدیر رستوران / شعبه', 'fondogh-online-menu'),
                [
                    'read'                      => true,
                    'edit_posts'                => true,
                    'upload_files'              => true,
                    'manage_restaurant_menu'    => true,
                    // A branch manager must never be a global WooCommerce manager.
                    'view_woocommerce_reports'  => true,
                    'edit_products'             => true,
                    'publish_products'          => true,
                    'read_private_products'     => true,
                ]
            );

            // Kitchen Staff (Minimal role for kitchen display)
            add_role(
                'kitchen_staff',
                __('پرسنل آشپزخانه', 'fondogh-online-menu'),
                [
                    'read'                      => true,
                    'view_kitchen_orders'       => true,
                    'manage_restaurant_menu'    => false,
                ]
            );

            // Administrator capabilities
            $admin = get_role('administrator');
            if ($admin && is_object($admin) && method_exists($admin, 'add_cap')) {
                $admin->add_cap('manage_restaurant_menu');
                $admin->add_cap('view_kitchen_orders');
            }

            // Shop Manager capabilities
            $shop_manager = get_role('shop_manager');
            if ($shop_manager && is_object($shop_manager) && method_exists($shop_manager, 'add_cap')) {
                $shop_manager->add_cap('manage_restaurant_menu');
                $shop_manager->add_cap('view_kitchen_orders');
            }
        } catch (\Throwable $e) {
            error_log('RMM Register roles error: ' . $e->getMessage());
        }
    }

    /**
     * Remove the legacy global WooCommerce capability from existing branch
     * managers. add_role() does not update roles that already exist, so this
     * runs on every activation for installations upgraded from older versions.
     */
    public static function migrate_role_capabilities() {
        $manager = get_role('restaurant_manager');
        if ($manager && is_object($manager) && method_exists($manager, 'remove_cap')) {
            $manager->remove_cap('manage_woocommerce');
        }
    }

    /**
     * Set default options
     */
    public static function init_default_settings() {
        try {
            if (!get_option('rmm_settings')) {
                update_option('rmm_settings', [
                    'operation_mode'          => 'online_delivery', // 'view_only', 'dine_in', 'online_delivery'
                    'catalog_hide_price'      => 'no',
                    'catalog_action_btn'      => 'whatsapp', // 'none', 'whatsapp', 'call'
                    'whatsapp_number'         => '',
                    'phone_number'            => '',
                    'enable_branches'         => 'yes',
                    'dashboard_slug'          => 'restaurant-portal',
                    'default_language'        => 'fa',
                    'enable_multi_lang'       => 'yes',
                    'enabled_languages'       => ['fa', 'en', 'ar', 'tr'],
                    'enable_lang_entrance_popup' => 'yes',
                    'ai_translate_provider'   => 'google_free',
                    'gemini_api_key'          => '',
                    'enable_sound_alert'      => 'yes',
                    'enable_happy_hour'       => 'no',
                    'remove_data_on_uninstall'=> 'no',
                ]);
            }
        } catch (\Throwable $e) {
            error_log('RMM Settings init error: ' . $e->getMessage());
        }
    }
}
