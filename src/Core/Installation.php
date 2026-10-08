<?php
namespace AdminCafe\Core;

defined('ABSPATH') || exit;

final class Installation
{
    public static function activate(): void
    {
        if (!class_exists('WooCommerce') || version_compare(PHP_VERSION, '8.2', '<')) {
            wp_die(esc_html__('AdminCafe requires WooCommerce and PHP 8.2 or newer.', 'admincafe'));
        }
        self::upgrade();
        Routes::rewrites();
        flush_rewrite_rules(false);
    }

    public static function upgrade(): void
    {
        if (get_option('admincafe_db_version') === ADMINCAFE_VERSION) {
            return;
        }
        Access::install();
        add_option('admincafe_settings', Settings::defaults(), '', false);
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$wpdb->prefix}admincafe_events (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_key varchar(191) NOT NULL,
            order_id bigint(20) unsigned NOT NULL,
            channel varchar(32) NOT NULL,
            title varchar(255) NOT NULL,
            body text NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY event_key (event_key),
            KEY created_at (created_at)
        ) $charset;");
        dbDelta("CREATE TABLE {$wpdb->prefix}admincafe_event_reads (
            event_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (event_id,user_id),
            KEY user_id (user_id)
        ) $charset;");
        update_option('admincafe_db_version', ADMINCAFE_VERSION, false);
        if (!wp_next_scheduled('admincafe_cleanup')) {
            wp_schedule_event(time() + DAY_IN_SECONDS, 'daily', 'admincafe_cleanup');
        }
    }

    public static function deactivate(): void
    {
        \AdminCafe\Translation\Module::deactivate();
        wp_clear_scheduled_hook('admincafe_cleanup');
        wp_clear_scheduled_hook('admincafe_send_push');
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('admincafe_send_push', [], 'admincafe');
        }
        flush_rewrite_rules(false);
    }
}
