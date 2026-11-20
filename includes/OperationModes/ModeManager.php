<?php
namespace RMM\OperationModes;

if (!defined('ABSPATH')) {
    exit;
}

class ModeManager {

    const TABLE_COOKIE = 'rmm_table_number';

    public function __construct() {
        add_action('init', [$this, 'init_session_and_cookies']);
        add_action('template_redirect', [$this, 'handle_view_only_redirects']);

        // View-only behavior
        add_filter('woocommerce_is_purchasable', [$this, 'filter_is_purchasable'], 10, 2);
        add_filter('woocommerce_loop_add_to_cart_link', [$this, 'filter_loop_add_to_cart_button'], 10, 3);
        add_filter('woocommerce_get_price_html', [$this, 'filter_price_html'], 10, 2);

        // Dine-in table checkout optimizations
        add_filter('woocommerce_checkout_fields', [$this, 'filter_dine_in_checkout_fields'], 20);
        add_action('woocommerce_checkout_create_order', [$this, 'save_table_to_order'], 10, 2);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'display_table_in_admin_order'], 10, 1);
        add_filter('woocommerce_order_item_name', [$this, 'display_dine_in_badge_in_order'], 10, 2);

        // Minimum order check for online delivery
        add_action('woocommerce_check_cart_items', [$this, 'validate_min_order_amount']);
    }

    /**
     * Store table number in cookie if present in query param (?table=5)
     */
    public function init_session_and_cookies() {
        if (!empty($_GET['table'])) {
            $table_no = absint($_GET['table']);
            if ($table_no > 0 && !headers_sent()) {
                setcookie(self::TABLE_COOKIE, $table_no, time() + 86400, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false);
            }
        }
    }

    /**
     * Get current table number (if dine-in)
     */
    public static function get_current_table() {
        if (!empty($_GET['table'])) {
            return absint($_GET['table']);
        }
        if (!empty($_COOKIE[self::TABLE_COOKIE])) {
            return absint($_COOKIE[self::TABLE_COOKIE]);
        }
        return 0;
    }

    /**
     * Get active mode
     */
    public static function get_current_mode() {
        return \RMM\Branches\BranchContext::get_branch_mode();
    }

    /**
     * Redirect Cart & Checkout in View-Only mode
     */
    public function handle_view_only_redirects() {
        if (self::get_current_mode() === 'view_only') {
            if (is_cart() || is_checkout()) {
                wp_safe_redirect(home_url('/'));
                exit;
            }
        }
    }

    /**
     * Filter purchasable status in View-Only mode
     */
    public function filter_is_purchasable($purchasable, $product) {
        if (self::get_current_mode() === 'view_only') {
            return false;
        }
        return $purchasable;
    }

    /**
     * Replace Add to Cart button in Loop when in View-Only mode
     */
    public function filter_loop_add_to_cart_button($button_html, $product, $args = []) {
        $mode = self::get_current_mode();
        if ($mode !== 'view_only') {
            return $button_html;
        }

        $action_btn = \RMM\Core\Settings::get('catalog_action_btn', 'whatsapp');
        $phone      = \RMM\Core\Settings::get('phone_number', '');
        $whatsapp   = \RMM\Core\Settings::get('whatsapp_number', '');

        // Check if current branch has specific phone
        $active_branch_id = \RMM\Branches\BranchContext::get_active_branch_id();
        $branch_phone = get_term_meta($active_branch_id, '_rmm_phone', true);
        if ($branch_phone) {
            $phone = $branch_phone;
        }

        if ($action_btn === 'whatsapp' && !empty($whatsapp)) {
            $msg = rawurlencode(sprintf(__('سلام، مایل به استعلام و سفارش غذای «%s» هستم.', 'restaurant-menu-manager'), $product->get_name()));
            $link = "https://wa.me/{$whatsapp}?text={$msg}";
            return sprintf(
                '<a href="%s" target="_blank" class="button rmm-action-btn rmm-btn-whatsapp" style="background:#25D366; color:#fff; border-radius:6px; display:inline-flex; align-items:center; gap:6px;"><span>💬</span> %s</a>',
                esc_url($link),
                __('سفارش در واتساپ', 'restaurant-menu-manager')
            );
        }

        if ($action_btn === 'call' && !empty($phone)) {
            return sprintf(
                '<a href="tel:%s" class="button rmm-action-btn rmm-btn-call" style="background:#0284c7; color:#fff; border-radius:6px; display:inline-flex; align-items:center; gap:6px;"><span>📞</span> %s</a>',
                esc_attr($phone),
                __('تماس با رستوران', 'restaurant-menu-manager')
            );
        }

        return ''; // Hide completely if none
    }

    /**
     * Filter price display in View-Only mode if hide price is enabled
     */
    public function filter_price_html($price_html, $product) {
        $mode = self::get_current_mode();
        if ($mode === 'view_only') {
            $hide = \RMM\Core\Settings::get('catalog_hide_price', 'no');
            if ($hide === 'yes') {
                return '<span class="rmm-price-hidden">' . esc_html__('قیمت منو', 'restaurant-menu-manager') . '</span>';
            }
        }
        return $price_html;
    }

    /**
     * Streamline checkout fields for Dine-In mode
     */
    public function filter_dine_in_checkout_fields($fields) {
        $mode = self::get_current_mode();
        $table_no = self::get_current_table();

        if ($mode === 'dine_in' || $table_no > 0) {
            // Remove unnecessary physical shipping/billing fields
            unset($fields['billing']['billing_company']);
            unset($fields['billing']['billing_country']);
            unset($fields['billing']['billing_address_1']);
            unset($fields['billing']['billing_address_2']);
            unset($fields['billing']['billing_city']);
            unset($fields['billing']['billing_state']);
            unset($fields['billing']['billing_postcode']);
            unset($fields['shipping']);

            // Add Table Number Field
            $fields['billing']['rmm_table_number'] = [
                'type'        => 'text',
                'label'       => __('شماره میز شما', 'restaurant-menu-manager'),
                'required'    => true,
                'class'       => ['form-row-wide'],
                'clear'       => true,
                'default'     => $table_no ? $table_no : '',
                'priority'    => 25,
            ];
        }

        return $fields;
    }

    /**
     * Save Table number into WooCommerce Order
     */
    public function save_table_to_order($order, $data) {
        $table_no = 0;
        if (!empty($_POST['rmm_table_number'])) {
            $table_no = absint($_POST['rmm_table_number']);
        } elseif (self::get_current_table() > 0) {
            $table_no = self::get_current_table();
        }

        if ($table_no > 0) {
            $order->update_meta_data('_rmm_table_number', $table_no);
            $order->update_meta_data('_rmm_order_type', 'dine_in');
        } else {
            $order->update_meta_data('_rmm_order_type', 'delivery');
        }
    }

    /**
     * Display Table Number in WP-Admin Order Screen
     */
    public function display_table_in_admin_order($order) {
        $table_no = $order->get_meta('_rmm_table_number');
        $branch_id = $order->get_meta('_rmm_order_branch_id');

        if ($table_no || $branch_id) {
            echo '<div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px; margin-top:15px;">';
            echo '<h4 style="margin:0 0 6px; color:#166534;">🍽️ ' . esc_html__('اطلاعات رستورانی سفارش', 'restaurant-menu-manager') . '</h4>';
            if ($table_no) {
                echo '<p style="margin:2px 0;"><strong>' . esc_html__('سفارش سر میز (Dine-in):', 'restaurant-menu-manager') . '</strong> <span style="background:#22c55e; color:#fff; padding:2px 8px; border-radius:4px; font-weight:bold;">' . sprintf(esc_html__('میز شماره %d', 'restaurant-menu-manager'), $table_no) . '</span></p>';
            }
            if ($branch_id) {
                $term = get_term($branch_id, \RMM\Branches\BranchTaxonomy::TAXONOMY);
                if ($term && !is_wp_error($term)) {
                    echo '<p style="margin:2px 0;"><strong>' . esc_html__('شعبه متصدی:', 'restaurant-menu-manager') . '</strong> ' . esc_html($term->name) . '</p>';
                }
            }
            echo '</div>';
        }
    }

    public function display_dine_in_badge_in_order($item_name, $item) {
        return $item_name;
    }

    /**
     * Check minimum order amount for current branch
     */
    public function validate_min_order_amount() {
        if (is_admin() || !is_cart() && !is_checkout()) {
            return;
        }

        $branch_id = \RMM\Branches\BranchContext::get_active_branch_id();
        if (!$branch_id) {
            return;
        }

        $min_order = floatval(get_term_meta($branch_id, '_rmm_min_order', true));
        if ($min_order > 0 && WC()->cart) {
            $cart_total = WC()->cart->get_subtotal();
            if ($cart_total < $min_order) {
                wc_add_notice(sprintf(
                    __('حداقل مبلغ سفارش برای این شعبه %s است. لطفاً برای تکمیل خرید، آیتم‌های دیگری به سبد اضافه کنید.', 'restaurant-menu-manager'),
                    wc_price($min_order)
                ), 'error');
            }
        }
    }
}
