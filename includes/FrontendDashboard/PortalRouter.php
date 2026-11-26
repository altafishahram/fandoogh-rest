<?php
namespace RMM\FrontendDashboard;

if (!defined('ABSPATH')) {
    exit;
}

class PortalRouter {

    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('parse_request', [$this, 'detect_portal_request']);
        add_action('template_redirect', [$this, 'handle_portal_routing'], 1);

        // Shortcode fallbacks
        add_shortcode('fondogh_portal', [$this, 'render_shortcode']);
        add_shortcode('restaurant_dashboard', [$this, 'render_shortcode']);
    }

    public static function get_portal_slug() {
        return \RMM\Core\Settings::get('dashboard_slug', 'restaurant-portal');
    }

    public function add_rewrite_rules() {
        $slug = self::get_portal_slug();
        add_rewrite_rule(
            '^' . preg_quote($slug, '/') . '/?$',
            'index.php?rmm_portal=1',
            'top'
        );
        add_rewrite_rule(
            '^' . preg_quote($slug, '/') . '/manifest\.json$',
            'index.php?rmm_portal_manifest=1',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = 'rmm_portal';
        $vars[] = 'rmm_portal_manifest';
        return $vars;
    }

    /**
     * Resilient Request Interceptor:
     * Works on any permalink structure, even if rewrite rules have not been flushed!
     */
    public function detect_portal_request($wp) {
        $slug = self::get_portal_slug();

        // 1. Direct query parameter (?rmm_portal=1 or ?restaurant-portal=1)
        if (isset($_GET['rmm_portal']) || isset($_GET['restaurant-portal'])) {
            $wp->query_vars['rmm_portal'] = 1;
            return;
        }

        // 2. Direct URI path matching
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');

        // Handle WordPress installed in subdirectory (e.g. localhost/wordpress/restaurant-portal)
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
        if ($home_path && strpos($path, $home_path) === 0) {
            $path = trim(substr($path, strlen($home_path)), '/');
        }

        if ($path === $slug || strpos($path, $slug . '/') === 0) {
            if ($path === $slug . '/manifest.json') {
                $wp->query_vars['rmm_portal_manifest'] = 1;
            } else {
                $wp->query_vars['rmm_portal'] = 1;
            }
        }
    }

    public function handle_portal_routing() {
        // Handle PWA Manifest
        if (get_query_var('rmm_portal_manifest')) {
            $this->serve_manifest();
            exit;
        }

        // Handle Main Portal View
        $slug = self::get_portal_slug();
        $is_portal = false;

        if (get_query_var('rmm_portal') || isset($_GET['rmm_portal'])) {
            $is_portal = true;
        } elseif (is_page($slug) || (is_singular() && get_post_field('post_name', get_the_ID()) === $slug)) {
            $is_portal = true;
        }

        if ($is_portal) {
            // Prevent 404
            status_header(200);
            global $wp_query;
            if ($wp_query) {
                $wp_query->is_404 = false;
                $wp_query->is_page = false;
            }

            $this->render_portal();
            exit;
        }
    }

    private function serve_manifest() {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'name'             => __('پیشخوان هوشمند رستوران | منو آنلاین فندق', 'fondogh-online-menu'),
            'short_name'       => __('منو فندق', 'fondogh-online-menu'),
            'start_url'        => home_url('/' . self::get_portal_slug() . '/'),
            'display'          => 'standalone',
            'background_color' => '#0f172a',
            'theme_color'      => '#0284c7',
            'icons'            => [
                [
                    'src'   => RMM_PLUGIN_URL . 'assets/images/icon-192.png',
                    'sizes' => '192x192',
                    'type'  => 'image/png'
                ],
                [
                    'src'   => RMM_PLUGIN_URL . 'assets/images/icon-512.png',
                    'sizes' => '512x512',
                    'type'  => 'image/png'
                ]
            ]
        ]);
    }

    private function render_portal() {
        // Complete isolation: Dequeue all theme/plugin scripts and styles to prevent conflicts
        add_action('wp_enqueue_scripts', function () {
            global $wp_scripts, $wp_styles;
            $wp_scripts->queue = [];
            $wp_styles->queue = [];
        }, 9999);

        // Include dedicated portal template
        require RMM_PLUGIN_PATH . 'includes/FrontendDashboard/Views/portal-app.php';
    }

    public function render_shortcode() {
        $portal_url = home_url('/' . self::get_portal_slug() . '/');
        return sprintf(
            '<div class="rmm-portal-shortcode-launch" style="text-align:center; padding:30px 20px; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc;">' .
            '<p style="margin:0 0 14px; font-weight:600; color:#0f172a;">%s</p>' .
            '<a href="%s" class="button button-primary" style="display:inline-block; padding:10px 22px; background:#0284c7; color:#fff; border-radius:8px; text-decoration:none; font-weight:700;">%s</a>' .
            '</div>',
            esc_html__('برای مدیریت منو و سفارشات وارد پیشخوان مستقل رستوران شوید:', 'fondogh-online-menu'),
            esc_url($portal_url),
            esc_html__('ورود به پیشخوان رستوران', 'fondogh-online-menu')
        );
    }
}
