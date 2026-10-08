<?php
namespace FandooghRest\Branches;

use FandooghRest\Core\Settings;

defined('ABSPATH') || exit;

/** Stable branch identities and request-local context. Legacy objects belong to branch 1. */
final class Branches
{
    public const META = '_fandoogh_branch_id';
    public const USER_META = '_fandoogh_branch_ids';
    public const GLOBAL_SETTINGS = ['menu_slug', 'panel_slug', 'currency_code'];
    private static int $current = 1;

    public static function defaultId(): int { return 1; }
    public static function current(): int { return self::$current; }
    public static function setCurrent(int $id): void { self::$current = $id > 0 ? $id : 1; }
    public static function runFor(int $id, callable $callback): mixed
    {
        $previous = self::current();
        self::setCurrent($id);
        try { return $callback(); } finally { self::setCurrent($previous); }
    }
    public static function all(): array
    {
        $rows = get_option('fandoogh_branches', []);
        if (!is_array($rows) || !$rows) {
            $legacy = get_option('admincafe_settings', []);
            $rows = [1 => ['id' => 1, 'name' => $legacy['restaurant_name'] ?? get_bloginfo('name'), 'slug' => 'default', 'enabled' => true, 'settings' => array_diff_key((array) $legacy, array_flip(self::GLOBAL_SETTINGS))]];
        }
        return array_values(array_filter($rows, static fn($row) => is_array($row) && !empty($row['id'])));
    }
    public static function get(int $id): ?array
    {
        foreach (self::all() as $row) { if ((int) $row['id'] === $id) { return $row; } }
        return null;
    }
    public static function isCentral(?int $userId = null): bool { return user_can($userId ?? get_current_user_id(), 'manage_options'); }
    public static function allowedIds(?int $userId = null): array
    {
        $userId ??= get_current_user_id();
        if (self::isCentral($userId)) { return array_map(static fn($row) => (int) $row['id'], self::all()); }
        $assigned = get_user_meta($userId, self::USER_META, true);
        if (!is_array($assigned)) {
            $user = get_userdata($userId);
            $operationalCap = false;
            foreach (\FandooghRest\Core\Access::CAPS as $cap) { if (user_can($userId, $cap)) { $operationalCap = true; break; } }
            $assigned = $operationalCap || ($user && array_intersect((array) $user->roles, ['admincafe_manager', 'admincafe_staff', 'admincafe_kitchen', 'admincafe_cashier'])) ? [1] : [];
        }
        return array_values(array_filter(array_unique(array_map('absint', $assigned)), static fn($id) => self::get($id) !== null));
    }
    public static function canAccess(int $id, ?int $userId = null): bool
    {
        $branch = self::get($id);
        return $branch && in_array($id, self::allowedIds($userId), true);
    }
    public static function productBranch($product): int
    {
        if (is_numeric($product)) { $product = wc_get_product((int) $product); }
        if (!$product) { return 0; }
        if ($product->is_type('variation')) { return self::productBranch($product->get_parent_id()); }
        return (int) $product->get_meta(self::META, true) ?: 1;
    }
    public static function categoryBranch(int $id): int { return (int) get_term_meta($id, self::META, true) ?: 1; }
    public static function mediaBranch(int $id): int { return (int) get_post_meta($id, self::META, true) ?: 1; }
    public static function orderBranch($order): int
    {
        if (is_numeric($order)) { $order = wc_get_order((int) $order); }
        return $order ? ((int) $order->get_meta(self::META, true) ?: 1) : 0;
    }
    public static function ownsProduct($product): bool { return self::productBranch($product) === self::current(); }
    public static function ownsCategory(int $id): bool { return self::categoryBranch($id) === self::current(); }
    public static function ownsMedia(int $id): bool { return !$id || self::mediaBranch($id) === self::current() || (self::isCentral() && !get_post_meta($id, self::META, true)); }
    public static function settings(int $id): array { return $id === 1 ? array_diff_key((array) get_option('admincafe_settings', []), array_flip(self::GLOBAL_SETTINGS)) : (array) (self::get($id)['settings'] ?? []); }
    public static function saveSettings(int $id, array $settings): void
    {
        $rows = self::all();
        foreach ($rows as &$row) { if ((int) $row['id'] === $id) { $row['settings'] = array_diff_key($settings, array_flip(self::GLOBAL_SETTINGS)); } }
        unset($row);
        update_option('fandoogh_branches', $rows, false);
    }
    public static function menuUrl(?int $branchId = null): string
    {
        $id = $branchId ?? self::current();
        $global = (array) get_option('admincafe_settings', []);
        $pageId = (int) (self::settings($id)['menu_page_id'] ?? 0);
        if ($pageId && get_post_status($pageId) === 'publish') { $url = (string) get_permalink($pageId); return $id === 1 ? $url : add_query_arg('branch_id', $id, $url); }
        $path = '/' . ($global['menu_slug'] ?? 'menu') . '/';
        if ($id !== 1) { $path .= (self::get($id)['slug'] ?? 'default') . '/'; }
        return home_url($path);
    }
    public static function publicData(array $row): array
    {
        $settings = self::settings((int) $row['id']);
        return ['id' => (int) $row['id'], 'name' => (string) $row['name'], 'slug' => (string) $row['slug'], 'enabled' => !empty($row['enabled']), 'menu_url' => self::menuUrl((int) $row['id']), 'phone' => (string) ($settings['restaurant_phone'] ?? ''), 'address' => (string) ($settings['restaurant_address'] ?? '')];
    }
    public static function save(array $input, int $id = 0): array|\WP_Error
    {
        $error = static fn($message, $status = 400) => new \WP_Error('fandoogh_branch', __($message, 'fandoogh-rest'), ['status' => $status]);
        if (!self::isCentral()) { return $error('You do not have permission.', 403); }
        $existing = $id ? self::get($id) : null;
        if ($id && !$existing) { return $error('Branch not found.', 404); }
        foreach (['name', 'slug', 'enabled', 'phone', 'address'] as $key) { if (isset($input[$key]) && !is_scalar($input[$key])) { return $error('Invalid branch field.'); } }
        $name = sanitize_text_field((string) ($input['name'] ?? $existing['name'] ?? ''));
        $slug = sanitize_title((string) ($input['slug'] ?? $existing['slug'] ?? $name));
        if (mb_strlen($name) > 100 || strlen($slug) > 100 || !$name || !$slug || in_array($slug, ['wp-admin', 'wp-json', 'checkout', 'cart', 'branches'], true)) { return $error('Choose a valid branch name and address.'); }
        if (array_key_exists('enabled', $input) && !is_bool($input['enabled']) && !in_array($input['enabled'], [0, 1, '0', '1', 'true', 'false'], true)) { return $error('Invalid branch enabled value.'); }
        foreach (self::all() as $row) { if ((int) $row['id'] !== $id && $row['slug'] === $slug) { return $error('This branch address is already used.', 409); } }
        $rows = self::all();
        if (!$id) { $id = max(array_column($rows, 'id')) + 1; }
        $row = $existing ?? ['id' => $id, 'settings' => array_diff_key(Settings::defaults(), array_flip(self::GLOBAL_SETTINGS)), 'enabled' => true];
        $row['name'] = $name; $row['slug'] = $slug;
        if (array_key_exists('enabled', $input)) { $row['enabled'] = rest_sanitize_boolean($input['enabled']); }
        if (isset($input['phone'])) { $row['settings']['restaurant_phone'] = sanitize_text_field((string) $input['phone']); }
        if (isset($input['address'])) { $row['settings']['restaurant_address'] = sanitize_textarea_field((string) $input['address']); }
        if (!$existing) { $row['settings']['restaurant_name'] = $name; }
        if ($id === self::defaultId()) {
            $legacy = (array) get_option('admincafe_settings', []);
            foreach (['restaurant_name', 'restaurant_phone', 'restaurant_address'] as $field) { if (array_key_exists($field, $row['settings'])) { $legacy[$field] = $row['settings'][$field]; } }
            update_option('admincafe_settings', $legacy, false);
        }
        $rows = array_values(array_filter($rows, static fn($item) => (int) $item['id'] !== $id)); $rows[] = $row;
        update_option('fandoogh_branches', $rows, false);
        do_action('fandoogh_branch_settings_updated', $id);
        return self::publicData($row);
    }
    public static function migrate(): void
    {
        if (get_option('fandoogh_branches_version') === '1') { return; }
        if (!get_option('fandoogh_branches')) { update_option('fandoogh_branches', self::all(), false); }
        foreach (get_users(['role__in' => ['admincafe_manager', 'admincafe_staff', 'admincafe_kitchen', 'admincafe_cashier'], 'fields' => 'ID']) as $id) {
            if (!metadata_exists('user', (int) $id, self::USER_META)) { update_user_meta((int) $id, self::USER_META, [1]); }
        }
        update_option('fandoogh_branches_version', '1', false);
    }
}
