<?php
namespace RMM\Branches;

if (!defined('ABSPATH')) {
    exit;
}

class BranchContext {

    const COOKIE_NAME = 'rmm_selected_branch';
    private static $current_branch_id = null;

    public function __construct() {
        // Init branch context early
        add_action('init', [$this, 'init_branch_from_request'], 20);
        add_action('rest_api_init', [$this, 'register_rest_endpoints']);

        // WooCommerce Price and Stock Filters
        add_filter('woocommerce_product_get_price', [$this, 'filter_product_price'], 10, 2);
        add_filter('woocommerce_product_get_regular_price', [$this, 'filter_product_regular_price'], 10, 2);
        add_filter('woocommerce_product_get_sale_price', [$this, 'filter_product_sale_price'], 10, 2);
        add_filter('woocommerce_product_is_in_stock', [$this, 'filter_product_stock'], 10, 2);

        // Cart validation and recalculation
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_add_to_cart'], 10, 3);
        add_filter('woocommerce_add_cart_item_data', [$this, 'attach_branch_to_cart_item'], 10, 3);
        add_action('woocommerce_before_calculate_totals', [$this, 'enforce_cart_branch_prices'], 20, 1);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_branch_to_order_item'], 10, 4);

        // Cleanup hooks
        add_action('before_delete_post', function ($post_id) {
            if (get_post_type($post_id) === 'product') {
                InventoryRepository::delete_for_product($post_id);
            }
        });
        add_action('delete_rmm_branch', function ($term_id) {
            InventoryRepository::delete_for_branch($term_id);
        });
    }

    /**
     * Resolve current branch ID from URL param, Cookie, or Default
     */
    public static function get_active_branch_id() {
        if (!taxonomy_exists(BranchTaxonomy::TAXONOMY)) {
            return 0; // Taxonomy not registered yet; do not cache 0 prematurely
        }

        if (self::$current_branch_id !== null) {
            return self::$current_branch_id;
        }

        // 1. Check URL query param (?branch=12). This is a preview for the
        // current request only: writing the cookie is exclusively done by the
        // explicit POST /set-branch endpoint, so GET has no side effects.
        if (!empty($_GET['branch'])) {
            $branch_param = absint($_GET['branch']);
            if (term_exists($branch_param, BranchTaxonomy::TAXONOMY)) {
                self::$current_branch_id = $branch_param;
                return self::$current_branch_id;
            }
        }

        // 2. Check Cookie
        if (!empty($_COOKIE[self::COOKIE_NAME])) {
            $cookie_branch = absint($_COOKIE[self::COOKIE_NAME]);
            if (term_exists($cookie_branch, BranchTaxonomy::TAXONOMY)) {
                self::$current_branch_id = $cookie_branch;
                return self::$current_branch_id;
            }
        }

        // 3. Fallback: First published branch
        $branches = get_terms([
            'taxonomy'   => BranchTaxonomy::TAXONOMY,
            'hide_empty' => false,
            'number'     => 1,
            'orderby'    => 'term_id',
            'order'      => 'ASC',
        ]);

        if (!empty($branches) && !is_wp_error($branches)) {
            self::$current_branch_id = $branches[0]->term_id;
            return self::$current_branch_id;
        }

        self::$current_branch_id = 0;
        return 0;
    }

    /**
     * Get operation mode of branch (or fallback to global)
     */
    public static function get_branch_mode($branch_id = null) {
        if ($branch_id === null) {
            $branch_id = self::get_active_branch_id();
        }

        if ($branch_id) {
            $mode = get_term_meta($branch_id, '_rmm_mode', true);
            if ($mode && $mode !== 'global') {
                return $mode;
            }
        }

        return \RMM\Core\Settings::get('operation_mode', 'online_delivery');
    }

    /**
     * Set branch cookie securely (HttpOnly and SameSite flags)
     */
    public static function set_branch_cookie($branch_id) {
        if (!headers_sent()) {
            $options = [
                'expires'  => time() + (86400 * 30),
                'path'     => COOKIEPATH ?: '/',
                'domain'   => COOKIE_DOMAIN ?: '',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ];
            setcookie(self::COOKIE_NAME, absint($branch_id), $options);
        }
    }

    public function init_branch_from_request() {
        self::get_active_branch_id();
    }

    /**
     * REST Endpoints
     */
    public function register_rest_endpoints() {
        // Read-only branch menu and inventory sync (No side-effects on GET)
        register_rest_route('rmm/v1', '/branch-menu-sync', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_sync_branch_menu'],
            'permission_callback' => '__return_true',
            'args'                => [
                'branch_id' => [
                    'required'          => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);

        // Explicit POST endpoint to set active branch cookie safely
        register_rest_route('rmm/v1', '/set-branch', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_set_branch'],
            'permission_callback' => '__return_true',
            'args'                => [
                'branch_id' => [
                    'required'          => true,
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
    }

    /**
     * GET endpoint: sync branch info & inventory without side-effects
     */
    public function rest_sync_branch_menu(\WP_REST_Request $request) {
        $branch_id = absint($request->get_param('branch_id'));
        $branch = get_term($branch_id, BranchTaxonomy::TAXONOMY);

        if (!$branch || is_wp_error($branch)) {
            return new \WP_Error('rmm_invalid_branch', __('شعبه نامعتبر است', 'fondogh-online-menu'), ['status' => 404]);
        }

        $inventory = InventoryRepository::get_branch_inventory($branch_id);
        $is_open   = get_term_meta($branch_id, '_rmm_is_open', true) !== '0';
        $mode      = self::get_branch_mode($branch_id);

        return rest_ensure_response([
            'success'   => true,
            'branch'    => [
                'id'       => $branch->term_id,
                'name'     => $branch->name,
                'is_open'  => $is_open,
                'mode'     => $mode,
                'phone'    => get_term_meta($branch_id, '_rmm_phone', true),
                'address'  => get_term_meta($branch_id, '_rmm_address', true),
                'hours'    => get_term_meta($branch_id, '_rmm_hours', true),
            ],
            'inventory' => $inventory,
        ]);
    }

    /**
     * POST endpoint: switch active branch and set secure cookie
     */
    public function rest_set_branch(\WP_REST_Request $request) {
        $branch_id = absint($request->get_param('branch_id'));
        $branch = get_term($branch_id, BranchTaxonomy::TAXONOMY);

        if (!$branch || is_wp_error($branch)) {
            return new \WP_Error('rmm_invalid_branch', __('شعبه نامعتبر است', 'fondogh-online-menu'), ['status' => 404]);
        }

        self::set_branch_cookie($branch_id);

        return rest_ensure_response([
            'success'   => true,
            'branch_id' => $branch_id,
            'message'   => __('شعبه با موفقیت انتخاب شد.', 'fondogh-online-menu'),
        ]);
    }

    /**
     * WooCommerce Price Filter: Regular Price
     */
    public function filter_product_regular_price($price, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $price;
        }

        $branch_id = self::get_active_branch_id();
        if (!$branch_id) {
            return $price;
        }

        $item = InventoryRepository::get_item($product->get_id(), $branch_id);
        if ($item && $item->regular_price !== null && $item->regular_price > 0) {
            return (string) $item->regular_price;
        }

        return $price;
    }

    /**
     * WooCommerce Price Filter: Sale Price
     */
    public function filter_product_sale_price($price, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $price;
        }

        $branch_id = self::get_active_branch_id();
        if (!$branch_id) {
            return $price;
        }

        $item = InventoryRepository::get_item($product->get_id(), $branch_id);
        if ($item) {
            if ($item->sale_price !== null && $item->sale_price > 0) {
                return (string) $item->sale_price;
            }
            if ($item->regular_price !== null && $item->regular_price > 0) {
                return '';
            }
        }

        return $price;
    }

    /**
     * WooCommerce Price Filter: Active Price (regular or sale)
     */
    public function filter_product_price($price, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $price;
        }

        $branch_id = self::get_active_branch_id();
        if (!$branch_id) {
            return $price;
        }

        $item = InventoryRepository::get_item($product->get_id(), $branch_id);
        if ($item) {
            if ($item->sale_price !== null && $item->sale_price > 0) {
                return (string) $item->sale_price;
            } elseif ($item->regular_price !== null && $item->regular_price > 0) {
                return (string) $item->regular_price;
            }
        }

        return $price;
    }

    /**
     * WooCommerce Stock Status Filter
     */
    public function filter_product_stock($in_stock, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $in_stock;
        }

        $branch_id = self::get_active_branch_id();
        if (!$branch_id) {
            return $in_stock;
        }

        $item = InventoryRepository::get_item($product->get_id(), $branch_id);
        if ($item) {
            if (!$item->is_available || $item->stock_status === 'outofstock') {
                return false;
            } else {
                return true;
            }
        }

        return $in_stock;
    }

    /**
     * Validate Add to Cart against current branch
     */
    public function validate_add_to_cart($passed, $product_id, $quantity) {
        $branch_id = self::get_active_branch_id();
        $mode = self::get_branch_mode($branch_id);

        // Block if mode is view_only
        if ($mode === 'view_only') {
            wc_add_notice(__('این منو صرفاً برای مشاهده است و سفارش‌گیری آنلاین فعال نیست.', 'fondogh-online-menu'), 'error');
            return false;
        }

        // Check if branch is open
        $is_open = get_term_meta($branch_id, '_rmm_is_open', true);
        if ($is_open === '0') {
            wc_add_notice(__('متأسفانه این شعبه در حال حاضر تعطیل است و سفارش جدید ثبت نمی‌کند.', 'fondogh-online-menu'), 'error');
            return false;
        }

        // Check branch inventory stock
        $item = InventoryRepository::get_item($product_id, $branch_id);
        if ($item && (!$item->is_available || $item->stock_status === 'outofstock')) {
            wc_add_notice(__('این غذا در حال حاضر در شعبه انتخابی شما تمام شده است.', 'fondogh-online-menu'), 'error');
            return false;
        }

        // Ensure cart doesn't mix items from different branches
        if (WC()->cart && !WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (!empty($cart_item['_rmm_branch_id']) && $cart_item['_rmm_branch_id'] != $branch_id) {
                    wc_add_notice(__('سبد خرید شما حاوی آیتم‌هایی از شعبه دیگری است. لطفاً ابتدا سبد خرید را خالی کنید یا به همان شعبه بازگردید.', 'fondogh-online-menu'), 'error');
                    return false;
                }
            }
        }

        return $passed;
    }

    /**
     * Attach current branch to cart item data
     */
    public function attach_branch_to_cart_item($cart_item_data, $product_id, $variation_id) {
        $cart_item_data['_rmm_branch_id'] = self::get_active_branch_id();
        return $cart_item_data;
    }

    /**
     * Enforce branch price on cart calculation
     */
    public function enforce_cart_branch_prices($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            $branch_id = $cart_item['_rmm_branch_id'] ?? self::get_active_branch_id();
            if (!$branch_id) {
                continue;
            }

            $product_id = $cart_item['product_id'];
            $item = InventoryRepository::get_item($product_id, $branch_id);

            if ($item) {
                $custom_price = null;
                if ($item->sale_price !== null && $item->sale_price > 0) {
                    $custom_price = $item->sale_price;
                } elseif ($item->regular_price !== null && $item->regular_price > 0) {
                    $custom_price = $item->regular_price;
                }

                if ($custom_price !== null && isset($cart_item['data']) && is_object($cart_item['data'])) {
                    // Set base branch price here; ModifiersEngine (priority 25) adds portion/addons idempotently
                    $cart_item['data']->set_price(floatval($custom_price));
                }
            }
        }
    }

    /**
     * Save branch meta to WC order line item
     */
    public function save_branch_to_order_item($item, $cart_item_key, $values, $order) {
        if (!empty($values['_rmm_branch_id'])) {
            $branch_id = absint($values['_rmm_branch_id']);
            $branch_term = get_term($branch_id, BranchTaxonomy::TAXONOMY);
            if ($branch_term && !is_wp_error($branch_term)) {
                $item->add_meta_data(__('شعبه', 'fondogh-online-menu'), $branch_term->name, true);
                $item->add_meta_data('_rmm_branch_id', $branch_id, true);
                $order->update_meta_data('_rmm_order_branch_id', $branch_id);
            }
        }
    }
}
