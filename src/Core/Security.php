<?php
namespace FandooghRest\Core;

defined('ABSPATH') || exit;

final class Security
{
    private static function session(): ?\WC_Session
    {
        if (!function_exists('WC')) {
            return null;
        }
        if (!WC()->session && did_action('woocommerce_init')) {
            wc_load_cart();
        }
        if (WC()->session && !WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
        return WC()->session;
    }

    public static function token(): string
    {
        $session = self::session();
        if (!$session) {
            return '';
        }
        $token = (string) $session->get('admincafe_csrf', '');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            $session->set('admincafe_csrf', $token);
        }
        return $token;
    }

    public static function sessionKey(): string
    {
        $session = self::session();
        return $session ? hash_hmac('sha256', (string) $session->get_customer_id(), wp_salt('auth')) : '';
    }

    public static function sameOrigin(\WP_REST_Request $request): bool
    {
        $origin = $request->get_header('origin') ?: $request->get_header('referer');
        $site = wp_parse_url(home_url());
        $remote = $origin ? wp_parse_url($origin) : null;
        if (($remote && (($remote['host'] ?? '') !== ($site['host'] ?? '') || ($remote['scheme'] ?? '') !== ($site['scheme'] ?? '') || ($remote['port'] ?? null) !== ($site['port'] ?? null))) || $request->get_header('sec-fetch-site') === 'cross-site') {
            return false;
        }
        return true;
    }

    public static function publicPermission(\WP_REST_Request $request): bool|\WP_Error
    {
        if (!self::sameOrigin($request)) {
            return new \WP_Error('admincafe_origin', __('Cross-site order requests are not allowed.', 'fandoogh-rest'), ['status' => 403]);
        }
        $expected = self::token();
        $supplied = (string) $request->get_header('x-admincafe-token');
        if (!$expected || !$supplied || !hash_equals($expected, $supplied)) {
            return new \WP_Error('admincafe_csrf', __('Your session has expired. Reload the menu and try again.', 'fandoogh-rest'), ['status' => 403]);
        }
        return true;
    }

    public static function throttle(string $bucket, int $limit, int $seconds): bool|\WP_Error
    {
        // Never trust arbitrary forwarded IP headers. Configure the proxy at the server.
        $identity = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $key = 'ac_rate_' . hash('sha256', $bucket . ':' . $identity);
        $record = get_transient($key);
        $count = is_array($record) ? (int) ($record['count'] ?? 0) : 0;
        $until = is_array($record) ? (int) ($record['until'] ?? 0) : time() + $seconds;
        if ($until <= time()) {
            $count = 0;
            $until = time() + $seconds;
        }
        if ($count >= $limit) {
            return new \WP_Error('admincafe_rate_limit', __('Too many requests. Please wait and try again.', 'fandoogh-rest'), ['status' => 429, 'retry_after' => max(1, $until - time())]);
        }
        set_transient($key, ['count' => $count + 1, 'until' => $until], max(1, $until - time()));
        return true;
    }
}
