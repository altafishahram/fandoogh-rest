<?php
/**
 * Uninstall TastyMenu Pro
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up options or custom tables if configured by admin
$settings = get_option('rmm_settings', []);
$remove_data = isset($settings['remove_data_on_uninstall']) && $settings['remove_data_on_uninstall'] === 'yes';

if ($remove_data) {
    global $wpdb;

    // Drop custom inventory table
    $table_inventory = $wpdb->prefix . 'rmm_branch_inventory';
    $wpdb->query("DROP TABLE IF EXISTS {$table_inventory}");

    // Delete options
    delete_option('rmm_settings');
    delete_option('rmm_db_version');

    // Remove capabilities
    $roles = ['administrator', 'restaurant_manager', 'kitchen_staff'];
    foreach ($roles as $role_slug) {
        $role = get_role($role_slug);
        if ($role) {
            $role->remove_cap('manage_restaurant_menu');
            $role->remove_cap('view_kitchen_orders');
        }
    }
}
