<?php
/**
 * Plugin Name: رستوران فندوق
 * Plugin URI: https://github.com/altafishahram/fandoogh-rest
 * Description: A Persian-first restaurant menu and independent operations panel powered by WooCommerce.
 * Version: 1.4.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce
 * WC requires at least: 9.0
 * WC tested up to: 11.2
 * Author: Fandoogh Rest
 * License: GPL-2.0-or-later
 * Text Domain: fandoogh-rest
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;
if (defined('FANDOOGH_REST_VERSION')) { return; }

// Refuse to run alongside a separate legacy copy, regardless of plugin load order.
$fandooghActivePlugins = array_merge((array) get_option('active_plugins', []), array_keys((array) get_site_option('active_sitewide_plugins', [])));
$fandooghLegacyConflict = defined('ADMINCAFE_VERSION');
foreach ($fandooghActivePlugins as $fandooghActivePlugin) {
    if (basename($fandooghActivePlugin) === 'admincafe.php' && dirname($fandooghActivePlugin) !== dirname(plugin_basename(__FILE__))) {
        $fandooghLegacyConflict = true;
    }
}
if ($fandooghLegacyConflict) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>' . esc_html__('Deactivate the old AdminCafe plugin before activating Fandoogh Rest.', 'fandoogh-rest') . '</p></div>';
    });
    return;
}
unset($fandooghActivePlugins, $fandooghActivePlugin, $fandooghLegacyConflict);

define('FANDOOGH_REST_VERSION', '1.4.0');
define('FANDOOGH_REST_FILE', defined('FANDOOGH_REST_LEGACY_ENTRY') ? FANDOOGH_REST_LEGACY_ENTRY : __FILE__);
define('FANDOOGH_REST_PATH', plugin_dir_path(__FILE__));
define('FANDOOGH_REST_URL', plugin_dir_url(__FILE__));

// Existing integrations and wp-config credentials keep working; new names take precedence.
foreach (['VERSION', 'FILE', 'PATH', 'URL', 'GOOGLE_TRANSLATE_API_KEY', 'REMOVE_DATA'] as $fandooghConstant) {
    $fandooghNew = 'FANDOOGH_REST_' . $fandooghConstant;
    $fandooghOld = 'ADMINCAFE_' . $fandooghConstant;
    if (!defined($fandooghNew) && defined($fandooghOld)) { define($fandooghNew, constant($fandooghOld)); }
    if (!defined($fandooghOld) && defined($fandooghNew)) { define($fandooghOld, constant($fandooghNew)); }
}
unset($fandooghConstant, $fandooghNew, $fandooghOld);

spl_autoload_register(static function (string $class): void {
    $legacyPrefix = 'AdminCafe\\';
    if (str_starts_with($class, $legacyPrefix)) {
        $replacement = 'FandooghRest\\' . substr($class, strlen($legacyPrefix));
        if (class_exists($replacement) || interface_exists($replacement) || trait_exists($replacement)) {
            class_alias($replacement, $class);
        }
        return;
    }
    $prefix = 'FandooghRest\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $file = FANDOOGH_REST_PATH . 'src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
if (is_file(FANDOOGH_REST_PATH . 'vendor/autoload.php')) {
    require_once FANDOOGH_REST_PATH . 'vendor/autoload.php';
}

register_activation_hook(__FILE__, [FandooghRest\Core\Installation::class, 'activate']);
register_deactivation_hook(__FILE__, [FandooghRest\Core\Installation::class, 'deactivate']);
add_action('before_woocommerce_init', static function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', FANDOOGH_REST_FILE, true);
    }
});
add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('fandoogh-rest', false, dirname(plugin_basename(__FILE__)) . '/languages');
    if (!class_exists('WooCommerce') || version_compare(PHP_VERSION, '8.2', '<') || version_compare(WC_VERSION, '9.0', '<')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="notice notice-error"><p>' . esc_html__('Fandoogh Rest requires PHP 8.2+ and WooCommerce 9.0+. Please activate a compatible WooCommerce installation.', 'fandoogh-rest') . '</p></div>';
        });
        return;
    }
    FandooghRest\Core\Plugin::boot();
});
