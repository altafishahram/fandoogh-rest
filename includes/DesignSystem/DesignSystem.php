<?php
namespace RMM\DesignSystem;

if (!defined('ABSPATH')) {
    exit;
}

class DesignSystem {

    public function __construct() {
        $this->init();
    }

    private function init() {
        SettingsPage::init();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_design_system_styles'], 20);
        add_action('admin_init', [$this, 'maybe_recompile_styles']);
    }

    /**
     * Recompile the generated stylesheet once after a plugin update so new
     * design tokens (e.g. the Category Sheet tokens) reach existing sites
     * without the admin having to press "Save" in the studio.
     */
    public function maybe_recompile_styles() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $stored = get_option('rmm_ds_compiled_version', '');
        if ($stored === RMM_VERSION) {
            return;
        }

        $compiled = CssCompiler::compile_and_save(SettingsPage::get_settings());
        if ($compiled) {
            update_option('rmm_ds_compiled_version', RMM_VERSION, false);
        }
    }

    public function enqueue_design_system_styles() {
        $compiled = CssCompiler::get_compiled_css_info();

        if ($compiled && !empty($compiled['file_url'])) {
            wp_enqueue_style(
                'rmm-design-system',
                $compiled['file_url'],
                ['rmm-frontend-menu'],
                $compiled['version']
            );
        } else {
            $settings = SettingsPage::get_settings();
            $css = CssCompiler::compile($settings);
            $min_css = CssCompiler::minify($css);
            wp_add_inline_style('rmm-frontend-menu-themes', $min_css);
        }
    }
}
