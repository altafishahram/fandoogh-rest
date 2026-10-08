<?php
namespace AdminCafe\Core;

use AdminCafe\Localization\Language;

defined('ABSPATH') || exit;

final class Assets
{
    public function register(): void
    {
        add_filter('script_loader_tag', static function (string $tag, string $handle): string {
            if (!in_array($handle, ['admincafe-menu', 'admincafe-panel'], true)) {
                return $tag;
            }
            $tag = preg_replace('/\s+type=[\'"][^\'"]*[\'"]/', '', $tag);
            return str_replace('<script ', '<script type="module" ', $tag);
        }, 10, 2);
    }

    public static function enqueue(string $app = 'menu'): void
    {
        wp_enqueue_style('admincafe', ADMINCAFE_URL . 'assets/admincafe.css', [], ADMINCAFE_VERSION);
        wp_enqueue_script('admincafe-' . $app, ADMINCAFE_URL . 'assets/' . $app . '.js', [], ADMINCAFE_VERSION, true);
    }

    public static function config(array $extra = [], string $app = 'menu'): array
    {
        $language = $app === 'panel' ? 'fa' : Language::resolve();
        return array_merge([
            'apiBase' => esc_url_raw(rest_url('admincafe/v1/')),
            'nonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
            'menuUrl' => Settings::menuUrl(), 'panelUrl' => Settings::panelUrl(),
            'assetUrl' => ADMINCAFE_URL . 'assets/', 'locale' => Language::htmlLocale($language),
            'language' => $language, 'direction' => Language::direction($language), 'languages' => Language::descriptors(),
            'defaultLanguage' => Settings::get('default_language', 'fa'), 'enabledLanguages' => Language::enabled(),
            'tableToken' => isset($_GET['table']) ? sanitize_text_field(wp_unslash($_GET['table'])) : '',
            'component' => 'menu', 'viewMode' => 'auto',
            'appearance' => $app === 'menu' ? \AdminCafe\Appearance\Appearance::publicSettings(Settings::all()) : [],
            'manifestUrl' => Settings::panelUrl() . 'manifest.webmanifest',
            'serviceWorkerUrl' => Settings::panelUrl() . 'sw.js',
            'strings' => $app === 'panel' ? Language::panelStrings() : Language::strings($language),
        ], $extra);
    }

    public static function mount(string $app = 'menu', array $extra = []): string
    {
        self::enqueue($app);
        $config = self::config($extra, $app);
        return '<div class="admincafe-root" dir="' . esc_attr($config['direction']) . '" lang="' . esc_attr($config['locale']) . '" data-admincafe-app="' . esc_attr($app) . '" data-admincafe-config="' . esc_attr(wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"></div>';
    }
}
