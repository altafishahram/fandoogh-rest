<?php
/**
 * Uninstall Fondogh Online Menu
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clean up options or custom tables if configured by admin
$settings = get_option('rmm_settings', []);
$remove_data = isset($settings['remove_data_on_uninstall']) && $settings['remove_data_on_uninstall'] === 'yes';

if ($remove_data) {
    global $wpdb;

    // 1. Drop custom inventory table
    $table_inventory = $wpdb->prefix . 'rmm_branch_inventory';
    $wpdb->query("DROP TABLE IF EXISTS {$table_inventory}");

    // 2. Delete plugin options and transients
    delete_option('rmm_settings');
    delete_option('rmm_db_version');
    delete_option('rmm_category_order');
    delete_option('rmm_design_system');
    delete_option('rmm_ds_compiled_version');
    delete_option('rmm_modal_global_ver');
    delete_option('rmm_happy_hour_rules');
    delete_transient('rmm_db_upgrade_failed');

    // 3-4. Delete all plugin post/term meta. esc_like() escapes the "_"
    // wildcards so unrelated meta keys are never removed by accident.
    $rmm_meta_like = $wpdb->esc_like('_rmm_') . '%';
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $rmm_meta_like));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->termmeta} WHERE meta_key LIKE %s", $rmm_meta_like));

    // 5. Remove plugin-specific custom roles
    remove_role('restaurant_manager');
    remove_role('kitchen_staff');

    // 6. Remove custom capabilities from remaining roles
    $roles_to_clean = ['administrator', 'shop_manager'];
    $caps_to_remove = ['manage_restaurant_menu', 'view_kitchen_orders'];
    foreach ($roles_to_clean as $role_slug) {
        $role = get_role($role_slug);
        if ($role) {
            foreach ($caps_to_remove as $cap) {
                $role->remove_cap($cap);
            }
        }
    }

    // 7. Remove compiled design system static assets
    $upload_dir = wp_upload_dir();
    if (empty($upload_dir['error'])) {
        $ds_dir = trailingslashit($upload_dir['basedir']) . 'fondogh-menu';
        if (is_dir($ds_dir)) {
            @unlink(trailingslashit($ds_dir) . 'fondogh-style.css');
            @rmdir($ds_dir);
        }
    }
}
