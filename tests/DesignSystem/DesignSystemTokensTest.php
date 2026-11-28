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
        $css = RMM\\DesignSystem\\CssCompiler::get_component_mapping_css();

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
        $settings = RMM\\DesignSystem\\TokenSchema::get_default_settings();
        $css      = RMM\\DesignSystem\\CssCompiler::compile($settings);

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
}
