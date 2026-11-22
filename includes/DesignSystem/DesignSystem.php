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
            wp_add_inline_style('rmm-frontend-menu', $min_css);
        }
    }
}
