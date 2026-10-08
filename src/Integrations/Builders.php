<?php
namespace AdminCafe\Integrations;

use AdminCafe\Core\Assets;

defined('ABSPATH') || exit;

final class Builders
{
    public function register(): void
    {
        foreach (['admincafe_menu' => 'menu', 'admincafe_categories' => 'categories', 'admincafe_products' => 'products', 'admincafe_cart' => 'cart'] as $name => $component) {
            add_shortcode($name, fn($attributes = []) => $this->shortcode((array) $attributes, $component));
        }
        add_action('init', [$this, 'block']);
        add_action('elementor/widgets/register', static function ($manager): void {
            $manager->register(new MenuWidget());
        });
        // Embedded/customer pages must not cache session-bound tokens. Only the bootstrap API sends them.
        add_filter('rest_post_dispatch', static function ($response, $server, $request) {
            if (str_starts_with($request->get_route(), '/admincafe/v1/')) {
                $response->header('Cache-Control', 'private, no-store, max-age=0');
                $response->header('Pragma', 'no-cache');
            }
            return $response;
        }, 10, 3);
    }

    public function shortcode(array $attributes, string $component = 'menu'): string
    {
        $attributes = shortcode_atts(['category' => 0, 'mode' => 'auto'], $attributes);
        return Assets::mount('menu', [
            'component' => $component,
            'categoryId' => absint($attributes['category']),
            'viewMode' => $attributes['mode'] === 'menu' ? 'menu' : 'auto',
        ]);
    }

    public function block(): void
    {
        wp_register_script('admincafe-block', ADMINCAFE_URL . 'assets/builder-block.js', ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n'], ADMINCAFE_VERSION, true);
        wp_set_script_translations('admincafe-block', 'admincafe', ADMINCAFE_PATH . 'languages');
        register_block_type('admincafe/menu', [
            'api_version' => 3,
            'editor_script' => 'admincafe-block',
            'attributes' => ['component' => ['type' => 'string', 'default' => 'menu'], 'category' => ['type' => 'number', 'default' => 0], 'mode' => ['type' => 'string', 'default' => 'auto']],
            'render_callback' => function (array $attributes): string {
                $component = in_array($attributes['component'] ?? '', ['menu', 'categories', 'products', 'cart'], true) ? $attributes['component'] : 'menu';
                return $this->shortcode($attributes, $component);
            },
        ]);
    }
}
