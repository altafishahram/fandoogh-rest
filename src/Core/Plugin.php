<?php
namespace AdminCafe\Core;

defined('ABSPATH') || exit;

final class Plugin
{
    public static function boot(): void
    {
        Access::register();
        add_action('init', [Installation::class, 'upgrade'], -1);
        foreach ([
            \AdminCafe\Localization\Language::class,
            Currency::class,
            Routes::class,
            Assets::class,
            \AdminCafe\Appearance\Module::class,
            \AdminCafe\Menu\Catalog::class,
            \AdminCafe\Translation\Module::class,
            \AdminCafe\Tables\Tables::class,
            \AdminCafe\Commerce\Orders::class,
            \AdminCafe\Commerce\Checkout::class,
            \AdminCafe\Rest\Management::class,
            \AdminCafe\Rest\Ordering::class,
            \AdminCafe\Import\Importer::class,
            \AdminCafe\Notifications\Notifications::class,
            \AdminCafe\Integrations\Builders::class,
        ] as $module) {
            (new $module())->register();
        }
        add_action('admin_menu', static function (): void {
            add_menu_page(__('AdminCafe', 'admincafe'), __('AdminCafe', 'admincafe'), 'admincafe_manage_orders', 'admincafe', static function (): void {
                echo '<div class="wrap"><h1>AdminCafe</h1><p>' . esc_html__('Restaurant operations are available in your dedicated panel.', 'admincafe') . '</p><a class="button button-primary" href="' . esc_url(Settings::panelUrl()) . '">' . esc_html__('Open restaurant panel', 'admincafe') . '</a></div>';
            }, 'dashicons-food', 56);
        });
    }
}
