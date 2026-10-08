<?php
namespace FandooghRest\Core;

defined('ABSPATH') || exit;

final class Routes
{
    public function register(): void
    {
        add_action('init', [self::class, 'rewrites']);
        add_filter('query_vars', static fn(array $vars): array => array_merge($vars, ['admincafe_route', 'admincafe_token']));
        add_filter('request', static function (array $vars): array {
            $pageId = (int) Settings::get('menu_page_id', 0);
            if (($vars['admincafe_route'] ?? '') === 'menu' && $pageId && get_post_status($pageId) === 'publish') {
                // Let WordPress resolve the builder page, including when its slug is /menu/.
                // Redirecting to the same virtual route would create a redirect loop.
                unset($vars['admincafe_route']);
                $vars['page_id'] = $pageId;
            }
            return $vars;
        });
        add_action('template_redirect', [$this, 'render'], 0);
    }

    public static function rewrites(): void
    {
        $menu = preg_quote((string) Settings::get('menu_slug'), '#');
        $panel = preg_quote((string) Settings::get('panel_slug'), '#');
        add_rewrite_rule('^' . $menu . '/?$', 'index.php?admincafe_route=menu', 'top');
        add_rewrite_rule('^' . $panel . '/?$', 'index.php?admincafe_route=panel', 'top');
        add_rewrite_rule('^' . $panel . '/sw\.js$', 'index.php?admincafe_route=worker', 'top');
        add_rewrite_rule('^' . $panel . '/manifest\.webmanifest$', 'index.php?admincafe_route=manifest', 'top');
        add_rewrite_rule('^cafe-qr/([a-zA-Z0-9_-]{16,128})/?$', 'index.php?admincafe_route=qr&admincafe_token=$matches[1]', 'top');
    }

    public function render(): void
    {
        $route = get_query_var('admincafe_route');
        if (!$route) {
            return;
        }
        status_header(200);
        if ($route === 'qr') {
            $table = \FandooghRest\Tables\Tables::findByToken((string) get_query_var('admincafe_token'));
            if (!$table || empty($table['enabled'])) {
                status_header(404);
                wp_die(esc_html__('This table menu is unavailable.', 'fandoogh-rest'), '', ['response' => 404]);
            }
            nocache_headers();
            $arguments = ['table' => $table['token']];
            if (\FandooghRest\Localization\Language::valid($_GET['lang'] ?? null)) { $arguments['lang'] = $_GET['lang']; }
            wp_safe_redirect(add_query_arg($arguments, Settings::menuUrl()), 302);
            exit;
        }
        if ($route === 'worker') {
            header('Content-Type: application/javascript; charset=utf-8');
            header('Service-Worker-Allowed: ' . wp_parse_url(Settings::panelUrl(), PHP_URL_PATH));
            header('Cache-Control: no-cache');
            header('X-Content-Type-Options: nosniff');
            $worker = FANDOOGH_REST_PATH . 'public/sw.js';
            if (is_file($worker)) {
                readfile($worker);
            }
            exit;
        }
        if ($route === 'manifest') {
            header('Content-Type: application/manifest+json; charset=utf-8');
            header('Cache-Control: no-cache');
            echo wp_json_encode([
                'id' => Settings::panelUrl(), 'name' => Settings::get('restaurant_name') . ' — ' . __('Fandoogh Rest', 'fandoogh-rest'), 'short_name' => __('Fandoogh Rest', 'fandoogh-rest'),
                'description' => 'Restaurant operations panel', 'lang' => 'fa', 'dir' => 'rtl', 'display' => 'standalone',
                'start_url' => Settings::panelUrl(), 'scope' => Settings::panelUrl(),
                'theme_color' => Settings::get('accent'), 'background_color' => Settings::get('background'),
                'icons' => [
                    ['src' => FANDOOGH_REST_URL . 'assets/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                    ['src' => FANDOOGH_REST_URL . 'assets/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        nocache_headers();
        show_admin_bar(false);
        if ($route === 'panel') {
            $this->panelLogin();
            if (!Access::can('admincafe_manage_orders')) {
                wp_die(esc_html__('You do not have access to this restaurant panel.', 'fandoogh-rest'), '', ['response' => 403]);
            }
            Assets::enqueue('panel');
        } else {
            Assets::enqueue('menu');
        }
        $panel = $route === 'panel';
        require FANDOOGH_REST_PATH . 'templates/app.php';
        exit;
    }

    private function panelLogin(): void
    {
        if (isset($_GET['logout']) && is_user_logged_in()) {
            if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'admincafe_logout')) {
                wp_logout();
                wp_safe_redirect(Settings::panelUrl());
                exit;
            }
        }
        if (is_user_logged_in()) {
            return;
        }
        $error = '';
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? ''));
            $limit = Security::throttle('login', 10, 300);
            $request = new \WP_REST_Request('POST');
            foreach (['origin' => 'HTTP_ORIGIN', 'referer' => 'HTTP_REFERER', 'sec-fetch-site' => 'HTTP_SEC_FETCH_SITE'] as $header => $serverKey) {
                if (isset($_SERVER[$serverKey])) {
                    $request->set_header($header, (string) $_SERVER[$serverKey]);
                }
            }
            if (!wp_verify_nonce($nonce, 'admincafe_login') || !Security::sameOrigin($request) || is_wp_error($limit)) {
                $error = __('Please refresh the page or wait a few minutes before trying again.', 'fandoogh-rest');
            } else {
                $user = wp_signon([
                    'user_login' => sanitize_text_field(wp_unslash($_POST['username'] ?? '')),
                    'user_password' => (string) wp_unslash($_POST['password'] ?? ''),
                    'remember' => !empty($_POST['remember']),
                ], is_ssl());
                if (is_wp_error($user) || !user_can($user, 'admincafe_manage_orders')) {
                    if (!is_wp_error($user)) {
                        wp_clear_auth_cookie();
                    }
                    $error = __('Login was unsuccessful. Check your restaurant account credentials.', 'fandoogh-rest');
                } else {
                    wp_safe_redirect(Settings::panelUrl());
                    exit;
                }
            }
        }
        require FANDOOGH_REST_PATH . 'templates/login.php';
        exit;
    }
}
