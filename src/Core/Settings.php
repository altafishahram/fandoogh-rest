<?php
namespace FandooghRest\Core;

use FandooghRest\Localization\Language;
use FandooghRest\Appearance\Appearance;
use FandooghRest\Branches\Branches;

defined('ABSPATH') || exit;

final class Settings
{
    public static function defaults(): array
    {
        return array_merge(Appearance::defaults(), [
            'restaurant_name' => get_bloginfo('name'), 'tagline' => 'طعم خوب، لحظه‌های بهتر',
            'logo_id' => 0, 'cover_id' => 0, 'menu_slug' => 'menu', 'panel_slug' => 'cafe-panel', 'menu_page_id' => 0,
            'dine_in_enabled' => false, 'pickup_enabled' => false, 'delivery_enabled' => false, 'ordering_paused' => false,
            'table_default_mode' => 'menu', 'accent' => '#c87545', 'category_background' => '#f3ede5', 'background' => '#faf8f5',
            'font_family' => 'Vazirmatn', 'custom_font_url' => '', 'currency_code' => get_option('woocommerce_currency', 'IRT'), 'currency_label' => '',
            'title_size_mobile' => 18, 'title_size_desktop' => 22, 'description_size_mobile' => 13, 'description_size_desktop' => 14,
            'price_size_mobile' => 16, 'price_size_desktop' => 18, 'title_weight' => 700, 'description_weight' => 400, 'price_weight' => 700,
            'title_weight_mobile' => 700, 'title_weight_desktop' => 700, 'description_weight_mobile' => 400,
            'description_weight_desktop' => 400, 'price_weight_mobile' => 700, 'price_weight_desktop' => 700,
            'layout' => 'grid', 'restaurant_phone' => '', 'restaurant_address' => '', 'hours_text' => '', 'preparation_minutes' => 20,
            'notification_sound' => true, 'messages' => ['unavailable' => 'فعلاً ناموجود', 'closed' => 'سفارش‌گیری موقتاً متوقف است', 'order_received' => 'سفارش شما برای تأیید کارکنان ارسال شد'],
            'menu_category_ids' => [], 'qr_color' => '#242424', 'qr_size' => 512,
            'enabled_languages' => ['fa', 'en', 'zh', 'tr'], 'default_language' => 'fa', 'content_translations' => [],
        ]);
    }

    public static function all(): array
    {
        $global = (array) get_option('admincafe_settings', []);
        $stored = array_replace(Branches::current() === Branches::defaultId() ? $global : Branches::settings(Branches::current()), array_intersect_key($global, array_flip(Branches::GLOBAL_SETTINGS)));
        $stored = array_intersect_key($stored, self::defaults());
        $settings = array_replace_recursive(self::defaults(), $stored);
        // Lists replace the defaults; recursive array replacement would silently re-enable languages.
        if (is_array($stored) && isset($stored['enabled_languages']) && is_array($stored['enabled_languages'])) {
            $settings['enabled_languages'] = array_values($stored['enabled_languages']);
        }
        $settings['currency_code'] = get_option('woocommerce_currency', 'IRT');
        return $settings;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function update(array $input): array|\WP_Error
    {
        $settings = self::all();
        foreach (Branches::GLOBAL_SETTINGS as $key) {
            if (array_key_exists($key, $input) && !Branches::isCentral() && (!is_scalar($input[$key]) || (string) $input[$key] !== (string) $settings[$key])) {
                return new \WP_Error('rest_forbidden', __('Only the collection administrator may change shared settings.', 'fandoogh-rest'), ['status' => 403]);
            }
            if (array_key_exists($key, $input) && is_scalar($input[$key]) && (string) $input[$key] === (string) $settings[$key]) { unset($input[$key]); }
        }
        $appearance = Appearance::validate($input, $settings);
        if (is_wp_error($appearance)) { return $appearance; }
        $settings = array_replace($settings, $appearance);
        foreach ($input as $key => $value) {
            if (array_key_exists($key, Appearance::defaults())) { continue; }
            if (!array_key_exists($key, $settings)) {
                continue;
            }
            if ($key === 'content_translations') {
                $value = self::sanitizeTranslations($value, $settings['content_translations']);
                if (is_wp_error($value)) { return $value; }
                $settings[$key] = $value;
                continue;
            }
            if (!in_array($key, ['messages', 'menu_category_ids', 'enabled_languages'], true) && !is_scalar($value) && $value !== null) {
                return new \WP_Error('admincafe_setting_type', __('Invalid setting value.', 'fandoogh-rest'), ['status' => 400]);
            }
            if (in_array($key, ['messages', 'menu_category_ids', 'enabled_languages'], true) && !is_array($value)) {
                return new \WP_Error('admincafe_setting_type', __('Invalid setting value.', 'fandoogh-rest'), ['status' => 400]);
            }
            if (is_array($value) && array_filter($value, static fn($item): bool => !is_scalar($item))) {
                return new \WP_Error('admincafe_setting_type', __('Invalid setting value.', 'fandoogh-rest'), ['status' => 400]);
            }
            if ($key === 'enabled_languages') {
                if (!$value || count($value) > 4 || array_filter($value, static fn($code): bool => !is_string($code) || !isset(Language::supported()[$code]))) {
                    return new \WP_Error('admincafe_languages', __('Choose supported customer languages.', 'fandoogh-rest'), ['status' => 400]);
                }
                $value = array_values(array_unique($value));
            } elseif ($key === 'default_language') {
                if (!is_string($value) || !isset(Language::supported()[$value])) {
                    return new \WP_Error('admincafe_languages', __('Choose supported customer languages.', 'fandoogh-rest'), ['status' => 400]);
                }
            } elseif (in_array($key, ['menu_slug', 'panel_slug'], true)) {
                $value = sanitize_title((string) $value);
                if (!$value || strpos($value, '/') !== false || in_array($value, ['wp-admin', 'wp-login', 'wp-json', 'cafe-qr', 'feed', 'checkout', 'cart'], true)) {
                    return new \WP_Error('admincafe_slug', __('Choose a valid, unreserved page address.', 'fandoogh-rest'), ['status' => 400]);
                }
                $page = get_page_by_path($value);
                if ($page && !($key === 'menu_slug' && (int) ($input['menu_page_id'] ?? $settings['menu_page_id']) === (int) $page->ID)) {
                    return new \WP_Error('admincafe_slug_conflict', __('This address is already used by a WordPress page.', 'fandoogh-rest'), ['status' => 409]);
                }
            } elseif (in_array($key, ['accent', 'category_background', 'background', 'qr_color'], true)) {
                $value = sanitize_hex_color((string) $value);
                if (!$value) {
                    return new \WP_Error('admincafe_color', __('Use a valid hexadecimal color.', 'fandoogh-rest'), ['status' => 400]);
                }
            } elseif (in_array($key, ['dine_in_enabled', 'pickup_enabled', 'delivery_enabled', 'ordering_paused', 'notification_sound'], true)) {
                $value = rest_sanitize_boolean($value);
            } elseif (str_contains($key, '_size_')) {
                $value = max(10, min(48, (int) $value));
            } elseif (str_contains($key, '_weight')) {
                $value = max(100, min(900, (int) round(((int) $value) / 100) * 100));
            } elseif (in_array($key, ['logo_id', 'cover_id'], true)) {
                $value = absint($value);
                if ($value && (!wp_attachment_is_image($value) || !Branches::ownsMedia($value))) {
                    return new \WP_Error('admincafe_image', __('Select an image from the media library.', 'fandoogh-rest'), ['status' => 400]);
                }
            } elseif ($key === 'menu_page_id') {
                $value = absint($value);
                if ($value && (get_post_type($value) !== 'page' || get_post_status($value) !== 'publish')) {
                    return new \WP_Error('admincafe_page', __('Select a published WordPress page.', 'fandoogh-rest'), ['status' => 400]);
                }
            } elseif ($key === 'menu_category_ids') {
                foreach ($value as $categoryId) { if (!Branches::ownsCategory(absint($categoryId))) { return new \WP_Error('rest_forbidden', __('Category belongs to another branch.', 'fandoogh-rest'), ['status' => 403]); } }
                $value = array_values(array_unique(array_filter(array_map('absint', (array) $value), static fn(int $id): bool => term_exists($id, 'product_cat') !== null && term_exists($id, 'product_cat') !== 0)));
            } elseif ($key === 'messages') {
                $value = array_intersect_key((array) $value, self::defaults()['messages']);
                $value = array_merge($settings['messages'], array_map(static fn($v): string => sanitize_text_field(mb_substr((string) $v, 0, 300)), $value));
            } elseif ($key === 'table_default_mode') {
                $value = in_array($value, ['menu', 'order'], true) ? $value : 'menu';
            } elseif ($key === 'layout') {
                $value = in_array($value, ['grid', 'list'], true) ? $value : 'grid';
            } elseif ($key === 'preparation_minutes') {
                $value = max(1, min(240, absint($value)));
            } elseif ($key === 'qr_size') {
                $value = max(128, min(2048, absint($value)));
            } elseif ($key === 'currency_code') {
                $value = strtoupper(sanitize_text_field((string) $value));
                if (!array_key_exists($value, get_woocommerce_currencies())) {
                    return new \WP_Error('admincafe_currency', __('Choose a supported WooCommerce currency.', 'fandoogh-rest'), ['status' => 400]);
                }
            } elseif ($key === 'font_family') {
                $value = in_array($value, ['Vazirmatn', 'IRANSans', 'Dana', 'system'], true) ? $value : 'Vazirmatn';
            } elseif ($key === 'custom_font_url') {
                $value = esc_url_raw((string) $value);
                if ($value && (wp_parse_url($value, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST) || !preg_match('/\.(woff2?|ttf|otf)$/i', (string) wp_parse_url($value, PHP_URL_PATH)))) {
                    return new \WP_Error('admincafe_font', __('Use a font file hosted on this site.', 'fandoogh-rest'), ['status' => 400]);
                }
            } else {
                $value = sanitize_textarea_field(mb_substr((string) $value, 0, $key === 'restaurant_address' ? 1000 : 300));
            }
            $settings[$key] = $value;
        }
        if (!in_array($settings['default_language'], $settings['enabled_languages'], true)) {
            return new \WP_Error('admincafe_languages', __('The default language must be enabled.', 'fandoogh-rest'), ['status' => 400]);
        }
        if ($settings['menu_slug'] === $settings['panel_slug']) {
            return new \WP_Error('admincafe_slug_conflict', __('Menu and panel addresses must be different.', 'fandoogh-rest'), ['status' => 400]);
        }
        $old = self::all();
        Branches::saveSettings(Branches::current(), $settings);
        $global = (array) get_option('admincafe_settings', []);
        update_option('admincafe_settings', Branches::current() === Branches::defaultId() ? $settings : array_replace($global, array_intersect_key($settings, array_flip(Branches::GLOBAL_SETTINGS))), false);
        do_action('fandoogh_branch_settings_updated', Branches::current());
        if (isset($input['content_translations'])) { do_action('admincafe_translation_manual_input', 'settings', Branches::current() === Branches::defaultId() ? 0 : Branches::current(), $input['content_translations']); }
        if ($old['currency_code'] !== $settings['currency_code']) {
            update_option('woocommerce_currency', $settings['currency_code']);
        }
        if ($old['menu_slug'] !== $settings['menu_slug'] || $old['panel_slug'] !== $settings['panel_slug']) {
            Routes::rewrites();
            flush_rewrite_rules(false);
        }
        return $settings;
    }

    public static function publicSettings(?string $language = null): array
    {
        $settings = self::all();
        if ($language && $language !== 'fa') {
            $translated = $settings['content_translations'][$language] ?? [];
            foreach (['restaurant_name', 'tagline', 'restaurant_address', 'hours_text'] as $field) {
                if (!empty($translated[$field])) { $settings[$field] = $translated[$field]; }
            }
            if (empty($translated['tagline']) && $settings['tagline'] === self::defaults()['tagline']) {
                $settings['tagline'] = Language::strings($language)[$settings['tagline']] ?? $settings['tagline'];
            }
            foreach ($settings['messages'] as $field => $text) {
                if (!empty($translated['messages'][$field])) {
                    $settings['messages'][$field] = $translated['messages'][$field];
                } elseif ($text === self::defaults()['messages'][$field]) {
                    $settings['messages'][$field] = Language::strings($language)[$text] ?? $text;
                }
            }
        }
        $appearance = Appearance::publicSettings($settings, Appearance::menuScope());
        unset($settings['panel_slug'], $settings['menu_page_id'], $settings['menu_category_ids'], $settings['content_translations'], $settings['custom_css']);
        $settings = array_replace($settings, $appearance);
        $settings['logo'] = $settings['logo_id'] ? (wp_get_attachment_image_url($settings['logo_id'], 'medium') ?: '') : '';
        $settings['cover'] = $settings['cover_id'] ? (wp_get_attachment_image_url($settings['cover_id'], 'large') ?: '') : '';
        return $settings;
    }

    private static function sanitizeTranslations(mixed $input, array $stored): array|\WP_Error
    {
        $error = static fn() => new \WP_Error('admincafe_translations', __('Invalid content translations.', 'fandoogh-rest'), ['status' => 400]);
        if (!is_array($input) || count($input) > 3) { return $error(); }
        foreach ($input as $language => $fields) {
            if (!in_array($language, ['en', 'zh', 'tr'], true) || !is_array($fields) || count($fields) > 5) { return $error(); }
            foreach ($fields as $field => $value) {
                if ($field === 'messages') {
                    if (!is_array($value) || count($value) > 3) { return $error(); }
                    foreach ($value as $name => $text) {
                        if (!isset(self::defaults()['messages'][$name]) || !is_string($text) || mb_strlen($text) > 300) { return $error(); }
                        $stored[$language]['messages'][$name] = sanitize_text_field($text);
                    }
                } else {
                    $limit = $field === 'restaurant_address' ? 1000 : 300;
                    if (!in_array($field, ['restaurant_name', 'tagline', 'restaurant_address', 'hours_text'], true) || !is_string($value) || mb_strlen($value) > $limit) { return $error(); }
                    $stored[$language][$field] = sanitize_textarea_field($value);
                }
            }
        }
        return $stored;
    }

    public static function menuUrl(): string
    {
        return Branches::menuUrl();
    }

    public static function panelUrl(): string
    {
        return home_url('/' . self::get('panel_slug') . '/');
    }
}
