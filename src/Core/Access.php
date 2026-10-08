<?php
namespace AdminCafe\Core;

defined('ABSPATH') || exit;

final class Access
{
    public const CAPS = ['admincafe_manage_menu', 'admincafe_manage_orders', 'admincafe_manage_settings', 'admincafe_manage_tables', 'admincafe_view_reports', 'admincafe_manage_staff', 'admincafe_receive_notifications'];

    public static function install(): void
    {
        $roles = [
            'admincafe_manager' => ['name' => __('Restaurant manager', 'admincafe'), 'caps' => self::CAPS],
            'admincafe_staff' => ['name' => __('Restaurant staff', 'admincafe'), 'caps' => ['admincafe_manage_orders', 'admincafe_manage_tables', 'admincafe_receive_notifications']],
            'admincafe_kitchen' => ['name' => __('Kitchen', 'admincafe'), 'caps' => ['admincafe_manage_orders', 'admincafe_receive_notifications']],
            'admincafe_cashier' => ['name' => __('Cashier', 'admincafe'), 'caps' => ['admincafe_manage_orders', 'admincafe_view_reports', 'admincafe_receive_notifications']],
        ];
        foreach ($roles as $key => $definition) {
            $role = get_role($key) ?: add_role($key, $definition['name'], ['read' => true]);
            foreach (self::CAPS as $capability) {
                if (in_array($capability, $definition['caps'], true)) {
                    $role->add_cap($capability);
                } else {
                    $role->remove_cap($capability);
                }
            }
        }
        $admin = get_role('administrator');
        if ($admin) {
            foreach (self::CAPS as $capability) {
                $admin->add_cap($capability);
            }
        }
    }

    public static function register(): void
    {
        add_action('admin_init', static function (): void {
            if (wp_doing_ajax() || current_user_can('manage_options')) {
                return;
            }
            if (self::isOperationalUser()) {
                wp_safe_redirect(Settings::panelUrl());
                exit;
            }
        });
        add_filter('show_admin_bar', static fn(bool $show): bool => self::isOperationalUser() ? false : $show);
        add_action('wp_logout', static function (int $userId): void {
            // A logged-out employee's devices must stop receiving private order notifications.
            delete_user_meta($userId, '_admincafe_push_devices');
        });
    }

    public static function can(string $cap): bool
    {
        return in_array($cap, self::CAPS, true) && current_user_can($cap);
    }

    public static function isOperationalUser(): bool
    {
        return !current_user_can('manage_options') && (bool) array_intersect(['admincafe_manager', 'admincafe_staff', 'admincafe_kitchen', 'admincafe_cashier'], (array) wp_get_current_user()->roles);
    }
}
