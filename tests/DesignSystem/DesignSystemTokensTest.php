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
