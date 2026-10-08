<?php
namespace FandooghRest\Integrations;

use FandooghRest\Core\Assets;
use FandooghRest\Branches\Branches;

defined('ABSPATH') || exit;

final class Builders
{
    public function register(): void
    {
        add_shortcode('fandoogh_rest_branches', static function (): string {
            $html = '<nav class="fandoogh-branches" aria-label="' . esc_attr__('Restaurant branches', 'fandoogh-rest') . '">';
            foreach (Branches::all() as $branch) {
                if (!$branch['enabled']) { continue; }
                $html .= '<a class="fandoogh-branch-link" href="' . esc_url(Branches::menuUrl($branch['id'])) . '">' . esc_html($branch['name']) . '</a> ';
            }
            return $html . '</nav>';
        });
        foreach (['admincafe_menu' => 'menu', 'admincafe_categories' => 'categories', 'admincafe_products' => 'products', 'admincafe_cart' => 'cart'] as $name => $component) {
            add_shortcode($name, fn($attributes = []) => $this->shortcode((array) $attributes, $component));
            add_shortcode(str_replace('admincafe_', 'fandoogh_rest_', $name), fn($attributes = []) => $this->shortcode((array) $attributes, $component));
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
        $attributes = shortcode_atts(['category' => 0, 'mode' => 'auto', 'branch' => 0], $attributes);
        $branch = $attributes['branch'];
        $branchId = Branches::current();
        if ($branch !== 0 && $branch !== '0' && $branch !== '') {
            $branchId = 0;
            foreach (Branches::all() as $row) {
                if ((is_scalar($branch) && (string) $row['id'] === (string) $branch) || (is_string($branch) && rawurldecode($row['slug']) === rawurldecode($branch))) { $branchId = $row['id']; break; }
            }
            if (!$branchId) { return '<p>' . esc_html__('This branch menu is unavailable.', 'fandoogh-rest') . '</p>'; }
        }
        return Assets::mount('menu', [
            'branchId' => $branchId,
            'component' => $component,
            'categoryId' => absint($attributes['category']),
            'viewMode' => $attributes['mode'] === 'menu' ? 'menu' : 'auto',
        ]);
    }

    public function block(): void
    {
        wp_register_script('admincafe-block', FANDOOGH_REST_URL . 'assets/builder-block.js', ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n'], FANDOOGH_REST_VERSION, true);
        wp_set_script_translations('admincafe-block', 'fandoogh-rest', FANDOOGH_REST_PATH . 'languages');
        register_block_type('admincafe/menu', [
            'api_version' => 3,
            'editor_script' => 'admincafe-block',
            'attributes' => ['component' => ['type' => 'string', 'default' => 'menu'], 'category' => ['type' => 'number', 'default' => 0], 'mode' => ['type' => 'string', 'default' => 'auto'], 'branch' => ['type' => 'string', 'default' => '']],
            'render_callback' => function (array $attributes): string {
                $component = in_array($attributes['component'] ?? '', ['menu', 'categories', 'products', 'cart'], true) ? $attributes['component'] : 'menu';
                return $this->shortcode($attributes, $component);
            },
        ]);
    }
}
