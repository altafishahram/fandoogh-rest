<?php

use PHPUnit\Framework\TestCase;

class CssArchitectureTest extends TestCase {

    private function frontend_css() {
        $base = dirname(__DIR__, 2) . '/assets/css/';

        return implode("\n", [
            file_get_contents($base . 'frontend-menu-base.css'),
            file_get_contents($base . 'frontend-menu-widgets.css'),
            file_get_contents($base . 'frontend-menu-modern.css'),
        ]);
    }

    public function test_frontend_styles_are_split_into_ordered_layers() {
        $root = dirname(__DIR__, 2);
        foreach ([
            'assets/css/frontend-menu-base.css',
            'assets/css/frontend-menu-widgets.css',
            'assets/css/frontend-menu-modern.css',
            'assets/css/category-sheet.css',
            'assets/css/portal-pwa.css',
            'assets/css/admin-ui.css',
            'assets/css/design-studio-admin.css',
            'assets/css/thermal-receipt.css',
        ] as $path) {
            $this->assertFileExists($root . '/' . $path);
        }

        $assets = file_get_contents($root . '/includes/Core/Assets.php');
        $this->assertStringContainsString('frontend-menu-base.css', $assets);
        $this->assertStringContainsString('frontend-menu-widgets.css', $assets);
        $this->assertStringContainsString('frontend-menu-modern.css', $assets);
        $this->assertStringContainsString('admin-ui.css', $assets);
        $this->assertStringContainsString("['rmm-frontend-menu-base']", $assets);
        $this->assertStringContainsString("['rmm-frontend-menu-widgets']", $assets);
        $this->assertStringContainsString("RMM_PLUGIN_URL . 'assets/css/frontend-menu-modern.css'", $assets);
        $this->assertStringContainsString("['rmm-frontend-menu-widgets']", $assets);
        $this->assertStringContainsString("'rmm-frontend-menu',", $assets);
        $this->assertFileDoesNotExist($root . '/assets/css/frontend-menu.css');
        $this->assertFileDoesNotExist($root . '/assets/css/frontend-menu-themes.css');
    }

    public function test_static_widget_styles_are_not_left_inline() {
        $root = dirname(__DIR__, 2);
        $paths = [
            'includes/Elementor/Widgets/FoodLoopWidget.php',
            'includes/Elementor/Widgets/FoodCardWidget.php',
            'includes/Elementor/Widgets/FoodModalWidget.php',
            'includes/FrontendDashboard/Views/portal-app.php',
            'includes/Elementor/Widgets/CategoryBarWidget.php',
            'includes/Elementor/Widgets/CategorySheetWidget.php',
            'includes/Elementor/Widgets/SearchBarWidget.php',
            'includes/Elementor/Widgets/ThemeToggleWidget.php',
            'includes/FrontendDashboard/Views/thermal-receipt.php',
            'includes/FrontendDashboard/PortalRouter.php',
            'includes/Branches/BranchTaxonomy.php',
            'includes/Core/Settings.php',
            'includes/Elementor/TemplateLibrary/TemplateManager.php',
            'includes/I18n/MultiLangManager.php',
        ];

        foreach ($paths as $path) {
            $source = file_get_contents($root . '/' . $path);
            preg_match_all('/\bstyle\s*=\s*"([^"]*)"/i', $source, $matches);

            foreach ($matches[1] as $inline_style) {
                $this->assertMatchesRegularExpression(
                    '/^(?:\s*display\s*:\s*none\s*;?\s*|(?=[\s\S]*<\?php)(?=[\s\S]*display\s*:\s*none)[\s\S]*\?>\s*)$/i',
                    trim($inline_style),
                    sprintf('Only state-driven visibility may remain inline in %s: %s', $path, $inline_style)
                );
            }
        }
    }

    public function test_static_style_blocks_are_externalized() {
        $root = dirname(__DIR__, 2);
        foreach ([
            'includes/Elementor/Widgets/ThemeToggleWidget.php',
            'includes/Core/Settings.php',
            'includes/DesignSystem/SettingsPage.php',
            'includes/FrontendDashboard/Views/thermal-receipt.php',
        ] as $path) {
            $source = file_get_contents($root . '/' . $path);
            $this->assertDoesNotMatchRegularExpression('/<style\\s*>/i', $source, sprintf('Static style block remains in %s', $path));
        }

        $settings_page = file_get_contents($root . '/includes/DesignSystem/SettingsPage.php');
        $receipt = file_get_contents($root . '/includes/FrontendDashboard/Views/thermal-receipt.php');
        $router = file_get_contents($root . '/includes/FrontendDashboard/PortalRouter.php');
        $languages = file_get_contents($root . '/includes/I18n/MultiLangManager.php');
        $this->assertStringContainsString('design-studio-admin.css', $settings_page);
        $this->assertStringContainsString('thermal-receipt.css', $receipt);
        $this->assertStringContainsString("wp_enqueue_style('rmm-frontend-menu-widgets')", $router);
        $this->assertStringContainsString("wp_enqueue_style('rmm-frontend-menu-widgets')", $languages);
    }

    public function test_extracted_admin_and_shared_widget_styles_have_rules() {
        $root = dirname(__DIR__, 2);
        $widgets = file_get_contents($root . '/assets/css/frontend-menu-widgets.css');
        $admin = file_get_contents($root . '/assets/css/admin-ui.css');

        foreach ([
            '.rmm-empty-cat-notice',
            '.rmm-search-widget-container .rmm-standalone-search-wrap',
            '.rmm-lang-switcher',
            '.rmm-theme-toggle-widget-wrap .rmm-icon-day',
            '.rmm-portal-shortcode-launch',
        ] as $selector) {
            $this->assertStringContainsString($selector, $widgets);
        }

        foreach ([
            '.rmm-branch-status--open',
            '.rmm-admin-number-100',
            '.rmm-template-preview-image',
        ] as $selector) {
            $this->assertStringContainsString($selector, $admin);
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

        preg_match_all('/\b(rmm-(?:loop|card|modal|portal)-style-\d+|rmm-(?:loop-image-trigger|loop-title-trigger|food-details-trigger|loop-order-trigger|card-image-trigger|card-title-trigger|card-order-trigger|modal-close-trigger))\b/', $source, $matches);
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
            'assets/css/frontend-menu-modern.css',
            'assets/css/category-sheet.css',
            'assets/css/portal-pwa.css',
            'assets/css/admin-ui.css',
            'assets/css/design-studio-admin.css',
            'assets/css/thermal-receipt.css',
        ];

        foreach ($paths as $path) {
            $css = file_get_contents($root . '/' . $path);
            $css = preg_replace('~/\*.*?\*/~s', '', $css);
            $css = preg_replace('/"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'/s', '', $css);
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


    public function test_storefront_and_portal_css_are_component_scoped() {
        $root = dirname(__DIR__, 2);
        $paths = [
            'assets/css/frontend-menu-base.css',
            'assets/css/frontend-menu-widgets.css',
            'assets/css/frontend-menu-modern.css',
            'assets/css/category-sheet.css',
            'assets/css/portal-pwa.css',
        ];

        foreach ($paths as $path) {
            $css = file_get_contents($root . '/' . $path);
            $this->assertDoesNotMatchRegularExpression(
                '/^\s*(?:\*|html|button|a|img|input)\s*\{/m',
                $css,
                sprintf('Found a global selector in %s', $path)
            );
        }

        $portal_css = file_get_contents($root . '/assets/css/portal-pwa.css');
        $portal_view = file_get_contents($root . '/includes/FrontendDashboard/Views/portal-app.php');
        $this->assertStringContainsString('class="rmm-portal-html"', $portal_view);
        $this->assertStringContainsString('html.rmm-portal-html *', $portal_css);
        $this->assertStringContainsString('html.rmm-portal-html[data-theme="dark"]', $portal_css);
        $this->assertStringContainsString('.rmm-tab-btn .tab-icon-wrap', $portal_css);
        $this->assertStringContainsString('.rmm-modal-backdrop .modal-header-icon', $portal_css);
    }

    public function test_responsive_breakpoints_cover_storefront_and_portal_layouts() {
        $root = dirname(__DIR__, 2);
        $expected = [
            'assets/css/frontend-menu-base.css' => ['max-width: 991px', 'max-width: 640px'],
            'assets/css/frontend-menu-widgets.css' => ['max-width: 768px', 'max-width: 580px', 'max-width: 420px'],
            'assets/css/frontend-menu-modern.css' => ['max-width: 767px', 'max-width: 640px', 'max-width: 540px'],
            'assets/css/category-sheet.css' => ['min-width: 768px', 'prefers-reduced-motion: reduce'],
            'assets/css/portal-pwa.css' => ['max-width: 900px', 'max-width: 600px', 'max-width: 480px'],
        ];

        foreach ($expected as $path => $queries) {
            $css = file_get_contents($root . '/' . $path);
            foreach ($queries as $query) {
                $this->assertStringContainsString(
                    '@media (' . $query . ')',
                    $css,
                    sprintf('Missing responsive rule %s in %s', $query, $path)
                );
            }
        }
    }

    public function test_existing_important_usage_does_not_increase() {
        $root = dirname(__DIR__, 2);
        $budgets = [
            'frontend' => [14, $this->frontend_css()],
            'portal' => [0, file_get_contents($root . '/assets/css/portal-pwa.css')],
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

    public function test_only_modern_menu_preset_remains_available() {
        $root = dirname(__DIR__, 2);
        $widget = file_get_contents($root . '/includes/Elementor/Widgets/FoodMenuWidget.php');
        $css = file_get_contents($root . '/assets/css/frontend-menu-modern.css');

        $this->assertStringContainsString("'modern_grid' =>", $widget);
        $this->assertStringContainsString('$style = \'modern_grid\';', $widget);

        foreach (['oriental', 'app_list', 'fine_dining', 'compact_grid', 'rmm-theme-oriental'] as $legacy_theme) {
            $this->assertStringNotContainsString($legacy_theme, $widget);
            $this->assertStringNotContainsString($legacy_theme, $css);
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
