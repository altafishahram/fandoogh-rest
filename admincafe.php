<?php
/**
 * Plugin Name: AdminCafe
 * Description: A Persian-first restaurant menu and independent operations panel powered by WooCommerce.
 * Version: 1.3.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce
 * WC requires at least: 9.0
 * WC tested up to: 11.1
 * Author: AdminCafe
 * License: GPL-2.0-or-later
 * Text Domain: admincafe
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;
define('ADMINCAFE_VERSION', '1.3.0');
define('ADMINCAFE_FILE', __FILE__);
define('ADMINCAFE_PATH', plugin_dir_path(__FILE__));
define('ADMINCAFE_URL', plugin_dir_url(__FILE__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'AdminCafe\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $file = ADMINCAFE_PATH . 'src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
if (is_file(ADMINCAFE_PATH . 'vendor/autoload.php')) {
    require_once ADMINCAFE_PATH . 'vendor/autoload.php';
}

register_activation_hook(__FILE__, [AdminCafe\Core\Installation::class, 'activate']);
register_deactivation_hook(__FILE__, [AdminCafe\Core\Installation::class, 'deactivate']);
add_action('before_woocommerce_init', static function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('admincafe', false, dirname(plugin_basename(__FILE__)) . '/languages');
    if (!class_exists('WooCommerce') || version_compare(PHP_VERSION, '8.2', '<') || version_compare(WC_VERSION, '9.0', '<')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="notice notice-error"><p>' . esc_html__('AdminCafe requires PHP 8.2+ and WooCommerce 9.0+. Please activate a compatible WooCommerce installation.', 'admincafe') . '</p></div>';
        });
        return;
    }
    AdminCafe\Core\Plugin::boot();
});
