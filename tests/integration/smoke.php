<?php
/**
 * Run with: wp eval-file tests/integration/smoke.php
 */

$required_classes = [
    'WooCommerce',
    '\\Elementor\\Widget_Base',
    '\\RMM\\Plugin',
    '\\RMM\\Elementor\\ElementorIntegration',
    '\\RMM\\Core\\GitHubUpdater',
];

foreach ($required_classes as $class_name) {
    if (!class_exists($class_name)) {
        WP_CLI::error('Required class is not loaded: ' . $class_name);
    }
}

if (false === has_filter('update_plugins_github.com') || false === has_filter('plugins_api')) {
    WP_CLI::error('GitHub updater hooks were not registered.');
}

if (!did_action('elementor/loaded')) {
    WP_CLI::error('Elementor did not finish loading.');
}

$plugin = \RMM\Plugin::instance();
if (!$plugin) {
    WP_CLI::error('Fondogh plugin instance could not be initialized.');
}

$widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
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


$product = new \WC_Product_Simple();
$product->set_name('CI Menu Smoke Test Product');
$product->set_status('publish');
$product->set_regular_price('125');
$product->save();

$page_id = wp_insert_post([
    'post_type' => 'page',
    'post_status' => 'publish',
    'post_title' => 'Fondogh Multi Widget Smoke Test',
]);
if (is_wp_error($page_id) || !$page_id) {
    WP_CLI::error('Could not create the Elementor smoke-test page.');
}

$elementor_elements = [
    [
        'id' => 'a1b2c3d',
        'elType' => 'widget',
        'widgetType' => 'rmm_food_menu_grid',
        'settings' => [
            'show_search' => 'yes',
            'show_theme_toggle' => 'yes',
        ],
        'elements' => [],
    ],
    [
        'id' => 'e4f5g6h',
        'elType' => 'widget',
        'widgetType' => 'rmm_food_menu_grid',
        'settings' => [
            'show_search' => 'yes',
            'show_theme_toggle' => 'yes',
        ],
        'elements' => [],
    ],
];

update_post_meta($page_id, '_elementor_edit_mode', 'builder');
update_post_meta($page_id, '_elementor_version', ELEMENTOR_VERSION);
update_post_meta($page_id, '_elementor_data', wp_slash(wp_json_encode($elementor_elements)));

$rendered_page = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($page_id);
if (substr_count($rendered_page, 'rmm-menu-widget-wrapper') !== 2) {
    WP_CLI::error('Expected two independently rendered Food Menu widget instances on one page.');
}
if (substr_count($rendered_page, 'class="rmm-theme-toggle-btn"') !== 2) {
    WP_CLI::error('The two menu widgets did not render their own theme toggle controls.');
}
if (strpos($rendered_page, 'id="rmm-modifiers-modal"') !== false ||
    strpos($rendered_page, 'id="theme-toggle-btn"') !== false) {
    WP_CLI::error('Duplicate global IDs were emitted by the menu widget instances.');
}

WP_CLI::log(wp_json_encode([
    'status' => 'ok',
    'wordpress' => get_bloginfo('version'),
    'woocommerce' => defined('WC_VERSION') ? WC_VERSION : null,
    'elementor' => defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : null,
    'registered_food_menu_widget' => true,
    'multiple_widget_instances' => 2,
    'branch_inventory_table' => $table_name,
], JSON_UNESCAPED_SLASHES));
