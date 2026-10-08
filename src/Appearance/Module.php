<?php
namespace FandooghRest\Appearance;

use FandooghRest\Core\Settings;
use FandooghRest\Rest\Management;

defined('ABSPATH') || exit;

final class Module
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
        add_filter('admincafe_management_bootstrap', static function (array $bootstrap): array {
            if (!current_user_can('admincafe_manage_settings')) {
                unset($bootstrap['settings']['custom_css']);
            }
            return $bootstrap;
        });
    }

    public function routes(): void
    {
        register_rest_route('admincafe/v1', '/manage/appearance/preview', [
            'methods' => 'POST',
            'permission_callback' => static fn(\WP_REST_Request $request) => Management::permission($request, 'admincafe_manage_settings'),
            'callback' => [$this, 'preview'],
        ]);
    }

    public function preview(\WP_REST_Request $request): array|\WP_Error
    {
        $input = $request->get_json_params();
        if (!is_array($input) || array_diff(array_keys($input), array_keys(Appearance::defaults()))) {
            return new \WP_Error(
                'admincafe_appearance',
                __('Send an appearance settings object.', 'fandoogh-rest'),
                ['status' => 400]
            );
        }
        $state = Appearance::validate($input, Settings::all());
        if (is_wp_error($state)) {
            return $state;
        }
        return [
            'menu_theme' => $state['menu_theme'],
            'custom_css_enabled' => $state['custom_css_enabled'],
            'menu_custom_css' => Appearance::compiled($state, Appearance::PREVIEW_SCOPE),
        ];
    }
}
