<?php
/** Preserve restaurant products, orders and configuration on uninstall by default. */
defined('WP_UNINSTALL_PLUGIN') || exit;

// Explicit opt-in for plugin-specific data only. WooCommerce business records are never removed.
$removeData = defined('FANDOOGH_REST_REMOVE_DATA') ? FANDOOGH_REST_REMOVE_DATA : (defined('ADMINCAFE_REMOVE_DATA') ? ADMINCAFE_REMOVE_DATA : false);
if ($removeData !== true) {
    return;
}
global $wpdb;
foreach (['admincafe_events', 'admincafe_event_reads', 'admincafe_translation_jobs'] as $table) {
    $wpdb->query('DROP TABLE IF EXISTS `' . esc_sql($wpdb->prefix . $table) . '`');
}
foreach (['fandoogh_branches', 'fandoogh_branches_version', 'admincafe_settings', 'admincafe_tables', 'admincafe_db_version', 'admincafe_vapid', 'admincafe_translation_config', 'admincafe_translation_schema', 'admincafe_translation_usage', 'admincafe_translation_provenance', 'admincafe_translation_manual', 'admincafe_translation_sources'] as $option) {
    delete_option($option);
}
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('admincafe_translation_lock_') . '%'));
foreach (['manual', 'provenance', 'sources'] as $metadata) {
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('admincafe_translation_' . $metadata . '_branch_') . '%'));
}
foreach (get_users(['fields' => 'ID']) as $userId) {
    delete_user_meta($userId, '_admincafe_push_devices');
    delete_user_meta($userId, '_fandoogh_branch_ids');
}
foreach (['admincafe_manager', 'admincafe_staff', 'admincafe_kitchen', 'admincafe_cashier'] as $role) {
    remove_role($role);
}
$admin = get_role('administrator');
if ($admin) {
    foreach (['admincafe_manage_menu', 'admincafe_manage_orders', 'admincafe_manage_settings', 'admincafe_manage_tables', 'admincafe_view_reports', 'admincafe_manage_staff', 'admincafe_receive_notifications'] as $capability) {
        $admin->remove_cap($capability);
    }
}
