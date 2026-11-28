<?php
namespace RMM\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Assets {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_assets']);
        add_action('wp_enqueue_scripts', [$this, 'optimize_view_only_mode'], 99);
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'add_floating_cart_fragments']);
    }

    public function register_frontend_assets() {
        // Frontend styles are split into ordered, cacheable layers.
        wp_register_style(
            'rmm-frontend-menu-base',
            RMM_PLUGIN_URL . 'assets/css/frontend-menu-base.css',
            [],
            RMM_VERSION
        );

        wp_register_style(
            'rmm-frontend-menu-widgets',
            RMM_PLUGIN_URL . 'assets/css/frontend-menu-widgets.css',
            ['rmm-frontend-menu-base'],
            RMM_VERSION
        );

        // Preserve the public handle and make it the final layer in the chain.
        wp_register_style(
            'rmm-frontend-menu',
            RMM_PLUGIN_URL . 'assets/css/frontend-menu-themes.css',
            ['rmm-frontend-menu-widgets'],
            RMM_VERSION
        );

        // Register core frontend scripts
        wp_register_script(
            'rmm-frontend-menu',
            RMM_PLUGIN_URL . 'assets/js/frontend-menu.js',
            ['jquery'],
            RMM_VERSION,
            true
        );

        // Category Sheet widget (independent from the classic Category Bar)
        wp_register_style(
            'rmm-category-sheet',
            RMM_PLUGIN_URL . 'assets/css/category-sheet.css',
            ['rmm-frontend-menu'],
            RMM_VERSION
        );

        wp_register_script(
            'rmm-category-sheet',
            RMM_PLUGIN_URL . 'assets/js/category-sheet.js',
            ['jquery', 'rmm-frontend-menu'],
            RMM_VERSION,
            true
        );

        // Get current active branch and mode
        $active_branch_id = \RMM\Branches\BranchContext::get_active_branch_id();
        $branch_mode = \RMM\Branches\BranchContext::get_branch_mode($active_branch_id);
        $curr_lang = class_exists('RMM\\I18n\\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa';
        $ui_strings = class_exists('RMM\\I18n\\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_all_ui_strings($curr_lang) : [];
        $ds_settings = class_exists('RMM\\DesignSystem\\SettingsPage') ? \RMM\DesignSystem\SettingsPage::get_settings() : [];
        $default_theme = $ds_settings['default_theme'] ?? 'dark';

        wp_localize_script('rmm-frontend-menu', 'rmmConfig', [
            'ajax_url'        => admin_url('admin-ajax.php'),
            'wc_ajax_url'     => class_exists('WC_AJAX') ? \WC_AJAX::get_endpoint('%%endpoint%%') : '',
            'nonce'           => wp_create_nonce('rmm_frontend_nonce'),
            'rest_url'        => esc_url_raw(rest_url('rmm/v1/')),
            'rest_nonce'      => wp_create_nonce('wp_rest'),
            'active_branch'   => $active_branch_id,
            'active_lang'     => $curr_lang,
            'default_theme'   => $default_theme,
            'operation_mode'     => $branch_mode,
            'is_view_only'       => ($branch_mode === 'view_only'),
            'catalog_hide_price' => (\RMM\Core\Settings::get('catalog_hide_price', 'no') === 'yes'),
            'whatsapp_number'    => \RMM\Core\Settings::get('whatsapp_number', ''),
            'currency_symbol'    => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'تومان',
            'strings'         => $ui_strings
        ]);
    }

    /**
     * Refresh floating mini-cart count and subtotal in WooCommerce AJAX fragments.
     *
     * @param array $fragments
     * @return array
     */
    public function add_floating_cart_fragments($fragments) {
        if (!function_exists('WC') || !WC()->cart) {
            return $fragments;
        }

        $count = (int) WC()->cart->get_cart_contents_count();
        $total = WC()->cart->get_cart_subtotal();

        $fragments['#rmm-cart-count'] = '<span class="rmm-cart-badge-count" id="rmm-cart-count" data-count="' . esc_attr($count) . '">' . esc_html($count) . '</span>';
        $fragments['#rmm-cart-total'] = '<span class="rmm-cart-total" id="rmm-cart-total">' . wp_kses_post($total) . '</span>';

        return $fragments;
    }

    /**
     * Cache-friendly performance optimization:
     * In view_only mode, remove WooCommerce checkout & cart scripts
     */
    public function optimize_view_only_mode() {
        $active_branch_id = \RMM\Branches\BranchContext::get_active_branch_id();
        $is_view_only = \RMM\Branches\BranchContext::get_branch_mode($active_branch_id) === 'view_only';

        if ($is_view_only && !is_admin()) {
            // Dequeue cart fragments which causes excessive AJAX requests
            wp_dequeue_script('wc-cart-fragments');
            wp_dequeue_script('wc-add-to-cart');
            
            // Optionally dequeue cart styles if checkout is disabled
            if (!is_cart() && !is_checkout()) {
                wp_dequeue_style('woocommerce-layout');
            }
        }
    }
}
