<?php

use PHPUnit\\Framework\\TestCase;

class CssArchitectureTest extends TestCase {

    private function frontend_css() {
        $base = dirname(__DIR__, 2) . '/assets/css/';

        return implode("\n", [
            file_get_contents($base . 'frontend-menu-base.css'),
            file_get_contents($base . 'frontend-menu-widgets.css'),
            file_get_contents($base . 'frontend-menu-themes.css'),
        ]);
    }

    public function test_frontend_styles_are_split_into_ordered_layers() {
        $root = dirname(__DIR__, 2);
        foreach ([
            'assets/css/frontend-menu-base.css',
            'assets/css/frontend-menu-widgets.css',
            'assets/css/frontend-menu-themes.css',
            'assets/css/category-sheet.css',
            'assets/css/portal-pwa.css',
        ] as $path) {
            $this->assertFileExists($root . '/' . $path);
        }

        $assets = file_get_contents($root . '/includes/Core/Assets.php');
        $this->assertStringContainsString('frontend-menu-base.css', $assets);
        $this->assertStringContainsString('frontend-menu-widgets.css', $assets);
        $this->assertStringContainsString('frontend-menu-themes.css', $assets);
        $this->assertStringContainsString("['rmm-frontend-menu-base']", $assets);
        $this->assertStringContainsString("['rmm-frontend-menu-widgets']", $assets);
        $this->assertStringContainsString("['rmm-frontend-menu-themes']", $assets);
        $this->assertFileDoesNotExist($root . '/assets/css/frontend-menu.css');
    }

    public function test_static_widget_styles_are_not_left_inline() {
        $root = dirname(__DIR__, 2);
        $paths = [
            'includes/Elementor/Widgets/FoodLoopWidget.php',
            'includes/Elementor/Widgets/FoodCardWidget.php',
            'includes/Elementor/Widgets/FoodModalWidget.php',
            'includes/FrontendDashboard/Views/portal-app.php',
        ];

        foreach ($paths as $path) {
            $source = file_get_contents($root . '/' . $path);
            preg_match_all('/\\bstyle\\s*=\\s*([\\\x22\\\x27])([^\\\x22\\\x27]*)\\1/i', $source, $matches);

            foreach ($matches[2] as $inline_style) {
                $this->assertMatchesRegularExpression(
                    '/^\\s*display\\s*:\\s*none\\s*;?\\s*$/i',
                    trim($inline_style),
                    sprintf('Only state-driven visibility may remain inline in %s: %s', $path, $inline_style)
                );
            }
        }
    }

    public function test_extracted_widget_classes_have_stylesheet_rules() {
        $root = dirname(__DIR__, 2);
        $sources = [
            file_get_contents($root . '/includes/Elementor/Widgets/FoodLoopWidget.php'),
            file_get_contents($root . '/includes/Elementor/Widgets/FoodCardWidget.php'),
            file_get_contents($root . '/includes/Elementor/Widgets/FoodModalWidget.php'),
            file_get_contents($root . '/includes/FrontendDashboard/Views/portal-app.php'),
        ];
        $css = $this->frontend_css() . "\n" . file_get_contents($root . '/assets/css/portal-pwa.css');
        $source = implode("\n", $sources);

        preg_match_all('/\\b(rmm-(?:loop|card|modal|portal)-style-\\d+|rmm-(?:loop-image-trigger|loop-title-trigger|food-details-trigger|loop-order-trigger|card-image-trigger|card-title-trigger|card-order-trigger|modal-close-trigger))\\b/', $source, $matches);
        $this->assertNotEmpty($matches[1]);

        foreach (array_unique($matches[1]) as $class) {
            $this->assertStringContainsString('.' . $class, $css, sprintf('Missing CSS rule for extracted class %s', $class));
        }
    }

    public function test_css_blocks_are_balanced() {
        $root = dirname(__DIR__, 2);
        $paths = [
            'assets/css/frontend-menu-base.css',
            'assets/css/frontend-menu-widgets.css',
            'assets/css/frontend-menu-themes.css',
            'assets/css/category-sheet.css',
            'assets/css/portal-pwa.css',
        ];

        foreach ($paths as $path) {
            $css = file_get_contents($root . '/' . $path);
            $css = preg_replace('~/\\*.*?\\*/~s', '', $css);
            $css = preg_replace('/"(?:\\\\.|[^"\\\\])*"|\\x27(?:\\\\.|[^\\x27\\\\])*\\x27/s', '', $css);
            $depth = 0;

            foreach (str_split($css) as $character) {
                if ($character === '{') {
                    $depth++;
                } elseif ($character === '}') {
                    $depth--;
                    $this->assertGreaterThanOrEqual(0, $depth, sprintf('Unexpected closing brace in %s', $path));
                }
            }

            $this->assertSame(0, $depth, sprintf('Unclosed CSS block in %s', $path));
        }
    }

    public function test_existing_important_usage_does_not_increase() {
        $root = dirname(__DIR__, 2);
        $budgets = [
            'frontend' => [315, $this->frontend_css()],
            'portal' => [240, file_get_contents($root . '/assets/css/portal-pwa.css')],
            'category sheet' => [1, file_get_contents($root . '/assets/css/category-sheet.css')],
        ];

        foreach ($budgets as $name => [$budget, $css]) {
            $this->assertLessThanOrEqual(
                $budget,
                substr_count($css, '!important'),
                sprintf('Avoid increasing !important usage in %s styles', $name)
            );
        }
    }

    public function test_extracted_styles_use_the_existing_theme_tokens() {
        $root = dirname(__DIR__, 2);
        $frontend = $this->frontend_css();
        $portal = file_get_contents($root . '/assets/css/portal-pwa.css');

        $this->assertStringContainsString('var(--rmm-ds-subtle, #f1f5f9)', $frontend);
        $this->assertStringContainsString('var(--rmm-ds-muted, #94a3b8)', $frontend);
        $this->assertStringContainsString('var(--rmm-bg, #f8fafc)', $portal);
        $this->assertStringContainsString('var(--rmm-text-secondary, #334155)', $portal);
        $this->assertStringContainsString('var(--rmm-danger, #ef4444)', $portal);
    }
}
