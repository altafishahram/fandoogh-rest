<?php
namespace RMM\FrontendDashboard;

if (!defined('ABSPATH')) {
    exit;
}

class AuthController {

    public function __construct() {
        add_action('init', [$this, 'handle_auth_actions']);
    }

    /**
     * Check if user is logged in and belongs to allowed roles
     */
    public static function is_authorized($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }

        if (!$user || !$user->exists()) {
            return false;
        }

        // Administrators and managers always authorized
        if (user_can($user, 'manage_options') || 
            user_can($user, 'manage_woocommerce') || 
            user_can($user, 'manage_restaurant_menu') || 
            user_can($user, 'view_kitchen_orders')) {
            return true;
        }

        $allowed_roles = ['administrator', 'shop_manager', 'restaurant_manager', 'kitchen_staff'];
        return (bool) array_intersect($allowed_roles, (array) $user->roles);
    }

    /**
     * Global managers (administrator / shop manager) may see every branch.
     * Branch-scoped roles are always restricted to their assigned branches,
     * even if a legacy installation left them with manage_woocommerce.
     *
     * @param int $user_id
     * @return bool
     */
    private static function is_global_manager($user_id) {
        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        $user = get_userdata($user_id);
        if (!$user || is_wp_error($user)) {
            return false;
        }

        $roles = (array) $user->roles;
        if (in_array('restaurant_manager', $roles, true) || in_array('kitchen_staff', $roles, true)) {
            return false;
        }

        return user_can($user_id, 'manage_woocommerce');
    }

    /**
     * Get branch IDs assigned to current user (or all if admin/shop_manager)
     *
     * @param int|null $user_id
     * @return int[]
     */
    public static function get_user_branches($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return [];
        }

        if (self::is_global_manager($user_id)) {
            // Only administrators & shop managers can access all branches.
            $terms = get_terms([
                'taxonomy'   => \RMM\Branches\BranchTaxonomy::TAXONOMY,
                'hide_empty' => false,
                'fields'     => 'ids',
            ]);
            return (!empty($terms) && !is_wp_error($terms)) ? array_map('absint', $terms) : [];
        }

        // Restaurant manager or kitchen staff: check branches where _rmm_manager_id matches
        $terms = get_terms([
            'taxonomy'   => \RMM\Branches\BranchTaxonomy::TAXONOMY,
            'hide_empty' => false,
            'fields'     => 'ids',
            'meta_query' => [
                [
                    'key'   => '_rmm_manager_id',
                    'value' => $user_id,
                ]
            ]
        ]);
        return (!empty($terms) && !is_wp_error($terms)) ? array_map('absint', $terms) : [];
    }

    /**
     * Check if user is allowed to view/operate on a given branch
     *
     * @param int $branch_id
     * @param int|null $user_id
     * @return bool
     */
    public static function user_can_access_branch($branch_id, $user_id = null) {
        $branch_id = absint($branch_id);
        if (!$branch_id) {
            return false;
        }

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        if (self::is_global_manager($user_id)) {
            return true;
        }

        $allowed = self::get_user_branches($user_id);
        return in_array($branch_id, $allowed, true);
    }

    /**
     * Check if user can view kitchen orders
     */
    public static function can_view_orders($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        if (!$user || !$user->exists()) {
            return false;
        }
        return user_can($user, 'manage_options') || 
               user_can($user, 'manage_woocommerce') || 
               user_can($user, 'manage_restaurant_menu') || 
               user_can($user, 'view_kitchen_orders') ||
               in_array('kitchen_staff', (array) $user->roles, true);
    }

    /**
     * Check if user can update order status (Kitchen or Manager)
     */
    public static function can_update_order_status($user = null) {
        return self::can_view_orders($user);
    }

    /**
     * Check if user can manage menu items and categories (Admin & Restaurant Manager only)
     * Kitchen staff is strictly forbidden.
     */
    public static function can_manage_menu($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        if (!$user || !$user->exists()) {
            return false;
        }
        if (in_array('kitchen_staff', (array) $user->roles, true) && 
            !user_can($user, 'manage_options') && 
            !user_can($user, 'manage_restaurant_menu')) {
            return false;
        }
        return user_can($user, 'manage_options') || 
               user_can($user, 'manage_woocommerce') || 
               user_can($user, 'manage_restaurant_menu');
    }

    /**
     * Check if user can update branch inventory & pricing
     */
    public static function can_manage_inventory($user = null) {
        return self::can_manage_menu($user);
    }

    /**
     * Check if user can view the catalogue-level REST resources
     * (stats, products, categories, branches). Kitchen display users are
     * deliberately excluded from these read-only endpoints.
     */
    public static function can_view_catalog($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        if (!$user || !$user->exists()) {
            return false;
        }

        $roles = (array) $user->roles;
        if (in_array('kitchen_staff', $roles, true) && !user_can($user, 'manage_options')) {
            return false;
        }

        return user_can($user, 'manage_options') ||
               user_can($user, 'manage_woocommerce') ||
               user_can($user, 'manage_restaurant_menu') ||
               user_can($user, 'view_kitchen_orders');
    }

    /**
     * Check if user can configure branch metadata
     */
    public static function can_manage_branches($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        if (!$user || !$user->exists()) {
            return false;
        }

        $roles = (array) $user->roles;
        if (in_array('restaurant_manager', $roles, true) || in_array('kitchen_staff', $roles, true)) {
            // Branch-scoped roles cannot create/edit branch records globally.
            return false;
        }

        return user_can($user, 'manage_options') || user_can($user, 'manage_woocommerce');
    }

    /**
     * Check if user can upload media files
     */
    public static function can_upload_media($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        if (!$user || !$user->exists()) {
            return false;
        }
        if (in_array('kitchen_staff', (array) $user->roles, true) && !user_can($user, 'manage_options')) {
            return false;
        }
        return user_can($user, 'upload_files') || user_can($user, 'manage_options') || user_can($user, 'manage_restaurant_menu');
    }

    /**
     * Handle login & logout POST requests on portal
     */
    public function handle_auth_actions() {
        // Handle Logout
        if (isset($_GET['rmm_action']) && $_GET['rmm_action'] === 'logout') {
            check_admin_referer('rmm_portal_logout');
            wp_logout();
            $slug = \RMM\Core\Settings::get('dashboard_slug', 'restaurant-portal');
            wp_safe_redirect(home_url('/' . $slug . '/'));
            exit;
        }

        // Handle Login
        if (isset($_POST['rmm_portal_login_submit'])) {
            if (!isset($_POST['rmm_login_nonce']) || !wp_verify_nonce($_POST['rmm_login_nonce'], 'rmm_portal_login')) {
                wp_die(__('اعتبارسنجی امنیتی ناموفق بود.', 'fondogh-online-menu'));
            }

            $credentials = [
                'user_login'    => sanitize_user($_POST['log'] ?? ''),
                'user_password' => $_POST['pwd'] ?? '',
                'remember'      => !empty($_POST['rememberme']),
            ];

            $user = wp_signon($credentials, is_ssl());

            $slug = \RMM\Core\Settings::get('dashboard_slug', 'restaurant-portal');
            $redirect_url = home_url('/' . $slug . '/');

            if (is_wp_error($user)) {
                $redirect_url = add_query_arg('login_error', urlencode($user->get_error_message()), $redirect_url);
            } else {
                if (!self::is_authorized($user)) {
                    wp_logout();
                    $redirect_url = add_query_arg('login_error', urlencode(__('حساب کاربری شما دسترسی به پنل مدیریت رستوران را ندارد.', 'fondogh-online-menu')), $redirect_url);
                }
            }

            wp_safe_redirect($redirect_url);
            exit;
        }
    }
}
