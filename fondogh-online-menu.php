<?php
/**
 * Plugin Name: منو آنلاین فندق (Fondogh Online Menu)
 * Plugin URI:  https://fondogh-menu.ir
 * Description: افزونه جامع و هوشمند مدیریت منوی آنلاین و سفارش‌گیری رستوران و کافه بر پایه ووکامرس و المنتور (چندشعبه‌ای، چندزبانه، پنل اختصاصی فرانت‌اند PWA، حالت کاتالوگ و سفارش سر میز).
 * Version:     1.3.8
 * Author:      تیم منو آنلاین فندق
 * Author URI:  https://fondogh-menu.ir
 * Text Domain: fondogh-online-menu
 * Domain Path: /languages
 * Update URI:  https://github.com/altafishahram/fandoogh-rest
 * Requires at least: 5.8
 * Requires PHP: 8.2
 * WC requires at least: 5.0
 * WC tested up to: 11.3.1
 * Elementor tested up to: 4.4.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define Plugin Constants safely
if (!defined('RMM_VERSION')) {
    define('RMM_VERSION', '1.3.8');
}
if (!defined('RMM_PLUGIN_FILE')) {
    define('RMM_PLUGIN_FILE', __FILE__);
}
if (!defined('RMM_PLUGIN_PATH')) {
    define('RMM_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('RMM_PLUGIN_URL')) {
    define('RMM_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('RMM_PLUGIN_BASENAME')) {
    define('RMM_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

// Require Autoloader with fallback
$autoloader_file = RMM_PLUGIN_PATH . 'includes/Autoloader.php';
if (file_exists($autoloader_file)) {
    require_once $autoloader_file;
    if (class_exists('RMM\\Autoloader')) {
        \RMM\Autoloader::run();
    }
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>' .
            esc_html__('خطا در بارگذاری افزونه منو آنلاین فندق: فایل includes/Autoloader.php یافت نشد. لطفاً مطمئن شوید افزونه به طور کامل درون پوشه خود آپلود و استخراج شده است.', 'fondogh-online-menu') .
            '</p></div>';
    });
    return;
}

/**
 * Register the GitHub updater before checking runtime dependencies.
 * Updates must remain available even when WooCommerce is temporarily disabled.
 */
add_action('plugins_loaded', function () {
    if (class_exists('RMM\\Core\\GitHubUpdater')) {
        new \RMM\Core\GitHubUpdater();
    }
}, 1);

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
 * Order access in this plugin uses WooCommerce CRUD APIs (wc_get_orders(),
 * wc_get_order(), and WC_Order methods), not direct order post/meta access.
 */
add_action('before_woocommerce_init', function () {
    $features_util = '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil';
    if (class_exists($features_util) && method_exists($features_util, 'declare_compatibility')) {
        $features_util::declare_compatibility('custom_order_tables', RMM_PLUGIN_FILE, true);
    }
});

/**
 * Activation & Deactivation Hooks (Safe Wrapper)
 */
register_activation_hook(__FILE__, function () {
    try {
        if (class_exists('RMM\\Core\\Installer')) {
            \RMM\Core\Installer::activate();
        }
    } catch (\Throwable $e) {
        error_log('Fondogh Menu Activation Error: ' . $e->getMessage());
    }
});

register_deactivation_hook(__FILE__, function () {
    try {
        if (class_exists('RMM\\Core\\Installer')) {
            \RMM\Core\Installer::deactivate();
        }
    } catch (\Throwable $e) {
        error_log('Fondogh Menu Deactivation Error: ' . $e->getMessage());
    }
});

/**
 * Initialize Plugin Core on plugins_loaded (Safe Wrapper)
 */
add_action('plugins_loaded', function () {
    // Check WooCommerce dependency
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>' .
                esc_html__('افزونه «منو آنلاین فندق» برای کارکرد صحیح نیازمند فعال بودن ووکامرس (WooCommerce) است.', 'fondogh-online-menu') .
                '</p></div>';
        });
        return;
    }

    try {
        if (class_exists('RMM\\Plugin')) {
            \RMM\Plugin::instance();
        }
    } catch (\Throwable $e) {
        error_log('Fondogh Menu Launch Error: ' . $e->getMessage());
        add_action('admin_notices', function () use ($e) {
            echo '<div class="notice notice-error"><p>' .
                sprintf(esc_html__('خطا در اجرای افزونه منو آنلاین فندق: %s', 'fondogh-online-menu'), esc_html($e->getMessage())) .
                '</p></div>';
        });
    }
});
