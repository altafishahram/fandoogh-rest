<?php
namespace AdminCafe\Appearance;

defined('ABSPATH') || exit;

final class Appearance
{
    public const MENU_SCOPE = '.admincafe-root[data-admincafe-app="menu"] .ac-menu';
    public const PREVIEW_SCOPE = '.admincafe-root .ac-menu.ac-appearance-preview';
    public const THEMES = ['cafe', 'minimal', 'midnight', 'garden', 'bistro'];
    public const SELECTORS = [
        'card' => '.ac-product',
        'detail' => '.ac-food-dialog',
        'language' => '.ac-language-dialog',
        'categories' => '.ac-categories button',
        'buttons' => '.ac-primary,.ac-plus,.ac-cart-float',
        'cart' => '.ac-cart-dialog',
    ];

    public static function defaults(): array
    {
        return [
            'menu_theme' => 'cafe',
            'custom_css_enabled' => true,
            'custom_css' => array_fill_keys(array_merge(['general'], array_keys(self::SELECTORS)), ''),
        ];
    }

    /** Validate complete appearance state. Raw editable strings are stored only in private settings. */
    public static function validate(array $input, array $current): array|\WP_Error
    {
        $state = array_intersect_key($current, self::defaults()) + self::defaults();
        if (array_key_exists('menu_theme', $input)) {
            if (!is_string($input['menu_theme']) || !in_array($input['menu_theme'], self::THEMES, true)) {
                return self::error('menu_theme', 'Choose a supported menu theme.');
            }
            $state['menu_theme'] = $input['menu_theme'];
        }
        if (array_key_exists('custom_css_enabled', $input)) {
            if (!is_bool($input['custom_css_enabled'])) {
                return self::error('custom_css_enabled', 'The custom CSS switch must be a boolean.');
            }
            $state['custom_css_enabled'] = $input['custom_css_enabled'];
        }
        if (array_key_exists('custom_css', $input)) {
            if (
                !is_array($input['custom_css'])
                || array_diff(array_keys($input['custom_css']), array_keys(self::defaults()['custom_css']))
            ) {
                return self::error('custom_css', 'Choose supported CSS editor fields.');
            }
            $state['custom_css'] = array_replace(self::defaults()['custom_css'], (array) $state['custom_css'], $input['custom_css']);
        }
        $total = 0;
        foreach ($state['custom_css'] as $field => $css) {
            if (!is_string($css) || strlen($css) > ($field === 'general' ? 12000 : 4000)) {
                return self::error($field, 'CSS exceeds the field limit.');
            }
            $total += strlen($css);
            try {
                (new CssCompiler())->compile($css, self::MENU_SCOPE, $field !== 'general');
            } catch (\InvalidArgumentException $error) {
                return self::error($field, $error->getMessage());
            }
        }
        if ($total > 30000) {
            return self::error('custom_css', 'The combined CSS exceeds 30000 bytes.');
        }
        return $state;
    }

    private static function error(string $field, string $message): \WP_Error
    {
        return new \WP_Error(
            'admincafe_appearance',
            sprintf(__('CSS setting "%1$s": %2$s', 'admincafe'), $field, __($message, 'admincafe')),
            ['status' => 400, 'field' => $field]
        );
    }

    public static function compiled(array $settings, string $scope = self::MENU_SCOPE): string
    {
        if (empty($settings['custom_css_enabled'])) {
            return '';
        }
        $output = '';
        foreach (array_replace(self::defaults()['custom_css'], (array) ($settings['custom_css'] ?? [])) as $field => $css) {
            if (!is_string($css) || !isset(self::defaults()['custom_css'][$field])) {
                continue;
            }
            try {
                $compiler = new CssCompiler();
                if ($field === 'general') {
                    $output .= $compiler->compile($css, $scope);
                } else {
                    $declarations = $compiler->compile($css, $scope, true);
                    if ($declarations !== '') {
                        $selectors = array_map(
                            static fn(string $selector): string => $scope . ' ' . $selector,
                            explode(',', self::SELECTORS[$field])
                        );
                        $output .= implode(',', $selectors) . '{' . $declarations . '}';
                    }
                }
            } catch (\InvalidArgumentException) {
                // Fail closed if options were modified outside validated saves.
            }
        }
        return $output;
    }

    /** Public early bootstrap is deliberately independent of catalog and business content. */
    public static function publicSettings(array $settings, string $scope = self::MENU_SCOPE): array
    {
        $keys = [
            'menu_theme', 'custom_css_enabled', 'accent', 'category_background', 'background',
            'font_family', 'custom_font_url', 'title_size_mobile', 'title_size_desktop',
            'description_size_mobile', 'description_size_desktop', 'price_size_mobile', 'price_size_desktop',
            'title_weight', 'description_weight', 'price_weight', 'title_weight_mobile', 'title_weight_desktop',
            'description_weight_mobile', 'description_weight_desktop', 'price_weight_mobile', 'price_weight_desktop',
        ];
        $result = array_intersect_key($settings, array_flip($keys));
        $result['menu_theme'] = in_array($settings['menu_theme'] ?? '', self::THEMES, true) ? $settings['menu_theme'] : 'cafe';
        $result['custom_css_enabled'] = (bool) ($settings['custom_css_enabled'] ?? true);
        $result['menu_custom_css'] = self::compiled($settings, $scope);
        return $result;
    }
}
