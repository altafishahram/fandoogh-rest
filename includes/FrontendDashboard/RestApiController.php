<?php
namespace RMM\FrontendDashboard;

if (!defined('ABSPATH')) {
    exit;
}

class RestApiController {

    const NAMESPACE = 'rmm/v1';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Explicit role/route matrix. A single boolean permission for every route is
     * not acceptable: kitchen staff may only read orders and update their status.
     *
     | Route | Permission callback |
     | --- | --- |
     | stats / products / categories / branches (read) | can_view |
     | toggle-stock / update-price | can_manage_inventory |
     | save-product / delete-product / category writes | can_manage_menu |
     | save-branch | can_manage_branches |
     | live-orders / order-receipt | can_view_orders |
     | update-order-status | can_update_orders |
     | toggle-branch | can_manage_inventory |
     | ai-translate | can_manage_menu |
     | upload-image | can_upload |
     */
    public function register_routes() {
        // Stats
        register_rest_route(self::NAMESPACE, '/portal/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_stats'],
            'permission_callback' => [$this, 'can_view'],
        ]);

        // Products for Menu & Stock manager
        register_rest_route(self::NAMESPACE, '/portal/products', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_products'],
            'permission_callback' => [$this, 'can_view'],
        ]);

        // Quick toggle stock status
        register_rest_route(self::NAMESPACE, '/portal/toggle-stock', [
            'methods'             => 'POST',
            'callback'            => [$this, 'toggle_stock'],
            'permission_callback' => [$this, 'can_manage_inventory'],
        ]);

        // Quick update price
        register_rest_route(self::NAMESPACE, '/portal/update-price', [
            'methods'             => 'POST',
            'callback'            => [$this, 'update_price'],
            'permission_callback' => [$this, 'can_manage_inventory'],
        ]);

        // Create or Edit Food item
        register_rest_route(self::NAMESPACE, '/portal/save-product', [
            'methods'             => 'POST',
            'callback'            => [$this, 'save_product'],
            'permission_callback' => [$this, 'can_manage_menu'],
        ]);

        // Delete Food item
        register_rest_route(self::NAMESPACE, '/portal/delete-product', [
            'methods'             => 'POST',
            'callback'            => [$this, 'delete_product'],
            'permission_callback' => [$this, 'can_manage_menu'],
        ]);

        // Product categories list
        register_rest_route(self::NAMESPACE, '/portal/categories', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_categories'],
            'permission_callback' => [$this, 'can_view'],
        ]);

        // Reorder Categories (Prioritization)
        register_rest_route(self::NAMESPACE, '/portal/reorder-categories', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reorder_categories'],
            'permission_callback' => [$this, 'can_manage_menu'],
        ]);

        // Save Category (Create or Edit)
        register_rest_route(self::NAMESPACE, '/portal/save-category', [
            'methods'             => 'POST',
            'callback'            => [$this, 'save_category'],
            'permission_callback' => [$this, 'can_manage_menu'],
        ]);

        // Delete Category
        register_rest_route(self::NAMESPACE, '/portal/delete-category', [
            'methods'             => 'POST',
            'callback'            => [$this, 'delete_category'],
            'permission_callback' => [$this, 'can_manage_menu'],
        ]);

        // Branches list
        register_rest_route(self::NAMESPACE, '/portal/branches', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_portal_branches'],
            'permission_callback' => [$this, 'can_view'],
        ]);

        // Save Branch (Create or Edit)
        register_rest_route(self::NAMESPACE, '/portal/save-branch', [
            'methods'             => 'POST',
            'callback'            => [$this, 'save_branch'],
            'permission_callback' => [$this, 'can_manage_branches'],
        ]);

        // Live orders feed for Kitchen Display (KDS)
        register_rest_route(self::NAMESPACE, '/portal/live-orders', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_live_orders'],
            'permission_callback' => [$this, 'can_view_orders'],
        ]);

        // Update order status (Processing -> Completed etc.)
        register_rest_route(self::NAMESPACE, '/portal/update-order-status', [
            'methods'             => 'POST',
            'callback'            => [$this, 'update_order_status'],
            'permission_callback' => [$this, 'can_update_orders'],
        ]);

        // Toggle branch open/closed
        register_rest_route(self::NAMESPACE, '/portal/toggle-branch', [
            'methods'             => 'POST',
            'callback'            => [$this, 'toggle_branch_open_status'],
            'permission_callback' => [$this, 'can_manage_inventory'],
        ]);

        // Get single order for 80mm thermal receipt
        register_rest_route(self::NAMESPACE, '/portal/order-receipt', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_order_receipt_data'],
            'permission_callback' => [$this, 'can_view_orders'],
        ]);

        // AI Translation endpoint
        register_rest_route(self::NAMESPACE, '/portal/ai-translate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_ai_translate'],
            'permission_callback' => [$this, 'can_manage_menu'],
        ]);

        // Upload Image endpoint (Featured & Gallery)
        register_rest_route(self::NAMESPACE, '/portal/upload-image', [
            'methods'             => 'POST',
            'callback'            => [$this, 'upload_image'],
            'permission_callback' => [$this, 'can_upload'],
        ]);
    }

    public function check_permission() {
        return AuthController::is_authorized();
    }

    public function can_view() {
        return AuthController::can_view_catalog();
    }

    public function can_manage_menu() {
        return AuthController::can_manage_menu();
    }

    public function can_manage_inventory() {
        return AuthController::can_manage_inventory();
    }

    public function can_manage_branches() {
        return AuthController::can_manage_branches();
    }

    public function can_view_orders() {
        return AuthController::can_view_orders();
    }

    public function can_update_orders() {
        return AuthController::can_update_order_status();
    }

    public function can_upload() {
        return AuthController::can_upload_media();
    }

    /**
     * Central branch guard. The client-supplied branch_id is a claim, not a
     * permission: it is always checked against AuthController::get_user_branches().
     *
     * @param \WP_REST_Request $request
     * @return int|\WP_Error
     */
    private function resolve_request_branch(\WP_REST_Request $request) {
        $branch_id = absint($request->get_param('branch_id'));
        if (!$branch_id) {
            $branch_id = absint(\RMM\Branches\BranchContext::get_active_branch_id());
        }

        if (!$branch_id || !term_exists($branch_id, \RMM\Branches\BranchTaxonomy::TAXONOMY)) {
            return new \WP_Error('rmm_invalid_branch', __('شعبه نامعتبر است.', 'fondogh-online-menu'), ['status' => 404]);
        }

        if (!current_user_can('manage_options') && !AuthController::user_can_access_branch($branch_id)) {
            return new \WP_Error('rmm_forbidden_branch', __('شما به این شعبه دسترسی ندارید.', 'fondogh-online-menu'), ['status' => 403]);
        }

        return $branch_id;
    }

    /**
     * Verify ownership of an order based on its stored branch, not on a param.
     *
     * @param \WC_Order|false $order
     * @return true|\WP_Error
     */
    private function assert_order_branch($order) {
        if (!$order) {
            return new \WP_Error('rmm_order_not_found', __('سفارش یافت نشد.', 'fondogh-online-menu'), ['status' => 404]);
        }

        $branch_id = absint($order->get_meta('_rmm_order_branch_id'));
        if (!$branch_id) {
            // Legacy orders without a branch are visible to global managers only.
            if (!AuthController::is_global_manager()) {
                return new \WP_Error('rmm_forbidden_order', __('شما به این سفارش دسترسی ندارید.', 'fondogh-online-menu'), ['status' => 403]);
            }
            return true;
        }

        if (!AuthController::is_global_manager() && !AuthController::user_can_access_branch($branch_id)) {
            return new \WP_Error('rmm_forbidden_order', __('شما به این سفارش دسترسی ندارید.', 'fondogh-online-menu'), ['status' => 403]);
        }

        return true;
    }

    /**
     * Verify a branch-scoped user can modify/delete an existing product.
     *
     * @param int $product_id
     * @return true|\WP_Error
     */
    private function assert_product_branch_access($product_id) {
        if (AuthController::is_global_manager()) {
            return true;
        }

        $prod_branches = array_values(array_filter(array_map('absint', (array) get_post_meta($product_id, '_rmm_branches', true))));
        if (empty($prod_branches)) {
            return new \WP_Error('rmm_forbidden_product', __('شما مجاز به تغییر یا حذف محصولات سراسری نیستید.', 'fondogh-online-menu'), ['status' => 403]);
        }

        foreach ($prod_branches as $b_id) {
            if (!AuthController::user_can_access_branch($b_id)) {
                return new \WP_Error('rmm_forbidden_product', __('شما به تمام شعبات این محصول دسترسی ندارید.', 'fondogh-online-menu'), ['status' => 403]);
            }
        }

        return true;
    }

    /**
     * Verify a product can receive a branch-specific inventory update.
     * Global managers may manage any product; branch-scoped users may only
     * update products assigned to the requested branch.
     *
     * @param int $product_id
     * @param int $branch_id
     * @return true|\WP_Error
     */
    private function assert_product_branch_inventory_access($product_id, $branch_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return new \WP_Error('rmm_product_not_found', __('محصول مورد نظر یافت نشد.', 'fondogh-online-menu'), ['status' => 404]);
        }

        if (AuthController::is_global_manager()) {
            return true;
        }

        $prod_branches = array_values(array_filter(array_map('absint', (array) get_post_meta($product_id, '_rmm_branches', true))));
        $has_inventory_row = (bool) \RMM\Branches\InventoryRepository::get_item($product_id, $branch_id);

        if (!in_array(absint($branch_id), $prod_branches, true) && !$has_inventory_row) {
            return new \WP_Error('rmm_forbidden_product_branch', __('این محصول به شعبه انتخاب‌شده اختصاص ندارد یا دسترسی آن برای شما مجاز نیست.', 'fondogh-online-menu'), ['status' => 403]);
        }

        return true;
    }

    /**
     * Dashboard Stats
     */
    public function get_stats(\WP_REST_Request $request) {
        $branch_id = $this->resolve_request_branch($request);
        if (is_wp_error($branch_id)) {
            return $branch_id;
        }

        // Count today's orders
        $today_start = date('Y-m-d 00:00:00');
        $args = [
            'date_created' => '>=' . strtotime($today_start),
            'limit'        => -1,
            'return'       => 'ids',
        ];

        if ($branch_id) {
            $args['meta_key']   = '_rmm_order_branch_id';
            $args['meta_value'] = $branch_id;
        }

        $order_ids = wc_get_orders($args);
        $total_orders = count($order_ids);
        $pending_orders = 0;
        $total_sales = 0;

        foreach ($order_ids as $id) {
            $order = wc_get_order($id);
            if ($order) {
                if (in_array($order->get_status(), ['processing', 'pending', 'on-hold'])) {
                    $pending_orders++;
                }
                if ($order->get_status() !== 'cancelled') {
                    $total_sales += floatval($order->get_total());
                }
            }
        }

        $branch = get_term($branch_id, \RMM\Branches\BranchTaxonomy::TAXONOMY);
        $is_open = ($branch && !is_wp_error($branch)) ? (get_term_meta($branch_id, '_rmm_is_open', true) !== '0') : true;

        return rest_ensure_response([
            'success'        => true,
            'total_orders'   => $total_orders,
            'pending_orders' => $pending_orders,
            'total_sales'    => $total_sales,
            'currency'       => get_woocommerce_currency_symbol(),
            'branch_is_open' => $is_open,
        ]);
    }

    /**
     * Food Products list for fast inventory management
     */
    public function get_products(\WP_REST_Request $request) {
        $branch_id = $this->resolve_request_branch($request);
        if (is_wp_error($branch_id)) {
            return $branch_id;
        }

        $branch_inventory = \RMM\Branches\InventoryRepository::get_branch_inventory($branch_id);

        $posts = get_posts([
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        ]);

        $items = [];
        foreach ($posts as $post) {
            $product = wc_get_product($post->ID);
            if (!$product) continue;

            $inv = isset($branch_inventory[$post->ID]) ? $branch_inventory[$post->ID] : null;

            $reg_price = ($inv && $inv->regular_price !== null) ? $inv->regular_price : $product->get_regular_price();
            $sale_price = ($inv && $inv->sale_price !== null) ? $inv->sale_price : $product->get_sale_price();
            $clean_reg_price = ($reg_price !== '' && $reg_price !== null) ? intval(round(floatval($reg_price))) : '';
            $clean_sale_price = ($sale_price !== '' && $sale_price !== null) ? intval(round(floatval($sale_price))) : '';
            $stock_status = ($inv) ? $inv->stock_status : $product->get_stock_status();
            $is_available = ($inv) ? (bool) $inv->is_available : ($stock_status === 'instock');

            // Categories (single query)
            $cat_terms = wp_get_post_terms($post->ID, 'product_cat');
            $cats = [];
            $cat_ids = [];
            if (!empty($cat_terms) && !is_wp_error($cat_terms)) {
                foreach ($cat_terms as $ct) {
                    $cats[]    = $ct->name;
                    $cat_ids[] = $ct->term_id;
                }
            }

            // Gallery images
            $gallery_ids = (array) $product->get_gallery_image_ids();
            $gallery_urls = [];
            foreach ($gallery_ids as $g_id) {
                $url = wp_get_attachment_image_url($g_id, 'medium') ?: wp_get_attachment_url($g_id);
                if ($url) {
                    $gallery_urls[] = [
                        'id'  => absint($g_id),
                        'url' => $url,
                    ];
                }
            }

            $items[] = [
                'id'            => $post->ID,
                'name'          => get_post_field('post_title', $post->ID),
                'name_en'       => get_post_meta($post->ID, '_rmm_title_en', true),
                'description'   => get_post_field('post_excerpt', $post->ID) ?: get_post_field('post_content', $post->ID),
                'image'         => wp_get_attachment_image_url($product->get_image_id(), 'medium') ?: wc_placeholder_img_src(),
                'image_id'      => $product->get_image_id(),
                'gallery_ids'   => $gallery_ids,
                'gallery'       => $gallery_urls,
                'categories'    => $cats,
                'category_id'   => !empty($cat_ids) ? $cat_ids[0] : 0,
                'regular_price' => $clean_reg_price,
                'sale_price'    => $clean_sale_price,
                'stock_status'  => $stock_status,
                'is_available'  => $is_available,
                'calories'      => get_post_meta($post->ID, '_rmm_calories', true),
                'prep_time'     => get_post_meta($post->ID, '_rmm_prep_time', true),
                'ingredients'       => get_post_meta($post->ID, '_rmm_ingredients', true),
                'badge'             => get_post_meta($post->ID, '_rmm_badge_icon', true) ?: 'none',
                'badge_text'        => get_post_meta($post->ID, '_rmm_badge_text', true) ?: '',
                'badge_custom_icon' => get_post_meta($post->ID, '_rmm_badge_custom_icon', true) ?: '',
                'badge_image'       => get_post_meta($post->ID, '_rmm_badge_image', true) ?: '',
                'branches'          => (array) get_post_meta($post->ID, '_rmm_branches', true),
                'translations'  => get_post_meta($post->ID, '_rmm_translations', true) ?: (object)[],
            ];
        }

        return rest_ensure_response([
            'success'  => true,
            'products' => $items,
        ]);
    }

    /**
     * Toggle Stock Status
     */
    public function toggle_stock(\WP_REST_Request $request) {
        $product_id = absint($request->get_param('product_id'));
        $status     = sanitize_key($request->get_param('status'));

        if (!$product_id) {
            return new \WP_Error('rmm_missing_params', __('شناسه محصول الزامی است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        if (!in_array($status, ['instock', 'outofstock'], true)) {
            return new \WP_Error('rmm_invalid_stock_status', __('وضعیت موجودی نامعتبر است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        $branch_id = $this->resolve_request_branch($request);
        if (is_wp_error($branch_id)) {
            return $branch_id;
        }

        $product_guard = $this->assert_product_branch_inventory_access($product_id, $branch_id);
        if (is_wp_error($product_guard)) {
            return $product_guard;
        }

        // Branch-scoped change only. Never touch the global WooCommerce product.
        $updated = \RMM\Branches\InventoryRepository::toggle_stock($product_id, $branch_id, $status);
        if (!$updated) {
            error_log(sprintf('RMM inventory toggle failed for product %d, branch %d.', $product_id, $branch_id));
            return new \WP_Error('rmm_inventory_update_failed', __('ذخیره وضعیت موجودی با خطا مواجه شد. لطفاً دوباره تلاش کنید.', 'fondogh-online-menu'), ['status' => 500]);
        }

        return rest_ensure_response([
            'success'      => true,
            'product_id'   => $product_id,
            'branch_id'    => $branch_id,
            'stock_status' => $status,
            'is_available' => ($status === 'instock'),
        ]);
    }

    /**
     * Update Price
     */
    public function update_price(\WP_REST_Request $request) {
        $product_id = absint($request->get_param('product_id'));
        $raw_regular_price = $request->get_param('regular_price');
        $raw_sale_price = $request->get_param('sale_price');

        if (!$product_id) {
            return new \WP_Error('rmm_missing_params', __('شناسه محصول الزامی است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        if (!is_numeric($raw_regular_price)) {
            return new \WP_Error('rmm_invalid_regular_price', __('قیمت عادی باید یک عدد معتبر باشد.', 'fondogh-online-menu'), ['status' => 400]);
        }

        $regular_price = (float) $raw_regular_price;
        $max_price = 99999999.99; // DECIMAL(10,2) capacity of the branch inventory table.
        if ($regular_price <= 0 || $regular_price > $max_price) {
            return new \WP_Error('rmm_invalid_regular_price', __('قیمت عادی باید بیشتر از صفر و حداکثر ۹۹٬۹۹۹٬۹۹۹٫۹۹ باشد.', 'fondogh-online-menu'), ['status' => 400]);
        }

        $sale_price = null;
        if ($raw_sale_price !== null && $raw_sale_price !== '') {
            if (!is_numeric($raw_sale_price)) {
                return new \WP_Error('rmm_invalid_sale_price', __('قیمت فروش ویژه باید یک عدد معتبر باشد.', 'fondogh-online-menu'), ['status' => 400]);
            }

            $sale_price = (float) $raw_sale_price;
            if ($sale_price <= 0 || $sale_price >= $regular_price || $sale_price > $max_price) {
                return new \WP_Error('rmm_invalid_sale_price', __('قیمت فروش ویژه باید بیشتر از صفر و کمتر از قیمت عادی باشد.', 'fondogh-online-menu'), ['status' => 400]);
            }
        }

        $branch_id = $this->resolve_request_branch($request);
        if (is_wp_error($branch_id)) {
            return $branch_id;
        }

        $product_guard = $this->assert_product_branch_inventory_access($product_id, $branch_id);
        if (is_wp_error($product_guard)) {
            return $product_guard;
        }

        // Branch-scoped price only. Never call set_regular_price()/set_price() on
        // the global WooCommerce product; that would reprice every other branch.
        $updated = \RMM\Branches\InventoryRepository::update_price($product_id, $branch_id, $regular_price, $sale_price);
        if (!$updated) {
            error_log(sprintf('RMM inventory price update failed for product %d, branch %d.', $product_id, $branch_id));
            return new \WP_Error('rmm_price_update_failed', __('ذخیره قیمت شعبه با خطا مواجه شد. لطفاً دوباره تلاش کنید.', 'fondogh-online-menu'), ['status' => 500]);
        }

        return rest_ensure_response([
            'success'       => true,
            'product_id'    => $product_id,
            'branch_id'     => $branch_id,
            'regular_price' => $regular_price,
            'sale_price'    => $sale_price,
        ]);
    }

    /**
     * Live Orders for KDS
     */
    public function get_live_orders(\WP_REST_Request $request) {
        $branch_id = $this->resolve_request_branch($request);
        if (is_wp_error($branch_id)) {
            return $branch_id;
        }
        $last_id = absint($request->get_param('last_order_id'));

        $query_args = [
            'status'  => ['processing', 'pending', 'on-hold', 'completed'],
            'limit'   => 30,
            'orderby' => 'date',
            'order'   => 'DESC',
        ];

        if ($branch_id) {
            $query_args['meta_key']   = '_rmm_order_branch_id';
            $query_args['meta_value'] = $branch_id;
        }

        $orders = wc_get_orders($query_args);
        $data = [];

        foreach ($orders as $order) {
            $table_no = $order->get_meta('_rmm_table_number');
            $order_type = $order->get_meta('_rmm_order_type') ?: ($table_no ? 'dine_in' : 'delivery');

            $items = [];
            foreach ($order->get_items() as $item) {
                $meta_display = [];
                foreach ($item->get_formatted_meta_data() as $meta) {
                    $meta_display[] = strip_tags($meta->display_key . ': ' . $meta->display_value);
                }

                $items[] = [
                    'name'     => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'total'    => $item->get_total(),
                    'meta'     => $meta_display,
                ];
            }

            $data[] = [
                'id'            => $order->get_id(),
                'status'        => $order->get_status(),
                'status_name'   => wc_get_order_status_name($order->get_status()),
                'created_at'    => $order->get_date_created()->date_i18n('H:i - Y/m/d'),
                'customer_name' => $order->get_formatted_billing_full_name() ?: __('مشتری حضوری', 'fondogh-online-menu'),
                'phone'         => $order->get_billing_phone(),
                'table_number'  => $table_no,
                'order_type'    => $order_type,
                'total'         => $order->get_total(),
                'currency'      => get_woocommerce_currency_symbol(),
                'notes'         => $order->get_customer_note(),
                'items'         => $items,
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'orders'  => $data,
        ]);
    }

    /**
     * Update order status
     */
    public function update_order_status(\WP_REST_Request $request) {
        $order_id   = absint($request->get_param('order_id'));
        $new_status = sanitize_key($request->get_param('status'));

        $allowed_statuses = ['pending', 'processing', 'on-hold', 'completed', 'cancelled'];
        if (function_exists('wc_get_order_statuses')) {
            $wc_statuses = array_map(function ($key) {
                return strpos($key, 'wc-') === 0 ? substr($key, 3) : $key;
            }, array_keys(wc_get_order_statuses()));
            $allowed_statuses = array_unique(array_merge($allowed_statuses, $wc_statuses));
        }

        if (!$order_id || !$new_status || !in_array($new_status, $allowed_statuses, true)) {
            return new \WP_Error('rmm_invalid_status', __('شناسه یا وضعیت سفارش نامعتبر است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        $order = wc_get_order($order_id);
        $guard = $this->assert_order_branch($order);
        if (is_wp_error($guard)) {
            return $guard;
        }

        $current_user = wp_get_current_user();
        $is_kitchen_only = in_array('kitchen_staff', (array) $current_user->roles, true)
            && !user_can($current_user, 'manage_options')
            && !user_can($current_user, 'manage_woocommerce')
            && !user_can($current_user, 'manage_restaurant_menu');

        if ($is_kitchen_only && ($order->get_status() !== 'processing' || $new_status !== 'completed')) {
            return new \WP_Error(
                'rmm_forbidden_order_transition',
                __('پرسنل آشپزخانه فقط مجاز به تغییر وضعیت سفارش از «در حال انجام» به «تحویل‌شده» هستند.', 'fondogh-online-menu'),
                ['status' => 403]
            );
        }

        $order->update_status($new_status, sprintf(__('تغییر وضعیت از طریق پنل آشپزخانه رستوران به %s', 'fondogh-online-menu'), $new_status));

        return rest_ensure_response([
            'success'     => true,
            'order_id'    => $order_id,
            'status'      => $new_status,
            'status_name' => wc_get_order_status_name($new_status),
        ]);
    }

    /**
     * Toggle branch open status
     */
    public function toggle_branch_open_status(\WP_REST_Request $request) {
        $branch_id = $this->resolve_request_branch($request);
        if (is_wp_error($branch_id)) {
            return $branch_id;
        }
        $is_open = sanitize_text_field($request->get_param('is_open')); // '1' or '0'

        update_term_meta($branch_id, '_rmm_is_open', $is_open);

        return rest_ensure_response([
            'success' => true,
            'branch_id' => $branch_id,
            'is_open' => ($is_open === '1'),
        ]);
    }

    /**
     * Get thermal receipt data
     */
    public function get_order_receipt_data(\WP_REST_Request $request) {
        $order_id = absint($request->get_param('order_id'));
        $order = wc_get_order($order_id);

        $guard = $this->assert_order_branch($order);
        if (is_wp_error($guard)) {
            return $guard;
        }

        $table_no = $order->get_meta('_rmm_table_number');
        $branch_id = $order->get_meta('_rmm_order_branch_id');
        $branch = get_term($branch_id, \RMM\Branches\BranchTaxonomy::TAXONOMY);

        $items = [];
        foreach ($order->get_items() as $item) {
            $modifiers = [];
            foreach ($item->get_formatted_meta_data() as $m) {
                $modifiers[] = strip_tags($m->display_key . ': ' . $m->display_value);
            }
            $items[] = [
                'name'      => $item->get_name(),
                'qty'       => $item->get_quantity(),
                'total'     => $item->get_total(),
                'modifiers' => $modifiers,
            ];
        }

        return rest_ensure_response([
            'order_id'      => $order->get_id(),
            'date'          => $order->get_date_created()->date_i18n('Y/m/d - H:i'),
            'restaurant'    => get_bloginfo('name'),
            'branch_name'   => ($branch && !is_wp_error($branch)) ? $branch->name : '',
            'branch_phone'  => $branch_id ? get_term_meta($branch_id, '_rmm_phone', true) : '',
            'table_number'  => $table_no,
            'customer_name' => $order->get_formatted_billing_full_name() ?: __('مشتری حضوری', 'fondogh-online-menu'),
            'phone'         => $order->get_billing_phone(),
            'notes'         => $order->get_customer_note(),
            'total'         => $order->get_total(),
            'currency'      => get_woocommerce_currency_symbol(),
            'items'         => $items,
        ]);
    }

    /**
     * Get list of product categories (ordered by priority)
     */
    public function get_categories() {
        return rest_ensure_response([
            'success'    => true,
            'categories' => $this->get_categories_data(),
        ]);
    }

    /**
     * Helper to retrieve all categories structured and sorted by priority
     */
    public function get_categories_data() {
        $terms = class_exists('\RMM\Menu\CategoryOrder')
            ? \RMM\Menu\CategoryOrder::get_ordered_categories(['hide_empty' => false])
            : get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

        $data = [];
        if (!empty($terms) && !is_wp_error($terms)) {
            $priority = 1;
            foreach ($terms as $t) {
                $thumb_id = absint(get_term_meta($t->term_id, 'thumbnail_id', true));
                $thumb_url = $thumb_id ? (wp_get_attachment_image_url($thumb_id, 'medium') ?: wp_get_attachment_url($thumb_id)) : '';
                $translations = get_term_meta($t->term_id, '_rmm_translations', true) ?: (object)[];
                $order_meta = get_term_meta($t->term_id, 'order', true);

                $data[] = [
                    'id'            => $t->term_id,
                    'name'          => html_entity_decode($t->name, ENT_QUOTES, 'UTF-8'),
                    'slug'          => $t->slug,
                    'description'   => $t->description,
                    'count'         => $t->count,
                    'thumbnail_id'  => $thumb_id,
                    'thumbnail_url' => $thumb_url,
                    'translations'  => $translations,
                    'priority'      => $priority++,
                    'order'         => ($order_meta !== '') ? intval($order_meta) : ($priority - 1),
                ];
            }
        }

        return $data;
    }

    /**
     * Reorder Product Categories (Save Priority List)
     */
    public function reorder_categories(\WP_REST_Request $request) {
        $category_ids = $request->get_param('category_ids');
        if (!is_array($category_ids)) {
            return new \WP_Error('rmm_invalid_data', __('اطلاعات دسته‌بندی نامعتبر است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        if (class_exists('\RMM\Menu\CategoryOrder')) {
            \RMM\Menu\CategoryOrder::save_category_order($category_ids);
        }

        return rest_ensure_response([
            'success'    => true,
            'message'    => __('ترتیب اولویت دسته‌بندی‌ها با موفقیت ذخیره شد.', 'fondogh-online-menu'),
            'categories' => $this->get_categories_data(),
        ]);
    }

    /**
     * Save Product Category (Create or Edit)
     */
    public function save_category(\WP_REST_Request $request) {
        $cat_id      = absint($request->get_param('id'));
        $name        = sanitize_text_field($request->get_param('name'));
        $description = sanitize_textarea_field($request->get_param('description'));
        $thumb_id    = absint($request->get_param('thumbnail_id'));
        $translations= $request->get_param('translations');
        $order       = $request->get_param('order');

        if (empty($name)) {
            return new \WP_Error('rmm_invalid_name', __('نام دسته‌بندی الزامی است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        if ($cat_id > 0) {
            $updated = wp_update_term($cat_id, 'product_cat', [
                'name'        => $name,
                'description' => $description,
            ]);
            if (is_wp_error($updated)) {
                return new \WP_Error('rmm_update_error', $updated->get_error_message(), ['status' => 500]);
            }
        } else {
            $inserted = wp_insert_term($name, 'product_cat', [
                'description' => $description,
            ]);
            if (is_wp_error($inserted)) {
                return new \WP_Error('rmm_insert_error', $inserted->get_error_message(), ['status' => 500]);
            }
            $cat_id = $inserted['term_id'];
        }

        // Save thumbnail
        update_term_meta($cat_id, 'thumbnail_id', $thumb_id);

        // Save order if passed
        if ($order !== null && $order !== '') {
            update_term_meta($cat_id, 'order', absint($order));
        }

        // Multilingual translations
        if (is_array($translations)) {
            $clean_translations = [];
            foreach ($translations as $lang_code => $fields) {
                if (is_array($fields)) {
                    $clean_translations[sanitize_key($lang_code)] = [
                        'name' => sanitize_text_field($fields['name'] ?? ''),
                        'desc' => sanitize_textarea_field($fields['desc'] ?? ''),
                    ];
                }
            }
            update_term_meta($cat_id, '_rmm_translations', $clean_translations);
        }

        $term = get_term($cat_id, 'product_cat');
        $thumb_url = $thumb_id ? (wp_get_attachment_image_url($thumb_id, 'medium') ?: wp_get_attachment_url($thumb_id)) : '';

        return rest_ensure_response([
            'success'  => true,
            'category' => [
                'id'            => $cat_id,
                'name'          => html_entity_decode($term->name, ENT_QUOTES, 'UTF-8'),
                'slug'          => $term->slug,
                'description'   => $term->description,
                'count'         => $term->count,
                'thumbnail_id'  => $thumb_id,
                'thumbnail_url' => $thumb_url,
                'translations'  => get_term_meta($cat_id, '_rmm_translations', true) ?: (object)[],
                'order'         => get_term_meta($cat_id, 'order', true),
            ],
            'message'  => __('دسته‌بندی با موفقیت ذخیره شد.', 'fondogh-online-menu'),
        ]);
    }

    /**
     * Delete Product Category
     */
    public function delete_category(\WP_REST_Request $request) {
        $cat_id = absint($request->get_param('id'));
        if (!$cat_id) {
            return new \WP_Error('rmm_invalid_id', __('شناسه دسته‌بندی نامعتبر است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        $res = wp_delete_term($cat_id, 'product_cat');
        if (is_wp_error($res) || !$res) {
            return new \WP_Error('rmm_delete_failed', __('خطا در حذف دسته‌بندی.', 'fondogh-online-menu'), ['status' => 500]);
        }

        return rest_ensure_response([
            'success' => true,
            'id'      => $cat_id,
            'message' => __('دسته‌بندی با موفقیت حذف شد.', 'fondogh-online-menu'),
        ]);
    }

    /**
     * Get Branches List for Portal
     */
    public function get_portal_branches() {
        $allowed = AuthController::get_user_branches();
        if (empty($allowed)) {
            return rest_ensure_response([
                'success'  => true,
                'branches' => [],
            ]);
        }
        $terms = get_terms([
            'taxonomy'   => \RMM\Branches\BranchTaxonomy::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'term_id',
            'order'      => 'ASC',
            // Filter at query time: post-fetch filtering still leaks term data.
            'include'    => $allowed,
        ]);

        $branches = [];
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $b) {
                $is_open = get_term_meta($b->term_id, '_rmm_is_open', true) !== '0';
                $branches[] = [
                    'id'           => $b->term_id,
                    'name'         => html_entity_decode($b->name, ENT_QUOTES, 'UTF-8'),
                    'slug'         => $b->slug,
                    'phone'        => get_term_meta($b->term_id, '_rmm_phone', true) ?: '',
                    'address'      => get_term_meta($b->term_id, '_rmm_address', true) ?: '',
                    'hours'        => get_term_meta($b->term_id, '_rmm_hours', true) ?: '',
                    'mode'         => get_term_meta($b->term_id, '_rmm_mode', true) ?: 'global',
                    'is_open'      => $is_open,
                    'min_order'    => floatval(get_term_meta($b->term_id, '_rmm_min_order', true) ?: 0),
                    'tables_count' => absint(get_term_meta($b->term_id, '_rmm_tables_count', true) ?: 10),
                ];
            }
        }

        return rest_ensure_response([
            'success'  => true,
            'branches' => $branches,
        ]);
    }

    /**
     * Save Branch (Create or Edit)
     */
    public function save_branch(\WP_REST_Request $request) {
        $branch_id    = absint($request->get_param('id'));
        $name         = sanitize_text_field($request->get_param('name'));
        $phone        = sanitize_text_field($request->get_param('phone'));
        $address      = sanitize_textarea_field($request->get_param('address'));
        $hours        = sanitize_text_field($request->get_param('hours'));
        $mode         = sanitize_key($request->get_param('mode') ?: 'global');
        $is_open      = ($request->get_param('is_open') === '0' || $request->get_param('is_open') === false) ? '0' : '1';
        $min_order    = floatval($request->get_param('min_order') ?? 0);
        $tables_count = absint($request->get_param('tables_count') ?? 10);

        if (empty($name)) {
            return new \WP_Error('rmm_invalid_name', __('نام شعبه الزامی است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        if ($branch_id > 0 && !AuthController::user_can_access_branch($branch_id)) {
            return new \WP_Error('rmm_forbidden_branch', __('شما به این شعبه دسترسی ندارید.', 'fondogh-online-menu'), ['status' => 403]);
        }

        if ($branch_id > 0) {
            $updated = wp_update_term($branch_id, \RMM\Branches\BranchTaxonomy::TAXONOMY, [
                'name' => $name,
            ]);
            if (is_wp_error($updated)) {
                return new \WP_Error('rmm_update_error', $updated->get_error_message(), ['status' => 500]);
            }
        } else {
            $inserted = wp_insert_term($name, \RMM\Branches\BranchTaxonomy::TAXONOMY);
            if (is_wp_error($inserted)) {
                return new \WP_Error('rmm_insert_error', $inserted->get_error_message(), ['status' => 500]);
            }
            $branch_id = $inserted['term_id'];
        }

        update_term_meta($branch_id, '_rmm_phone', $phone);
        update_term_meta($branch_id, '_rmm_address', $address);
        update_term_meta($branch_id, '_rmm_hours', $hours);
        update_term_meta($branch_id, '_rmm_mode', $mode);
        update_term_meta($branch_id, '_rmm_is_open', $is_open);
        update_term_meta($branch_id, '_rmm_min_order', $min_order);
        update_term_meta($branch_id, '_rmm_tables_count', $tables_count);

        $term = get_term($branch_id, \RMM\Branches\BranchTaxonomy::TAXONOMY);

        return rest_ensure_response([
            'success' => true,
            'branch'  => [
                'id'           => $branch_id,
                'name'         => html_entity_decode($term->name, ENT_QUOTES, 'UTF-8'),
                'phone'        => $phone,
                'address'      => $address,
                'hours'        => $hours,
                'mode'         => $mode,
                'is_open'      => ($is_open === '1'),
                'min_order'    => $min_order,
                'tables_count' => $tables_count,
            ],
            'message' => __('اطلاعات شعبه با موفقیت ذخیره شد.', 'fondogh-online-menu'),
        ]);
    }

    /**
     * Save Product (Create or Edit)
     */
    public function save_product(\WP_REST_Request $request) {
        $product_id = absint($request->get_param('id'));
        $name       = sanitize_text_field($request->get_param('name'));

        if (empty($name)) {
            return new \WP_Error('rmm_invalid_name', __('نام غذا الزامی است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        // A branch assignment is also a claim: reject any branch the current
        // user is not allowed to manage before writing the product.
        $branches_param = $request->get_param('branches');
        $clean_branches = is_array($branches_param)
            ? array_values(array_filter(array_map('absint', $branches_param)))
            : [];
        foreach ($clean_branches as $b_id) {
            if (!AuthController::user_can_access_branch($b_id)) {
                return new \WP_Error('rmm_forbidden_branch', __('شما به یکی از شعبات اختصاصدادهشده دسترسی ندارید.', 'fondogh-online-menu'), ['status' => 403]);
            }
        }

        if ($product_id > 0) {
            $guard = $this->assert_product_branch_access($product_id);
            if (is_wp_error($guard)) {
                return $guard;
            }
            $product = wc_get_product($product_id);
            if (!$product) {
                return new \WP_Error('rmm_not_found', __('محصول مورد نظر یافت نشد.', 'fondogh-online-menu'), ['status' => 404]);
            }
        } else {
            $product = new \WC_Product_Simple();
        }

        $product->set_name($name);
        $product->set_status('publish');

        if ($request->has_param('description')) {
            $desc = sanitize_textarea_field($request->get_param('description'));
            $product->set_short_description($desc);
        }

        $regular_price = max(0, floatval($request->get_param('regular_price')));
        $raw_sale      = $request->get_param('sale_price');
        $sale_price    = ($raw_sale !== null && $raw_sale !== '' && floatval($raw_sale) > 0 && floatval($raw_sale) < $regular_price)
            ? floatval($raw_sale)
            : null;

        $stock_status = sanitize_text_field($request->get_param('stock_status') ?: 'instock');
        $stock_status = ($stock_status === 'outofstock') ? 'outofstock' : 'instock';

        // Only update global WooCommerce price/stock when creating a new product or when user is a global manager.
        // Branch-scoped managers update branch inventory rows below without overwriting other branches.
        $is_global_mgr = AuthController::is_global_manager();
        if ($product_id === 0 || $is_global_mgr) {
            $product->set_regular_price($regular_price);
            $product->set_price($sale_price !== null ? $sale_price : $regular_price);
            $product->set_sale_price($sale_price !== null ? $sale_price : '');
            $product->set_stock_status($stock_status);
        }

        // Featured Image
        if ($request->has_param('image_id')) {
            $image_id = absint($request->get_param('image_id'));
            $product->set_image_id($image_id);
        }

        // Product Gallery Album
        if ($request->has_param('gallery_ids')) {
            $gallery_ids = $request->get_param('gallery_ids');
            if (is_array($gallery_ids)) {
                $clean_gallery = array_filter(array_map('absint', $gallery_ids));
                $product->set_gallery_image_ids($clean_gallery);
            } else {
                $product->set_gallery_image_ids([]);
            }
        }

        $saved_id = $product->save();

        if (!$saved_id) {
            return new \WP_Error('rmm_save_failed', __('ذخیره‌سازی با خطا مواجه شد.', 'fondogh-online-menu'), ['status' => 500]);
        }

        // Category
        if ($request->has_param('category_id')) {
            $cat_id = absint($request->get_param('category_id'));
            wp_set_object_terms($saved_id, $cat_id > 0 ? [$cat_id] : [], 'product_cat');
        }

        // Restaurant Meta Fields
        $name_en     = sanitize_text_field($request->get_param('name_en'));
        $calories    = sanitize_text_field($request->get_param('calories'));
        $prep_time          = absint($request->get_param('prep_time'));
        $ingredients        = sanitize_text_field($request->get_param('ingredients'));
        $badge              = sanitize_text_field($request->get_param('badge') ?: 'none');
        $badge_text         = sanitize_text_field($request->get_param('badge_text') ?: '');
        $badge_custom_icon  = sanitize_text_field($request->get_param('badge_custom_icon') ?: '');
        $badge_image        = esc_url_raw($request->get_param('badge_image') ?: '');

        update_post_meta($saved_id, '_rmm_title_en', $name_en);
        update_post_meta($saved_id, '_rmm_calories', $calories);
        update_post_meta($saved_id, '_rmm_prep_time', $prep_time);
        update_post_meta($saved_id, '_rmm_ingredients', $ingredients);
        update_post_meta($saved_id, '_rmm_badge_icon', $badge);
        update_post_meta($saved_id, '_rmm_badge_text', $badge_text);
        update_post_meta($saved_id, '_rmm_badge_custom_icon', $badge_custom_icon);
        update_post_meta($saved_id, '_rmm_badge_image', $badge_image);

        // Multilingual Translations array
        $translations = $request->get_param('translations');
        if (is_array($translations)) {
            $clean_translations = [];
            foreach ($translations as $lang_code => $fields) {
                if (is_array($fields)) {
                    $clean_translations[sanitize_key($lang_code)] = [
                        'name'        => sanitize_text_field($fields['name'] ?? ''),
                        'desc'        => sanitize_textarea_field($fields['desc'] ?? ''),
                        'ingredients' => sanitize_textarea_field($fields['ingredients'] ?? ''),
                    ];
                }
            }
            update_post_meta($saved_id, '_rmm_translations', $clean_translations);

            if (!empty($clean_translations['en']['name'])) {
                update_post_meta($saved_id, '_rmm_title_en', $clean_translations['en']['name']);
            }
            if (!empty($clean_translations['en']['desc'])) {
                update_post_meta($saved_id, '_rmm_desc_en', $clean_translations['en']['desc']);
            }
        }

        // Branches assignment (validated against the access matrix above)
        if (is_array($branches_param)) {
            update_post_meta($saved_id, '_rmm_branches', $clean_branches);

            // Also update inventory table for each assigned branch.
            $failed_inventory_branches = [];
            foreach ($clean_branches as $b_id) {
                if ($b_id > 0) {
                    $inventory_saved = \RMM\Branches\InventoryRepository::upsert([
                        'product_id'    => $saved_id,
                        'branch_id'     => $b_id,
                        'regular_price' => $regular_price,
                        'sale_price'    => $sale_price ? floatval($sale_price) : null,
                        'stock_status'  => $stock_status,
                        'is_available'  => ($stock_status === 'instock') ? 1 : 0,
                    ]);

                    if (!$inventory_saved) {
                        $failed_inventory_branches[] = $b_id;
                    }
                }
            }
        }

        do_action('rmm_branch_inventory_updated', $saved_id, 0);

        if (!empty($failed_inventory_branches)) {
            error_log(sprintf(
                'RMM product %d was saved but branch inventory failed for branch IDs: %s.',
                $saved_id,
                implode(',', $failed_inventory_branches)
            ));

            return new \WP_Error(
                'rmm_partial_inventory_save',
                __('محصول ذخیره شد، اما ثبت قیمت و موجودی در بعضی شعب با خطا مواجه شد. شناسه شعب ناموفق: ', 'fondogh-online-menu') . implode(', ', $failed_inventory_branches),
                ['status' => 500, 'product_id' => $saved_id, 'failed_branch_ids' => $failed_inventory_branches]
            );
        }

        return rest_ensure_response([
            'success'    => true,
            'product_id' => $saved_id,
            'message'    => __('اطلاعات غذا با موفقیت ذخیره شد.', 'fondogh-online-menu'),
        ]);
    }

    /**
     * Delete Product
     */
    public function delete_product(\WP_REST_Request $request) {
        $product_id = absint($request->get_param('product_id'));

        if (!$product_id) {
            return new \WP_Error('rmm_invalid_id', __('شناسه نامعتبر است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        $guard = $this->assert_product_branch_access($product_id);
        if (is_wp_error($guard)) {
            return $guard;
        }

        $res = wp_trash_post($product_id);

        if (!$res) {
            return new \WP_Error('rmm_delete_failed', __('حذف با خطا مواجه شد.', 'fondogh-online-menu'), ['status' => 500]);
        }

        \RMM\Branches\InventoryRepository::delete_for_product($product_id);
        do_action('rmm_branch_inventory_updated', $product_id, 0);

        return rest_ensure_response([
            'success'    => true,
            'product_id' => $product_id,
            'message'    => __('غذا به سطل زباله منتقل شد.', 'fondogh-online-menu'),
        ]);
    }

    /**
     * AI Translation endpoint
     */
    public function handle_ai_translate(\WP_REST_Request $request) {
        $texts = $request->get_param('texts');
        $target_lang = sanitize_key($request->get_param('target_lang') ?: 'en');

        if (!is_array($texts) || empty($texts)) {
            return new \WP_Error('rmm_invalid_texts', __('متن‌های ورودی جهت ترجمه معتبر نیستند.', 'fondogh-online-menu'), ['status' => 400]);
        }

        $clean_texts = [];
        foreach ($texts as $k => $v) {
            $clean_texts[sanitize_key($k)] = sanitize_textarea_field($v);
        }

        $translated = \RMM\I18n\AiTranslator::translate_batch($clean_texts, $target_lang);

        if (is_wp_error($translated)) {
            return rest_ensure_response([
                'success' => false,
                'message' => $translated->get_error_message(),
            ]);
        }

        // Check if anything actually translated for non-Persian target
        if ($target_lang !== 'fa') {
            $has_diff = false;
            foreach ($clean_texts as $k => $orig) {
                if (!empty($orig) && !empty($translated[$k]) && $translated[$k] !== $orig) {
                    $has_diff = true;
                    break;
                }
            }
            if (!$has_diff && !empty(array_filter($clean_texts))) {
                return rest_ensure_response([
                    'success' => false,
                    'message' => __('متن‌ها ترجمه نشدند. لطفاً ارتباط سرور با سرویس ترجمه یا کلید هوش مصنوعی را بررسی کنید.', 'fondogh-online-menu'),
                ]);
            }
        }

        return rest_ensure_response([
            'success'     => true,
            'target_lang' => $target_lang,
            'translated'  => $translated,
        ]);
    }

    /**
     * Upload Image to WordPress Media Library (Featured or Gallery) with Auto-WebP & Resize
     */
    public function upload_image(\WP_REST_Request $request) {
        if (empty($_FILES['file'])) {
            return new \WP_Error('rmm_no_file', __('هیچ فایلی برای آپلود ارسال نشده است.', 'fondogh-online-menu'), ['status' => 400]);
        }

        $file = $_FILES['file'];

        if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return new \WP_Error('rmm_upload_failed', __('خطا در دریافت فایل آپلود شده.', 'fondogh-online-menu'), ['status' => 400]);
        }

        // Check max filesize
        $max_size_kb = \RMM\Core\Settings::get('image_max_size_kb', 3072);
        if ($file['size'] > ($max_size_kb * 1024)) {
            return new \WP_Error('rmm_file_too_large', sprintf(__('حجم فایل (%s) بیشتر از سقف مجاز (%d کیلوبایت) است.', 'fondogh-online-menu'), size_format($file['size']), $max_size_kb), ['status' => 400]);
        }

        $enable_webp = \RMM\Core\Settings::get('enable_webp_conversion', 'yes') === 'yes';
        $max_width   = absint(\RMM\Core\Settings::get('image_max_width', 1200)) ?: 1200;
        $max_height  = absint(\RMM\Core\Settings::get('image_max_height', 1200)) ?: 1200;
        $quality     = absint(\RMM\Core\Settings::get('webp_quality', 82)) ?: 82;

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = 0;

        // Perform WebP conversion and scaling if enabled and supported by PHP GD
        if ($enable_webp && function_exists('imagewebp') && function_exists('imagecreatefromstring')) {
            $tmp_path = $file['tmp_name'];
            $img_data = @file_get_contents($tmp_path);
            if ($img_data) {
                // Safeguard against memory exhaustion from maliciously crafted excessive pixel dimensions
                $size_info = @getimagesizefromstring($img_data);
                if ($size_info && (!empty($size_info[0]) && !empty($size_info[1]))) {
                    if ($size_info[0] > 8000 || $size_info[1] > 8000 || ($size_info[0] * $size_info[1]) > 40000000) {
                        return new \WP_Error('rmm_image_too_large', __('ابعاد تصویر فراتر از حد مجاز (حداکثر ۸۰۰۰×۸۰۰۰ پیکسل) است.', 'fondogh-online-menu'), ['status' => 400]);
                    }
                }

                $src_img = @imagecreatefromstring($img_data);
                if ($src_img) {
                    $orig_w = imagesx($src_img);
                    $orig_h = imagesy($src_img);

                    $new_w = $orig_w;
                    $new_h = $orig_h;

                    // Compute proportionate scaling down if dimensions exceed bounds
                    if ($orig_w > $max_width || $orig_h > $max_height) {
                        $ratio = min($max_width / $orig_w, $max_height / $orig_h);
                        $new_w = max(1, (int) round($orig_w * $ratio));
                        $new_h = max(1, (int) round($orig_h * $ratio));
                    }

                    $dst_img = imagecreatetruecolor($new_w, $new_h);
                    // Maintain alpha transparency for WebP / PNG
                    imagealphablending($dst_img, false);
                    imagesavealpha($dst_img, true);
                    $transparent = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
                    imagefilledrectangle($dst_img, 0, 0, $new_w, $new_h, $transparent);

                    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

                    $orig_filename = pathinfo($file['name'], PATHINFO_FILENAME);
                    $clean_slug = sanitize_file_name($orig_filename);
                    if (empty($clean_slug)) {
                        $clean_slug = 'food-' . time();
                    }
                    $webp_filename = $clean_slug . '-' . wp_generate_password(6, false) . '.webp';

                    $upload_dir = wp_upload_dir();
                    if (empty($upload_dir['error']) && wp_mkdir_p($upload_dir['path'])) {
                        $target_file = $upload_dir['path'] . '/' . $webp_filename;

                        if (imagewebp($dst_img, $target_file, $quality)) {
                            imagedestroy($dst_img);
                            imagedestroy($src_img);

                            $attachment = [
                                'post_mime_type' => 'image/webp',
                                'post_title'     => preg_replace('/\.[^.]+$/', '', $file['name']),
                                'post_content'   => '',
                                'post_status'    => 'inherit'
                            ];

                            $attachment_id = wp_insert_attachment($attachment, $target_file);
                            if (!is_wp_error($attachment_id) && $attachment_id > 0) {
                                $attach_data = wp_generate_attachment_metadata($attachment_id, $target_file);
                                wp_update_attachment_metadata($attachment_id, $attach_data);
                            } else {
                                $attachment_id = 0;
                            }
                        } else {
                            imagedestroy($dst_img);
                            imagedestroy($src_img);
                        }
                    } else {
                        imagedestroy($dst_img);
                        imagedestroy($src_img);
                    }
                }
            }
        }

        // Fallback to standard WP upload handler if WebP wasn't generated (restricted to image MIME types only)
        if (!$attachment_id) {
            $image_mimes_filter = function ($mimes) {
                return [
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png'          => 'image/png',
                    'gif'          => 'image/gif',
                    'webp'         => 'image/webp',
                ];
            };
            add_filter('upload_mimes', $image_mimes_filter, 999);
            $attachment_id = media_handle_upload('file', 0);
            remove_filter('upload_mimes', $image_mimes_filter, 999);
            if (is_wp_error($attachment_id)) {
                return new \WP_Error('rmm_upload_error', $attachment_id->get_error_message(), ['status' => 500]);
            }
        }

        $url = wp_get_attachment_image_url($attachment_id, 'medium') ?: wp_get_attachment_url($attachment_id);
        $full_url = wp_get_attachment_url($attachment_id);

        return rest_ensure_response([
            'success'  => true,
            'id'       => $attachment_id,
            'url'      => $url,
            'full_url' => $full_url,
            'is_webp'  => (get_post_mime_type($attachment_id) === 'image/webp'),
        ]);
    }
}
