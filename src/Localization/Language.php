<?php
namespace FandooghRest\Localization;

use FandooghRest\Core\Settings;

defined('ABSPATH') || exit;

/** Customer language context, separate from operational and WordPress user locales. */
final class Language
{
    private static ?string $active = null;
    private static bool $switching = false;
    private static array $catalogs = [];

    public function register(): void
    {
        add_filter('determine_locale', [self::class, 'requestLocale'], 99);
        add_filter('locale', [self::class, 'requestLocale'], 99);
        add_filter('gettext_admincafe', [self::class, 'gettext'], 20, 3);
        add_filter('get_available_languages', static function (array $locales): array {
            foreach (self::supported() as $language) {
                if (is_file(FANDOOGH_REST_PATH . 'languages/checkout/core/' . $language['locale'] . '.mo')) { $locales[] = $language['locale']; }
            }
            return array_values(array_unique($locales));
        });
        add_filter('load_translation_file', [self::class, 'translationFile'], 20, 3);
        add_filter('load_script_translation_file', [self::class, 'scriptTranslationFile'], 20, 3);
        add_action('init', [self::class, 'loadCommerceTranslations'], -5);
        add_filter('the_title', static function (string $title, int $id): string {
            if (!self::isCustomerRequest()) { return $title; }
            if ($id > 0 && $id === (int) get_option('woocommerce_checkout_page_id', 0)) { return __('Checkout', 'woocommerce'); }
            if ($id > 0 && $id === (int) get_option('woocommerce_cart_page_id', 0)) { return __('Cart', 'woocommerce'); }
            return $title;
        }, 20, 2);
        add_filter('document_title_parts', static function (array $parts): array {
            if (self::isCustomerRequest() && function_exists('is_page')) {
                $checkout = (int) get_option('woocommerce_checkout_page_id', 0);
                $cart = (int) get_option('woocommerce_cart_page_id', 0);
                if ($checkout > 0 && is_page($checkout)) { $parts['title'] = __('Checkout', 'woocommerce'); }
                elseif ($cart > 0 && is_page($cart)) { $parts['title'] = __('Cart', 'woocommerce'); }
            }
            return $parts;
        });
    }

    public static function supported(): array
    {
        return [
            'fa' => ['code' => 'fa', 'name' => 'فارسی', 'locale' => 'fa_IR', 'html_locale' => 'fa-IR', 'direction' => 'rtl'],
            'en' => ['code' => 'en', 'name' => 'English', 'locale' => 'en_US', 'html_locale' => 'en-US', 'direction' => 'ltr'],
            'zh' => ['code' => 'zh', 'name' => '简体中文', 'locale' => 'zh_CN', 'html_locale' => 'zh-CN', 'direction' => 'ltr'],
            'tr' => ['code' => 'tr', 'name' => 'Türkçe', 'locale' => 'tr_TR', 'html_locale' => 'tr-TR', 'direction' => 'ltr'],
        ];
    }

    public static function enabled(): array
    {
        $codes = Settings::get('enabled_languages', ['fa', 'en', 'zh', 'tr']);
        $codes = is_array($codes) ? array_values(array_intersect(array_keys(self::supported()), $codes)) : [];
        return $codes ?: ['fa'];
    }

    public static function descriptors(): array
    {
        return array_values(array_intersect_key(self::supported(), array_flip(self::enabled())));
    }

    public static function valid(mixed $code): bool { return is_string($code) && in_array($code, self::enabled(), true); }

    public static function resolve(?string $explicit = null): string
    {
        if (self::valid($explicit)) { return $explicit; }
        if (self::valid(self::$active)) { return self::$active; }
        if (self::valid($_GET['lang'] ?? null)) { return $_GET['lang']; }
        $session = function_exists('WC') && WC()->session ? WC()->session->get('admincafe_language', '') : '';
        if (self::valid($session)) { return $session; }
        if (self::valid($_COOKIE['admincafe_language'] ?? null)) { return $_COOKIE['admincafe_language']; }
        $default = Settings::get('default_language', 'fa');
        return self::valid($default) ? $default : self::enabled()[0];
    }

    public static function current(): string { return self::resolve(); }
    public static function locale(string $code): string { return self::supported()[$code]['locale'] ?? 'fa_IR'; }
    public static function direction(string $code): string { return self::supported()[$code]['direction'] ?? 'rtl'; }
    public static function htmlLocale(string $code): string { return self::supported()[$code]['html_locale'] ?? 'fa-IR'; }

    /** Called only after language validation in a public customer callback. */
    public static function activate(string $code): bool
    {
        if (!self::valid($code)) { return false; }
        self::$switching = true;
        try {
            if (isset($GLOBALS['wp_locale_switcher'])) { switch_to_locale(self::locale($code)); }
        } finally { self::$switching = false; }
        self::$active = $code;
        self::loadCommerceTranslations();
        return true;
    }

    /** Only CSRF-protected order/checkout callbacks persist preferences. */
    public static function remember(string $code): bool
    {
        if (!self::valid($code)) { return false; }
        if (function_exists('WC') && WC()->session) { WC()->session->set('admincafe_language', $code); }
        if (!headers_sent()) {
            $path = (string) (wp_parse_url(home_url('/'), PHP_URL_PATH) ?: '/');
            setcookie('admincafe_language', $code, ['expires' => time() + 30 * DAY_IN_SECONDS, 'path' => $path, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax']);
        }
        $_COOKIE['admincafe_language'] = $code;
        return self::activate($code);
    }

    private static function path(): string
    {
        $path = wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $base = rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
        if ($base && str_starts_with($path, $base . '/')) { $path = substr($path, strlen($base)); }
        return '/' . trim(rawurldecode($path), '/');
    }

    private static function restRoute(): string
    {
        $route = $_GET['rest_route'] ?? '';
        if (is_string($route) && $route) { return '/' . ltrim($route, '/'); }
        $prefix = '/' . trim(rest_get_url_prefix(), '/') . '/';
        $path = self::path();
        return str_starts_with($path, $prefix) ? '/' . substr($path, strlen($prefix)) : '';
    }

    public static function isPanelRequest(): bool
    {
        $path = self::path();
        $panel = '/' . trim((string) Settings::get('panel_slug', 'cafe-panel'), '/');
        return $path === $panel || str_starts_with($path, $panel . '/') || str_starts_with(self::restRoute(), '/admincafe/v1/manage/');
    }

    public static function isCustomerRequest(): bool
    {
        if (is_admin() || self::isPanelRequest() || (defined('DOING_CRON') && DOING_CRON) || (defined('WP_CLI') && WP_CLI)) { return false; }
        $route = self::restRoute();
        if ($route) {
            return (bool) preg_match('#^/wc/store/v\d+/(cart|checkout|order)(?:/|$)#', $route)
                || in_array($route, ['/admincafe/v1/bootstrap', '/admincafe/v1/checkout', '/admincafe/v1/orders/table', '/admincafe/v1/orders/track'], true);
        }
        if (isset($_GET['wc-ajax']) && is_string($_GET['wc-ajax'])) { return true; }
        $path = self::path();
        if ($path === '/' . trim((string) Settings::get('menu_slug', 'menu'), '/') || str_starts_with($path, '/cafe-qr/')) { return true; }
        foreach ([(int) Settings::get('menu_page_id', 0), (int) get_option('woocommerce_cart_page_id', 0), (int) get_option('woocommerce_checkout_page_id', 0)] as $id) {
            if (!$id || get_post_status($id) !== 'publish') { continue; }
            $url = get_permalink($id);
            $target = $url ? wp_parse_url($url, PHP_URL_PATH) : '';
            $base = rtrim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
            if ($base && is_string($target) && str_starts_with($target, $base . '/')) { $target = substr($target, strlen($base)); }
            $target = '/' . trim((string) $target, '/');
            if ($path === $target || ($target !== '/' && str_starts_with($path, $target . '/')) || (isset($_GET['page_id']) && (int) $_GET['page_id'] === $id)) { return true; }
        }
        return false;
    }

    public static function requestLocale(string $locale): string
    {
        if (self::$switching) { return $locale; }
        if (self::isPanelRequest()) { return 'fa_IR'; }
        return self::isCustomerRequest() ? self::locale(self::current()) : $locale;
    }

    private static function readCatalog(string $relative): array
    {
        if (!isset(self::$catalogs[$relative])) {
            $path = FANDOOGH_REST_PATH . $relative;
            $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
            self::$catalogs[$relative] = is_array($data) ? $data : [];
        }
        return self::$catalogs[$relative];
    }

    public static function strings(string $code): array
    {
        $ui = self::readCatalog('resources/customer-strings.json');
        $server = self::readCatalog('resources/customer-server-strings.json');
        return array_merge($ui[$code] ?? $ui['fa'] ?? [], $server[$code] ?? $server['fa'] ?? []);
    }

    public static function panelStrings(): array { return self::readCatalog('languages/fa_IR.json'); }
    public static function staffText(string $message): string { return self::panelStrings()[$message] ?? $message; }

    public static function gettext(string $translation, string $message, string $domain): string
    {
        if (self::isPanelRequest()) { return self::panelStrings()[$message] ?? $message; }
        if (self::isCustomerRequest()) {
            $code = self::current();
            return self::strings($code)[$message] ?? ($code === 'fa' ? self::panelStrings()[$message] ?? $translation : $message);
        }
        return $translation;
    }

    public static function translationFile(string $file, string $domain, ?string $locale = null): string
    {
        $locale ??= determine_locale();
        // Prefer installed/custom packs; fall back only on customer/operational pages.
        if (is_readable($file) || (!self::isCustomerRequest() && !self::isPanelRequest())) { return $file; }
        if (!in_array($locale, ['fa_IR', 'zh_CN', 'tr_TR'], true)) { return $file; }
        $type = $domain === 'woocommerce' ? 'woocommerce' : ($domain === 'default' ? 'core' : '');
        if (!$type) { return $file; }
        if ($domain === 'default' && preg_match('/^(admin-|ms-)/', basename($file))) { return $file; }
        $name = $domain === 'woocommerce' ? 'woocommerce-' . $locale . '.mo' : $locale . '.mo';
        $candidate = FANDOOGH_REST_PATH . 'languages/checkout/' . $type . '/' . $name;
        return is_readable($candidate) ? $candidate : $file;
    }

    public static function scriptTranslationFile(string $file, string $handle, string $domain): string
    {
        if (is_readable($file) || !self::isCustomerRequest()) { return $file; }
        $type = $domain === 'woocommerce' ? 'woocommerce' : ($domain === 'default' ? 'core' : '');
        if (!$type) { return $file; }
        $name = basename($file);
        $prefix = ($domain === 'woocommerce' ? 'woocommerce-' : '') . self::locale(self::current()) . '-';
        if (!str_starts_with($name, $prefix) || !str_ends_with($name, '.json')) { return $file; }
        $candidate = FANDOOGH_REST_PATH . 'languages/checkout/' . $type . '/' . $name;
        return is_readable($candidate) ? $candidate : $file;
    }

    public static function loadCommerceTranslations(): void
    {
        if (!self::isCustomerRequest()) { return; }
        $code = self::current();
        $locale = self::locale($code);
        $file = FANDOOGH_REST_PATH . 'languages/checkout/woocommerce/woocommerce-' . $locale . '.mo';
        $installed = WP_LANG_DIR . '/plugins/woocommerce-' . $locale . '.mo';
        if (is_file($file) && !is_file($installed)) { load_textdomain('woocommerce', $file, $locale); }
    }
}
