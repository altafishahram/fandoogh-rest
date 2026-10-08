<?php
namespace FandooghRest\Rest;

use FandooghRest\Core\Settings;
use FandooghRest\Menu\Catalog;
use FandooghRest\Tables\Tables;
use FandooghRest\Reports\Reports;
use FandooghRest\Localization\Language;

final class Management
{
    private const CAPS = ['admincafe_manage_menu', 'admincafe_manage_orders', 'admincafe_manage_settings', 'admincafe_manage_tables', 'admincafe_view_reports', 'admincafe_manage_staff', 'admincafe_receive_notifications'];
    private const ROLES = ['admincafe_manager', 'admincafe_staff', 'admincafe_kitchen', 'admincafe_cashier'];

    public function register(): void { add_action('rest_api_init', [$this, 'routes']); }
    public static function permission(\WP_REST_Request $request, string $cap): bool|\WP_Error
    {
        if (!is_user_logged_in() || !wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')) {
            return new \WP_Error('rest_forbidden', __('Sign in again to continue.', 'fandoogh-rest'), ['status' => 401]);
        }
        if (!current_user_can($cap)) { return new \WP_Error('rest_forbidden', __('You do not have permission.', 'fandoogh-rest'), ['status' => 403]); }
        return true;
    }
    private function route(string $path, string $methods, callable $callback, string $cap): void
    {
        register_rest_route('admincafe/v1', '/manage/' . $path, ['methods' => $methods, 'callback' => static function ($request) use ($callback, $methods) {
            if (in_array($methods, ['POST', 'PATCH'], true) && $request->get_header('Content-Type') !== null && str_contains((string) $request->get_header('Content-Type'), 'application/json')) {
                $body = $request->get_json_params();
                if (!is_array($body)) { return self::error(__('Send a JSON object.', 'fandoogh-rest')); }
            }
            return $callback($request);
        },
            'permission_callback' => fn($request) => self::permission($request, $cap)]);
    }
    public function routes(): void
    {
        register_rest_route('admincafe/v1', '/manage/bootstrap', ['methods' => 'GET', 'callback' => [$this, 'bootstrap'], 'permission_callback' => function ($request) {
            foreach (self::CAPS as $cap) { if (current_user_can($cap)) { return self::permission($request, $cap); } }
            return new \WP_Error('rest_forbidden', __('You do not have access to the panel.', 'fandoogh-rest'), ['status' => 403]);
        }]);
        $this->route('settings', 'GET', fn() => Settings::all(), 'admincafe_manage_settings');
        $this->route('settings', 'POST', fn($r) => Settings::update($r->get_json_params() ?: []), 'admincafe_manage_settings');
        $this->route('products', 'GET', [$this, 'products'], 'admincafe_manage_menu');
        $this->route('products', 'POST', fn($r) => Catalog::saveProduct($r->get_json_params() ?: []), 'admincafe_manage_menu');
        $this->route('products/(?P<id>\d+)', 'GET', fn($r) => Catalog::product((int) $r['id']), 'admincafe_manage_menu');
        $this->route('products/(?P<id>\d+)', 'PATCH', fn($r) => Catalog::saveProduct($r->get_json_params() ?: [], (int) $r['id']), 'admincafe_manage_menu');
        $this->route('products/(?P<id>\d+)', 'DELETE', function ($r) {
            $product = wc_get_product((int) $r['id']);
            if (!$product) { return self::error(__('Product not found.', 'fandoogh-rest'), 404); }
            $product->delete(false);
            return ['deleted' => true];
        }, 'admincafe_manage_menu');
        $this->route('categories', 'GET', function () {
            $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
            return is_wp_error($terms) ? $terms : array_map([Catalog::class, 'category'], $terms);
        }, 'admincafe_manage_menu');
        $this->route('categories', 'POST', fn($r) => $this->saveCategory($r), 'admincafe_manage_menu');
        $this->route('categories/(?P<id>\d+)', 'PATCH', fn($r) => $this->saveCategory($r, (int) $r['id']), 'admincafe_manage_menu');
        $this->route('categories/(?P<id>\d+)', 'DELETE', function ($r) {
            $result = wp_delete_term((int) $r['id'], 'product_cat');
            return is_wp_error($result) ? $result : ($result ? ['deleted' => true] : self::error(__('Category not found.', 'fandoogh-rest'), 404));
        }, 'admincafe_manage_menu');
        $this->route('reorder', 'POST', [$this, 'reorder'], 'admincafe_manage_menu');
        $this->route('tables', 'GET', fn() => Tables::all(), 'admincafe_manage_tables');
        $this->route('tables', 'POST', fn($r) => Tables::save($r->get_json_params() ?: []), 'admincafe_manage_tables');
        $this->route('tables/(?P<id>\d+)', 'PATCH', fn($r) => Tables::save($r->get_json_params() ?: [], (int) $r['id']), 'admincafe_manage_tables');
        $this->route('tables/(?P<id>\d+)', 'DELETE', fn($r) => Tables::delete((int) $r['id']), 'admincafe_manage_tables');
        $this->route('reports', 'GET', fn($r) => Reports::summary((int) ($r['days'] ?? 7)), 'admincafe_view_reports');
        $this->route('staff', 'GET', [$this, 'staff'], 'admincafe_manage_staff');
        $this->route('staff', 'POST', fn($r) => $this->saveStaff($r), 'admincafe_manage_staff');
        $this->route('staff/(?P<id>\d+)', 'PATCH', fn($r) => $this->saveStaff($r, (int) $r['id']), 'admincafe_manage_staff');
        $this->route('staff/(?P<id>\d+)', 'DELETE', [$this, 'deleteStaff'], 'admincafe_manage_staff');
        $this->route('media', 'POST', [$this, 'media'], 'admincafe_manage_menu');
    }
    private static function error(string $message, int $status = 400): \WP_Error
    {
        return new \WP_Error('admincafe_invalid_request', $message, ['status' => $status]);
    }
    public function bootstrap(): array
    {
        $user = wp_get_current_user();
        $capabilities = [];
        foreach (self::CAPS as $cap) { $capabilities[$cap] = current_user_can($cap); }
        $pages = [];
        if (current_user_can('admincafe_manage_settings')) {
            foreach (get_pages(['post_status' => 'publish']) as $page) { $pages[] = ['id' => $page->ID, 'title' => $page->post_title, 'url' => get_permalink($page)]; }
        }
        $roles = [];
        if (current_user_can('admincafe_manage_staff')) {
            $role_names = ['admincafe_manager' => __('Restaurant manager', 'fandoogh-rest'), 'admincafe_staff' => __('Restaurant staff', 'fandoogh-rest'), 'admincafe_kitchen' => __('Kitchen', 'fandoogh-rest'), 'admincafe_cashier' => __('Cashier', 'fandoogh-rest')];
            foreach (self::ROLES as $role) {
                // Managers may manage operational accounts; only administrators may grant manager access.
                if ($role === 'admincafe_manager' && !current_user_can('manage_options')) { continue; }
                $roles[] = ['id' => $role, 'name' => $role_names[$role]];
            }
        }
        return apply_filters('admincafe_management_bootstrap', ['settings' => Settings::all(), 'capabilities' => $capabilities,
            'user' => ['id' => $user->ID, 'name' => $user->display_name, 'roles' => array_values($user->roles)], 'menu_url' => Settings::menuUrl(), 'panel_url' => Settings::panelUrl(),
            'pages' => $pages, 'checkout_url' => wc_get_checkout_url(), 'currency_symbol' => html_entity_decode(get_woocommerce_currency_symbol()),
            'push' => ['public_key' => '', 'available' => false, 'reason' => __('Push is not configured.', 'fandoogh-rest')], 'roles' => $roles,
            'currencies' => array_map(static fn($code, $name): array => ['code' => $code, 'name' => $name], array_keys(get_woocommerce_currencies()), array_values(get_woocommerce_currencies())),
            'languages' => Language::descriptors()]);
    }
    public function products($request): array
    {
        $args = ['limit' => isset($request['page']) ? 100 : -1, 'page' => max(1, (int) ($request['page'] ?? 1)), 'type' => ['simple', 'variable'], 'status' => ['publish', 'draft', 'private'], 'orderby' => 'menu_order', 'order' => 'ASC'];
        if (!empty($request['search'])) { $args['s'] = sanitize_text_field($request['search']); }
        return array_map([Catalog::class, 'serialize'], wc_get_products($args));
    }
    public function saveCategory($request, int $id = 0): array|\WP_Error
    {
        $input = $request->get_json_params() ?: [];
        $translation_patch = null;
        if (array_key_exists('translations', $input)) {
            $translation_patch = Catalog::validateTranslations($input['translations'], ['name']);
            if (is_wp_error($translation_patch)) { return $translation_patch; }
        }
        foreach (['name', 'parent', 'image_id', 'icon', 'order'] as $key) { if (isset($input[$key]) && !is_scalar($input[$key])) { return self::error(__('Invalid category field.', 'fandoogh-rest')); } }
        $term = $id ? get_term($id, 'product_cat') : null;
        if ($id && (!$term || is_wp_error($term))) { return self::error(__('Category not found.', 'fandoogh-rest'), 404); }
        $name = sanitize_text_field($input['name'] ?? ($term ? $term->name : ''));
        if (!$name) { return self::error(__('Category name is required.', 'fandoogh-rest')); }
        $parent = isset($input['parent']) ? absint($input['parent']) : ($term ? (int) $term->parent : 0);
        if ($parent && (!term_exists($parent, 'product_cat') || $parent === $id || ($id && term_is_ancestor_of($id, $parent, 'product_cat')))) { return self::error(__('Invalid category parent.', 'fandoogh-rest')); }
        if (isset($input['image_id']) && (int) $input['image_id'] && !wp_attachment_is_image(absint($input['image_id']))) { return self::error(__('Choose an image attachment.', 'fandoogh-rest')); }
        $saved = $id ? wp_update_term($id, 'product_cat', ['name' => $name, 'parent' => $parent]) : wp_insert_term($name, 'product_cat', ['parent' => $parent]);
        if (is_wp_error($saved)) { return $saved; }
        $id = (int) $saved['term_id'];
        if (isset($input['image_id'])) { update_term_meta($id, 'thumbnail_id', absint($input['image_id'])); }
        if (isset($input['icon'])) { update_term_meta($id, '_admincafe_icon', sanitize_text_field(mb_substr((string) $input['icon'], 0, 60))); }
        if (isset($input['order'])) { update_term_meta($id, '_admincafe_order', max(0, (int) $input['order'])); }
        if ($translation_patch !== null) { update_term_meta($id, '_admincafe_translations', Catalog::mergeTranslations(Catalog::storedTranslations(get_term_meta($id, '_admincafe_translations', true), ['name']), $translation_patch)); }
        if ($translation_patch !== null) { do_action('admincafe_translation_manual_input', 'category', $id, $translation_patch); }
        return Catalog::category(get_term($id, 'product_cat'));
    }
    public function reorder($request): array|\WP_Error
    {
        $input = $request->get_json_params() ?: [];
        $id = absint($input['category_id'] ?? 0);
        if (!$id || !term_exists($id, 'product_cat') || !isset($input['ids']) || !is_array($input['ids']) || count($input['ids']) > 2000) { return self::error(__('Supply a category and product IDs.', 'fandoogh-rest')); }
        $ids = array_values(array_unique(array_map('absint', $input['ids'])));
        foreach ($ids as $product_id) {
            $product = wc_get_product($product_id);
            if (!$product || !in_array($id, $product->get_category_ids(), true)) { return self::error(__('Every product must belong to this category.', 'fandoogh-rest')); }
        }
        update_term_meta($id, '_admincafe_product_order', $ids);
        return ['category_id' => $id, 'ids' => $ids];
    }
    private static function staffData($user): array
    {
        return ['id' => $user->ID, 'name' => $user->display_name, 'username' => $user->user_login, 'email' => $user->user_email, 'role' => current(array_intersect($user->roles, self::ROLES)) ?: '', 'roles' => array_values(array_intersect($user->roles, self::ROLES))];
    }
    public function staff(): array
    {
        return array_map([self::class, 'staffData'], get_users(['role__in' => self::ROLES, 'number' => 200, 'orderby' => 'display_name']));
    }
    private static function editableStaff(int $id): bool
    {
        $user = get_user_by('id', $id);
        if (!$user || $id === get_current_user_id() || is_super_admin($id) || in_array('administrator', $user->roles, true)) { return false; }
        if (array_diff($user->roles, self::ROLES) || !array_intersect($user->roles, self::ROLES)) { return false; }
        return !in_array('admincafe_manager', $user->roles, true) || current_user_can('manage_options');
    }
    public function saveStaff($request, int $id = 0): array|\WP_Error
    {
        $input = $request->get_json_params() ?: [];
        foreach (['role', 'name', 'email', 'username', 'password'] as $key) { if (isset($input[$key]) && !is_scalar($input[$key])) { return self::error(__('Invalid staff field.', 'fandoogh-rest')); } }
        if ($id && !self::editableStaff($id)) { return self::error(__('This account cannot be changed from the panel.', 'fandoogh-rest'), 403); }
        $role = $input['role'] ?? ($id ? self::staffData(get_user_by('id', $id))['role'] : 'admincafe_staff');
        if (!in_array($role, self::ROLES, true) || ($role === 'admincafe_manager' && !current_user_can('manage_options'))) { return self::error(__('Choose an operational Fandoogh Rest role.', 'fandoogh-rest'), 403); }
        $data = ['role' => $role];
        if ($id) { $data['ID'] = $id; }
        if (isset($input['name'])) { $data['display_name'] = sanitize_text_field($input['name']); }
        if (isset($input['email'])) {
            if (!is_email($input['email'])) { return self::error(__('A valid email is required.', 'fandoogh-rest')); }
            $data['user_email'] = sanitize_email($input['email']);
        }
        if (!$id) {
            $data['user_login'] = sanitize_user($input['username'] ?? '', true);
            if (!$data['user_login'] || empty($data['user_email'])) { return self::error(__('Username and email are required.', 'fandoogh-rest')); }
        }
        if (!$id || isset($input['password'])) {
            $password = (string) ($input['password'] ?? '');
            if (strlen($password) < 12 || strlen($password) > 4096) { return self::error(__('Password must contain at least 12 characters.', 'fandoogh-rest')); }
            $data['user_pass'] = $password;
        }
        $saved = $id ? wp_update_user($data) : wp_insert_user($data);
        return is_wp_error($saved) ? $saved : self::staffData(get_user_by('id', $saved));
    }
    public function deleteStaff($request): array|\WP_Error
    {
        $id = (int) $request['id'];
        if (!self::editableStaff($id)) { return self::error(__('This account cannot be removed from the panel.', 'fandoogh-rest'), 403); }
        // Revoke operational access while preserving account and historical order ownership.
        get_user_by('id', $id)->set_role('customer');
        return ['deleted' => true];
    }
    public function media($request): array|\WP_Error
    {
        $files = $request->get_file_params();
        $file = $files['file'] ?? null;
        if (!$file || !is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { return self::error(__('Upload a valid image.', 'fandoogh-rest')); }
        if ((int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > 5 * 1024 * 1024) { return self::error(__('Image must be smaller than 5 MB.', 'fandoogh-rest')); }
        $mimes = ['jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name'], $mimes);
        $image = wp_getimagesize($file['tmp_name']);
        if (!$check['ext'] || !$check['type'] || !$image || !in_array($image['mime'] ?? '', array_values($mimes), true) || $image[0] * $image[1] > 40000000) { return self::error(__('Only valid JPEG, PNG, or WebP images are supported.', 'fandoogh-rest')); }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $id = media_handle_upload('file', 0, [], ['test_form' => false, 'mimes' => $mimes]);
        return is_wp_error($id) ? $id : ['id' => $id, 'url' => wp_get_attachment_url($id)];
    }
}
