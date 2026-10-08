<?php
namespace FandooghRest\Core;

defined('ABSPATH') || exit;

final class Plugin
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) { return; }
        self::$booted = true;
        Access::register();
        add_action('init', [Installation::class, 'upgrade'], -1);
        foreach ([
            \FandooghRest\Localization\Language::class,
            Currency::class,
            \FandooghRest\Branches\Module::class,
            Routes::class,
            Assets::class,
            \FandooghRest\Appearance\Module::class,
            \FandooghRest\Menu\Catalog::class,
            \FandooghRest\Translation\Module::class,
            \FandooghRest\Tables\Tables::class,
            \FandooghRest\Commerce\Orders::class,
            \FandooghRest\Commerce\Checkout::class,
            \FandooghRest\Rest\Management::class,
            \FandooghRest\Rest\Ordering::class,
            \FandooghRest\Import\Importer::class,
            \FandooghRest\Notifications\Notifications::class,
            \FandooghRest\Integrations\Builders::class,
        ] as $module) {
            (new $module())->register();
        }
        add_action('admin_menu', static function (): void {
            add_menu_page(__('Fandoogh Rest', 'fandoogh-rest'), __('Fandoogh Rest', 'fandoogh-rest'), 'admincafe_manage_orders', 'admincafe', static function (): void {
                echo '<div class="wrap"><h1>' . esc_html__('Fandoogh Rest', 'fandoogh-rest') . '</h1><p>' . esc_html__('Restaurant operations are available in your dedicated panel.', 'fandoogh-rest') . '</p><a class="button button-primary" href="' . esc_url(Settings::panelUrl()) . '">' . esc_html__('Open restaurant panel', 'fandoogh-rest') . '</a></div>';
            }, 'dashicons-food', 56);
        });
    }
}
