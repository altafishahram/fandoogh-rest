<?php

namespace FandooghRest\Rest;

use FandooghRest\Commerce\Orders;
use FandooghRest\Commerce\Checkout;
use FandooghRest\Core\Settings;
use FandooghRest\Core\Security;
use FandooghRest\Menu\Catalog;
use FandooghRest\Tables\Tables;
use FandooghRest\Localization\Language;
use FandooghRest\Branches\Branches;

final class Ordering
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
    }

    public function routes(): void
    {
        $public = [Security::class, 'publicPermission'];
        $manage = static function (\WP_REST_Request $r) {
            return current_user_can('admincafe_manage_orders')
                && wp_verify_nonce($r->get_header('X-WP-Nonce'), 'wp_rest')
                ? true
                : new \WP_Error('forbidden', __('Order permission required.', 'fandoogh-rest'), ['status' => 403]);
        };
        register_rest_route('admincafe/v1', '/bootstrap', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'bootstrap']
        ]);
        register_rest_route('admincafe/v1', '/orders/table', [
            'methods' => 'POST',
            'permission_callback' => $public,
            'callback' => [$this, 'tableOrder']
        ]);
        register_rest_route('admincafe/v1', '/checkout', [
            'methods' => 'POST',
            'permission_callback' => $public,
            'callback' => [$this, 'checkout']
        ]);
        register_rest_route('admincafe/v1', '/orders/track', [
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'track']
        ]);
        register_rest_route('admincafe/v1', '/manage/orders', [
            [
                'methods' => 'GET',
                'permission_callback' => $manage,
                'callback' => [$this, 'listing']
            ],
            [
                'methods' => 'POST',
                'permission_callback' => $manage,
                'callback' => [$this, 'manual']
            ],
        ]);
        register_rest_route('admincafe/v1', '/manage/orders/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'permission_callback' => $manage,
                'callback' => [$this, 'detail']
            ],
            [
                'methods' => 'PATCH',
                'permission_callback' => $manage,
                'callback' => [$this, 'update']
            ],
        ]);
    }

    private function response(array $data): \WP_REST_Response
    {
        $response = new \WP_REST_Response($data);
        $response->header('Cache-Control', 'no-store, private, max-age=0');
        $response->header('Pragma', 'no-cache');
        return $response;
    }

    public function bootstrap(\WP_REST_Request $r): \WP_REST_Response|\WP_Error
    {
        $branchId = Checkout::resolveBranch($r->get_params(), false);
        if (is_wp_error($branchId)) { return $branchId; }
        return Branches::runFor($branchId, fn() => $this->bootstrapInBranch($r));
    }

    private function bootstrapInBranch(\WP_REST_Request $r): \WP_REST_Response|\WP_Error
    {
        $token = Security::token();
        $language = Checkout::language($r->get_param('lang'));
        if (is_wp_error($language)) {
            return $language;
        }
        Language::activate($language);
        $table = null;
        if ($r->get_param('table')) {
            $table = Tables::context(sanitize_text_field($r->get_param('table')));
            if (is_wp_error($table)) {
                return $table;
            }
        }
        $menu = Catalog::menu($language);
        $descriptor = Language::supported()[$language];
        return $this->response([
            'branch_id' => Branches::current(),
            'branch' => Branches::publicData(Branches::get(Branches::current())),
            'branches' => array_values(array_map([Branches::class, 'publicData'], array_filter(Branches::all(), static fn($branch) => $branch['enabled']))),
            'settings' => Settings::publicSettings($language),
            'products' => $menu['products'],
            'categories' => $menu['categories'],
            'context' => $table,
            'language' => $language,
            'locale' => $descriptor['html_locale'],
            'direction' => Language::direction($language),
            'languages' => Language::descriptors(),
            'strings' => Language::strings($language),
            'ordering' => [
                'dine_in' => (bool) Settings::get('dine_in_enabled'),
                'pickup' => (bool) Settings::get('pickup_enabled'),
                'delivery' => (bool) Settings::get('delivery_enabled'),
                'paused' => (bool) Settings::get('ordering_paused')
            ],
            'csrf_token' => $token,
            'menu_url' => Branches::menuUrl(),
            'checkout_url' => add_query_arg(['lang' => $language, 'branch_id' => Branches::current()], wc_get_checkout_url()),
            'currency_symbol' => html_entity_decode(get_woocommerce_currency_symbol()),
            'wc_store_api_url' => rest_url('wc/store/v1/'),
            'wc_nonce' => wp_create_nonce('wc_store_api')
        ]);
    }

    public function tableOrder(\WP_REST_Request $r): \WP_REST_Response|\WP_Error
    {
        $limit = Security::throttle('table_order', 15, 60);
        if (is_wp_error($limit)) {
            return $limit;
        }
        $result = Orders::create((array) $r->get_json_params());
        return is_wp_error($result) ? $result : $this->response($result);
    }

    public function checkout(\WP_REST_Request $r): \WP_REST_Response|\WP_Error
    {
        $limit = Security::throttle('checkout', 30, 60);
        if (is_wp_error($limit)) {
            return $limit;
        }
        $branchId = Checkout::resolveBranch($r->get_params());
        if (is_wp_error($branchId)) { return $branchId; }
        $data = Checkout::choose(sanitize_key($r->get_param('channel') ?? ''), $r->get_param('language'), $branchId, $r->get_param('replace_cart') === true);
        return is_wp_error($data) ? $data : $this->response($data);
    }

    public function track(\WP_REST_Request $r): \WP_REST_Response|\WP_Error
    {
        $limit = Security::throttle('tracking', 60, 60);
        if (is_wp_error($limit)) {
            return $limit;
        }
        $token = (string) $r->get_param('token');
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return new \WP_Error('not_found', __('Order not found.', 'fandoogh-rest'), ['status' => 404]);
        }
        $orders = wc_get_orders(['limit' => 1, 'meta_query' => [['key' => '_admincafe_tracking_hash', 'value' => hash('sha256', $token)]]]);
        if (!$orders) {
            return new \WP_Error('not_found', __('Order not found.', 'fandoogh-rest'), ['status' => 404]);
        }
        $order = $orders[0];
        if ($r->get_param('branch_id') !== null && (int) $r->get_param('branch_id') !== Branches::orderBranch($order)) {
            return new \WP_Error('not_found', __('Order not found.', 'fandoogh-rest'), ['status' => 404]);
        }
        return $this->response([
            'branch_id' => Branches::orderBranch($order),
            'number' => $order->get_order_number(),
            'stage' => $order->get_meta('_admincafe_stage'),
            'payment_status' => $order->get_status()
        ]);
    }

    private function order(\WP_REST_Request $r): \WC_Order|\WP_Error
    {
        $order = wc_get_order(absint($r['id']));
        return $order instanceof \WC_Order && $order->get_meta('_admincafe_channel')
            && Branches::orderBranch($order) === Branches::current() && Branches::canAccess(Branches::orderBranch($order))
            ? $order
            : new \WP_Error('not_found', __('Order not found.', 'fandoogh-rest'), ['status' => 404]);
    }

    public function listing(\WP_REST_Request $r): \WP_REST_Response|\WP_Error
    {
        if (!Branches::canAccess(Branches::current())) { return new \WP_Error('forbidden', __('Order permission required.', 'fandoogh-rest'), ['status' => 403]); }
        $query = [['key' => '_admincafe_channel', 'compare' => 'EXISTS'], Orders::branchQuery()];
        if ($r->get_param('stage')) {
            $stage = sanitize_key($r->get_param('stage'));
            if (!array_key_exists($stage, Orders::transitions())) {
                return new \WP_Error('stage', __('Invalid stage.', 'fandoogh-rest'), ['status' => 400]);
            }
            $query[] = ['key' => '_admincafe_stage', 'value' => $stage];
        }
        if ($r->get_param('channel')) {
            $channel = sanitize_key($r->get_param('channel'));
            if (!in_array($channel, [
                'table',
                'counter',
                'pickup',
                'delivery'
            ], true)) {
                return new \WP_Error('channel', __('Invalid channel.', 'fandoogh-rest'), ['status' => 400]);
            }
            $query[] = ['key' => '_admincafe_channel', 'value' => $channel];
        }
        $result = wc_get_orders([
            'limit' => 25,
            'page' => max(1, absint($r->get_param('page'))),
            'paginate' => true,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $query,
            'status' => array_keys(wc_get_order_statuses())
        ]);
        return $this->response([
            'orders' => array_map([Orders::class, 'detail'], $result->orders),
            'total' => $result->total,
            'pages' => $result->max_num_pages
        ]);
    }
    public function detail(\WP_REST_Request $r): \WP_REST_Response|\WP_Error
    {
        $order = $this->order($r);
        return is_wp_error($order) ? $order : $this->response(Orders::detail($order));
    }
    public function manual(\WP_REST_Request $r): \WP_REST_Response|\WP_Error
    {
        $user = wp_get_current_user();
        if (!current_user_can('admincafe_manage_settings') && !in_array('admincafe_cashier', $user->roles, true)) {
            return new \WP_Error('cashier', __('Cashier permission required.', 'fandoogh-rest'), ['status' => 403]);
        }
        $data = Orders::create((array) $r->get_json_params(), true);
        return is_wp_error($data) ? $data : $this->response($data);
    }
    public function update(\WP_REST_Request $r): \WP_REST_Response|\WP_Error
    {
        $order = $this->order($r);
        if (is_wp_error($order)) {
            return $order;
        }
        $lock = 'admincafe_order_lock_' . $order->get_id();
        $timestamp = get_option($lock);
        if ($timestamp && (int) $timestamp < time() - 300) {
            global $wpdb;
            // Compare the stored value atomically: never remove another worker's newly acquired lock.
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $lock, (string) $timestamp));
            wp_cache_delete($lock, 'options');
            wp_cache_delete('notoptions', 'options');
        }
        // Serialize settlement/refund requests so a double click cannot pay or refund twice.
        $lockTime = time();
        if (!add_option($lock, $lockTime, '', false)) {
            return new \WP_Error('order_busy', __('This order is being updated. Retry shortly.', 'fandoogh-rest'), ['status' => 409]);
        }
        try {
            $order = wc_get_order($order->get_id());
            $data = Orders::update($order, (array) $r->get_json_params());
            return is_wp_error($data) ? $data : $this->response($data);
        } finally {
            global $wpdb;
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $lock, (string) $lockTime));
            wp_cache_delete($lock, 'options');
            wp_cache_delete('notoptions', 'options');
        }
    }
}
