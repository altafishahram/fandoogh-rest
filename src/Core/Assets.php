<?php
namespace FandooghRest\Core;

use FandooghRest\Localization\Language;
use FandooghRest\Branches\Branches;

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
        wp_enqueue_style('admincafe', FANDOOGH_REST_URL . 'assets/fandoogh-rest.css', [], FANDOOGH_REST_VERSION);
        wp_enqueue_script('admincafe-' . $app, FANDOOGH_REST_URL . 'assets/' . $app . '.js', [], FANDOOGH_REST_VERSION, true);
    }

    public static function config(array $extra = [], string $app = 'menu'): array
    {
        if (!empty($extra['branchId']) && (int) $extra['branchId'] !== Branches::current()) {
            return Branches::runFor((int) $extra['branchId'], static fn(): array => self::config($extra, $app));
        }
        $branch = Branches::get(Branches::current());
        $language = $app === 'panel' ? 'fa' : Language::resolve();
        return array_merge([
            'apiBase' => esc_url_raw(rest_url('admincafe/v1/')),
            'nonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
            'menuUrl' => Settings::menuUrl(), 'panelUrl' => Settings::panelUrl(),
            'assetUrl' => FANDOOGH_REST_URL . 'assets/', 'locale' => Language::htmlLocale($language),
            'language' => $language, 'direction' => Language::direction($language), 'languages' => Language::descriptors(),
            'defaultLanguage' => Settings::get('default_language', 'fa'), 'enabledLanguages' => Language::enabled(),
            'tableToken' => isset($_GET['table']) ? sanitize_text_field(wp_unslash($_GET['table'])) : '',
            'branchId' => $app === 'panel' && !isset($_GET['branch_id']) ? 0 : Branches::current(),
            'branchSlug' => $branch['slug'] ?? '', 'branchName' => $branch['name'] ?? '',
            'component' => 'menu', 'viewMode' => 'auto',
            'appearance' => $app === 'menu' ? \FandooghRest\Appearance\Appearance::publicSettings(Settings::all(), \FandooghRest\Appearance\Appearance::menuScope()) : [],
            'manifestUrl' => Settings::panelUrl() . 'manifest.webmanifest',
            'serviceWorkerUrl' => Settings::panelUrl() . 'sw.js',
            'strings' => $app === 'panel' ? Language::panelStrings() : Language::strings($language),
        ], $extra);
    }

    public static function mount(string $app = 'menu', array $extra = []): string
    {
        if ($app === 'menu' && !empty($extra['branchId'])) {
            $branch = Branches::get((int) $extra['branchId']);
            if (!$branch || empty($branch['enabled'])) { return '<p>' . esc_html__('This branch menu is unavailable.', 'fandoogh-rest') . '</p>'; }
        }
        self::enqueue($app);
        $config = self::config($extra, $app);
        return '<div class="admincafe-root" dir="' . esc_attr($config['direction']) . '" lang="' . esc_attr($config['locale']) . '" data-fandoogh-branch="' . esc_attr((string) $config['branchId']) . '" data-admincafe-app="' . esc_attr($app) . '" data-admincafe-config="' . esc_attr(wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"></div>';
    }
}
