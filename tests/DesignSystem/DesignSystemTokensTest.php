<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/includes/DesignSystem/TokenSchema.php';
require_once dirname(__DIR__, 2) . '/includes/DesignSystem/CssCompiler.php';

class DesignSystemTokensTest extends TestCase {

    public function test_light_and_dark_modes_share_the_same_token_schema() {
        $light = RMM\DesignSystem\TokenSchema::get_default_tokens('light');
        $dark  = RMM\DesignSystem\TokenSchema::get_default_tokens('dark');

        $light_keys = array_keys($light);
        $dark_keys  = array_keys($dark);

        sort($light_keys);
        sort($dark_keys);

        $this->assertSame($light_keys, $dark_keys);
    }

    public function test_component_mapping_only_references_defined_design_tokens() {
        $tokens = RMM\DesignSystem\TokenSchema::get_default_tokens('light');
        $css    = RMM\DesignSystem\CssCompiler::get_component_mapping_css();

        preg_match_all('/--rmm-ds-([a-z0-9-]+)/', $css, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $token_name) {
            $key = str_replace('-', '_', $token_name);
            $this->assertArrayHasKey(
                $key,
                $tokens,
                sprintf('CSS references undefined design token: --rmm-ds-%s', $token_name)
            );
        }
    }

    public function test_category_navigation_mapping_covers_default_active_and_visual_variants() {
        $css = RMM\DesignSystem\CssCompiler::get_component_mapping_css();

        $expected = [
            '.rmm-category-nav-wrapper',
            '.rmm-category-nav-wrapper .rmm-cat-pill',
            '.rmm-category-nav-wrapper .rmm-cat-item.active',
            '.rmm-cat-style--underline .rmm-cat-pill.active',
            '.rmm-cat-style--button .rmm-cat-item.active',
            'var(--rmm-ds-cat-gap)',
            'var(--rmm-ds-cat-idle-bg)',
            'var(--rmm-ds-cat-idle-text)',
            'var(--rmm-ds-cat-active-bg)',
            'var(--rmm-ds-cat-active-text)',
            'var(--rmm-ds-cat-icon)',
            'var(--rmm-ds-radius-btn)',
        ];

        foreach ($expected as $value) {
            $this->assertStringContainsString(
                $value,
                $css,
                sprintf('Category navigation mapping is missing: %s', $value)
            );
        }
    }

    public function test_category_navigation_tokens_are_compiled_for_light_and_dark_themes() {
        $settings = RMM\DesignSystem\TokenSchema::get_default_settings();
        $css      = RMM\DesignSystem\CssCompiler::compile($settings);

        foreach (['cat_gap', 'cat_idle_bg', 'cat_idle_text', 'cat_active_bg', 'cat_active_text', 'cat_icon'] as $token) {
            $css_variable = '--rmm-ds-' . str_replace('_', '-', $token) . ':';

            $this->assertStringContainsString($css_variable, $css);
        }

        $this->assertStringContainsString('[data-theme="light"]', $css);
        $this->assertStringContainsString('[data-theme="dark"]', $css);
    }

    public function test_compiler_outputs_category_sheet_and_catalog_tokens_for_both_modes() {
        $settings = RMM\DesignSystem\TokenSchema::get_default_settings();
        $css      = RMM\DesignSystem\CssCompiler::compile($settings);

        $this->assertStringContainsString('--rmm-ds-sheet-bg:', $css);
        $this->assertStringContainsString('--rmm-ds-sheet-card-active-bg:', $css);
        $this->assertStringContainsString('--rmm-ds-catalog-bg:', $css);
        $this->assertStringContainsString('--rmm-ds-catalog-wa-bg:', $css);
        $this->assertStringContainsString('[data-theme="light"]', $css);
        $this->assertStringContainsString('[data-theme="dark"]', $css);
    }

    public function test_category_navigation_base_styles_use_design_tokens_with_fallbacks() {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/frontend-menu.css');

        $this->assertNotFalse($css);

        $expected = [
            'background: var(--rmm-ds-card, #ffffff);',
            'gap: var(--rmm-ds-cat-gap, 8px);',
            'background: var(--rmm-ds-cat-idle-bg, #f1f5f9);',
            'border: 1px solid var(--rmm-ds-border, #e2e8f0);',
            'color: var(--rmm-ds-cat-idle-text, #475569);',
            'background: var(--rmm-ds-cat-active-bg, #0284c7);',
            'color: var(--rmm-ds-cat-active-text, #ffffff);',
            'border-color: var(--rmm-ds-cat-active-bg, #0284c7);',
        ];

        foreach ($expected as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $css,
                sprintf('Category navigation base CSS is missing token fallback: %s', $declaration)
            );
        }
    }



    public function test_live_search_base_styles_use_design_tokens_with_fallbacks() {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/frontend-menu.css');

        $this->assertNotFalse($css);

        $expected = [
            'color: var(--rmm-ds-search-icon, #94a3b8);',
            'border-radius: var(--rmm-ds-search-radius, 12px);',
            'border: 1.5px solid var(--rmm-ds-search-border, #e2e8f0);',
            'background: var(--rmm-ds-search-bg, #ffffff);',
            'border-color: var(--rmm-ds-primary, #0284c7);',
        ];

        foreach ($expected as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $css,
                sprintf('Live search base CSS is missing token fallback: %s', $declaration)
            );
        }
    }


    public function test_floating_cart_base_styles_use_design_tokens_with_fallbacks() {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/frontend-menu.css');

        $this->assertNotFalse($css);

        $expected = [
            'background: var(--rmm-ds-cart-bg, #0f172a);',
            'color: var(--rmm-ds-cart-text, #ffffff);',
            'border-radius: var(--rmm-ds-cart-radius, 16px);',
            'box-shadow: var(--rmm-ds-shadow-card, 0 10px 25px -5px rgba(15, 23, 42, 0.4));',
            'background: var(--rmm-ds-cart-badge-bg, #0284c7);',
            'color: var(--rmm-ds-cart-badge-text, #fff);',
        ];

        foreach ($expected as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $css,
                sprintf('Floating cart base CSS is missing token fallback: %s', $declaration)
            );
        }
    }



    public function test_modal_option_base_styles_use_design_tokens_with_fallbacks() {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/frontend-menu.css');

        $this->assertNotFalse($css);

        $expected = [
            'background: var(--rmm-ds-card, #ffffff);',
            'border: 1.5px solid var(--rmm-ds-border, #e2e8f0);',
            'border-radius: var(--rmm-ds-radius-input, 10px);',
            'border-color: var(--rmm-ds-primary, #0284c7);',
            'background: var(--rmm-ds-primary-soft, #f0f9ff);',
            'background: var(--rmm-ds-primary-soft, #eff6ff);',
            'accent-color: var(--rmm-ds-primary, #0284c7);',
            'color: var(--rmm-ds-primary, #0284c7);',
        ];

        foreach ($expected as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $css,
                sprintf('Modal option base CSS is missing token fallback: %s', $declaration)
            );
        }
    }


    public function test_modal_slider_controls_use_design_tokens_with_fallbacks() {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/frontend-menu.css');

        $this->assertNotFalse($css);

        $expected = [
            'background: var(--rmm-ds-card, rgba(255, 255, 255, 0.9));',
            'color: var(--rmm-ds-text, #0f172a);',
            'background: var(--rmm-ds-card, #ffffff);',
            'background: var(--rmm-ds-modal-backdrop, rgba(15, 23, 42, 0.55));',
            'background: var(--rmm-ds-muted, rgba(255, 255, 255, 0.5));',
            'background: var(--rmm-ds-primary, #ffffff);',
        ];

        foreach ($expected as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $css,
                sprintf('Modal slider CSS is missing token fallback: %s', $declaration)
            );
        }
    }

    public function test_entrance_language_modal_uses_design_tokens_with_fallbacks() {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/frontend-menu.css');

        $this->assertNotFalse($css);

        $expected = [
            'background: var(--rmm-ds-modal-backdrop, rgba(15, 23, 42, 0.75));',
            'background: var(--rmm-ds-card, #ffffff);',
            'border-radius: var(--rmm-ds-modal-radius, 24px);',
            'box-shadow: var(--rmm-ds-shadow-card, 0 25px 50px -12px rgba(0, 0, 0, 0.35));',
            'background: var(--rmm-ds-subtle, #f1f5f9);',
            'color: var(--rmm-ds-text-secondary, #64748b);',
            'background: var(--rmm-ds-primary-soft, #eff6ff);',
            'border-color: var(--rmm-ds-primary, #0284c7);',
            'accent-color: var(--rmm-ds-primary, #0284c7);',
        ];

        foreach ($expected as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $css,
                sprintf('Entrance language modal CSS is missing token fallback: %s', $declaration)
            );
        }
    }



    public function test_standalone_search_widget_uses_design_tokens_with_fallbacks() {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/frontend-menu.css');

        $this->assertNotFalse($css);

        $expected = [
            'border-radius: var(--rmm-ds-search-radius, 12px);',
            'border: 1.5px solid var(--rmm-ds-search-border, #e2e8f0);',
            'background: var(--rmm-ds-search-bg, #ffffff);',
            'border-color: var(--rmm-ds-primary, #0284c7);',
            'color: var(--rmm-ds-search-icon, #94a3b8);',
            'background: var(--rmm-ds-subtle, #f1f5f9);',
            'color: var(--rmm-ds-muted, #64748b);',
            'background: var(--rmm-ds-border, #e2e8f0);',
            'color: var(--rmm-ds-text, #0f172a);',
        ];

        foreach ($expected as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $css,
                sprintf('Standalone search CSS is missing token fallback: %s', $declaration)
            );
        }
    }


    public function test_view_switcher_controls_use_design_tokens_with_fallbacks() {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/frontend-menu.css');

        $this->assertNotFalse($css);

        $expected = [
            'background: var(--rmm-ds-subtle, #f1f5f9);',
            'border-radius: var(--rmm-ds-radius-btn, 12px);',
            'border: 1px solid var(--rmm-ds-border, #e2e8f0);',
            'border-radius: var(--rmm-ds-radius-input, 8px);',
            'color: var(--rmm-ds-muted, #64748b);',
            'color: var(--rmm-ds-text, #0f172a);',
            'outline: 2px solid var(--rmm-ds-primary, #0284c7);',
            'background: var(--rmm-ds-card, #ffffff);',
            'color: var(--rmm-ds-primary, #0284c7);',
            'box-shadow: var(--rmm-ds-shadow-btn, 0 2px 8px rgba(0, 0, 0, 0.08));',
        ];

        foreach ($expected as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $css,
                sprintf('View switcher CSS is missing token fallback: %s', $declaration)
            );
        }
    }


    public function test_lightbox_gallery_controls_use_design_tokens_with_fallbacks() {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/frontend-menu.css');

        $this->assertNotFalse($css);

        $expected = [
            'background: var(--rmm-ds-modal-backdrop, rgba(10, 15, 29, 0.95));',
            'backdrop-filter: blur(var(--rmm-ds-modal-blur, 12px));',
            'border-radius: var(--rmm-ds-radius-card, 16px);',
            'box-shadow: var(--rmm-ds-shadow-card, 0 25px 60px rgba(0, 0, 0, 0.6));',
            'background: var(--rmm-ds-bg, #020617);',
            'background: var(--rmm-ds-danger, #ef4444);',
            'background: var(--rmm-ds-subtle, rgba(255, 255, 255, 0.2));',
            'border: 1px solid var(--rmm-ds-border, rgba(255, 255, 255, 0.3));',
            'background: var(--rmm-ds-card, #ffffff);',
            'border-radius: var(--rmm-ds-radius-btn, 20px);',
        ];

        foreach ($expected as $declaration) {
            $this->assertStringContainsString(
                $declaration,
                $css,
                sprintf('Lightbox CSS is missing token fallback: %s', $declaration)
            );
        }
    }

}
