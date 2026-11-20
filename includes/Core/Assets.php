<?php
namespace RMM\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Assets {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_assets']);
        add_action('wp_enqueue_scripts', [$this, 'optimize_view_only_mode'], 99);
    }

    public function register_frontend_assets() {
        // Register core frontend styles
        wp_register_style(
            'rmm-frontend-menu',
            RMM_PLUGIN_URL . 'assets/css/frontend-menu.css',
            [],
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

        // Get current active branch and mode
        $active_branch_id = \RMM\Branches\BranchContext::get_active_branch_id();
        $branch_mode = \RMM\Branches\BranchContext::get_branch_mode($active_branch_id);
        $curr_lang = class_exists('RMM\\I18n\\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa';
        $ui_strings = class_exists('RMM\\I18n\\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_all_ui_strings($curr_lang) : [];

        wp_localize_script('rmm-frontend-menu', 'rmmConfig', [
            'ajax_url'        => admin_url('admin-ajax.php'),
            'nonce'           => wp_create_nonce('rmm_frontend_nonce'),
            'rest_url'        => esc_url_raw(rest_url('rmm/v1/')),
            'rest_nonce'      => wp_create_nonce('wp_rest'),
            'active_branch'   => $active_branch_id,
            'active_lang'     => $curr_lang,
            'operation_mode'  => $branch_mode,
            'is_view_only'    => ($branch_mode === 'view_only'),
            'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'تومان',
            'strings'         => $ui_strings
        ]);
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
