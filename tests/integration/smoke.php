<?php
/**
 * Run with: wp eval-file tests/integration/smoke.php
 */

$required_classes = [
    'WooCommerce',
    '\\Elementor\\Widget_Base',
    '\\RMM\\Plugin',
    '\\RMM\\Elementor\\ElementorIntegration',
];

foreach ($required_classes as $class_name) {
    if (!class_exists($class_name)) {
        WP_CLI::error('Required class is not loaded: ' . $class_name);
    }
}

if (!did_action('elementor/loaded')) {
    WP_CLI::error('Elementor did not finish loading.');
}

$plugin = \\RMM\\Plugin::instance();
if (!$plugin) {
    WP_CLI::error('Fondogh plugin instance could not be initialized.');
}

$widgets_manager = \\Elementor\\Plugin::instance()->widgets_manager;
$widgets = $widgets_manager->get_widget_types();
if (!isset($widgets['rmm_food_menu_grid'])) {
    WP_CLI::error('The Food Menu Elementor widget was not registered.');
}

global $wpdb;
$table_name = $wpdb->prefix . 'rmm_branch_inventory';
$found_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name)));
if ($found_table !== $table_name) {
    WP_CLI::error('Branch inventory table was not created during plugin activation.');
}

WP_CLI::log(wp_json_encode([
    'status' => 'ok',
    'wordpress' => get_bloginfo('version'),
    'woocommerce' => defined('WC_VERSION') ? WC_VERSION : null,
    'elementor' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : null,
    'registered_food_menu_widget' => true,
    'branch_inventory_table' => $table_name,
], JSON_UNESCAPED_SLASHES));
