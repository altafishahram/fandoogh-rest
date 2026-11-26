<?php
namespace RMM\DesignSystem;

if (!defined('ABSPATH')) {
    exit;
}

class TokenSchema {

    public static function get_default_tokens($mode = 'light') {
        if ($mode === 'dark') {
            return [
                /* ── Base Colors ── */
                'primary'          => '#38bdf8',
                'primary_hover'    => '#0ea5e9',
                'primary_soft'     => 'rgba(56, 189, 248, 0.15)',
                'on_primary'       => '#0f172a',
                'bg'               => '#0b0f17',
                'card'             => '#131b2e',
                'border'           => '#1e293b',
                'subtle'           => '#1a233a',
                'text'             => '#f8fafc',
                'text_secondary'   => '#cbd5e1',
                'muted'            => '#64748b',
                'success'          => '#34d399',
                'danger'           => '#f87171',
                'warning'          => '#fbbf24',
                /* ── Typography ── */
                'font_family'      => "'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
                'title_size'       => '16px',
                'desc_size'        => '13px',
                'price_size'       => '15px',
                'font_weight'      => '600',
                /* ── Shape ── */
                'radius_card'      => '16px',
                'radius_btn'       => '12px',
                'radius_input'     => '10px',
                /* ── Spacing ── */
                'pad_card'         => '16px',
                'gap_grid'         => '16px',
                /* ── Shadows ── */
                'shadow_card'      => '0 4px 20px -2px rgba(0, 0, 0, 0.4)',
                'shadow_btn'       => '0 2px 10px -1px rgba(56, 189, 248, 0.35)',
                /* ── Icons ── */
                'icon_color'       => '#94a3b8',
                'icon_size'        => '18px',
                /* ── Badge ── */
                'badge_bg'         => '#fbbf24',
                'badge_text'       => '#0f172a',
                'badge_radius'     => '999px',
                /* ── Food Card ── */
                'card_image_ratio' => '4 / 3',
                'card_image_height'=> '180px',
                'card_image_fit'   => 'cover',
                'card_image_radius'=> '12px',
                'card_image_list_size' => '100px',
                'card_layout'      => 'grid',
                'btn_pad_y'        => '8px',
                'btn_pad_x'        => '14px',
                'btn_style'        => 'solid',
                /* ── Modal ── */
                'modal_backdrop'   => 'rgba(0, 0, 0, 0.62)',
                'modal_blur'       => '8px',
                'modal_radius'     => '20px',
                'qty_bg'           => '#1a233a',
                /* ── Categories ── */
                'cat_style'        => 'pill',
                'cat_gap'          => '8px',
                'cat_icon'         => '18px',
                'cat_idle_bg'      => '#1a233a',
                'cat_idle_text'    => '#cbd5e1',
                'cat_active_bg'    => '#38bdf8',
                'cat_active_text'  => '#0f172a',
                /* ── Category Sheet (popup category grid) ── */
                'sheet_bg'               => '#131b2e',
                'sheet_title'            => '#f8fafc',
                'sheet_backdrop'         => 'rgba(0, 0, 0, 0.62)',
                'sheet_card_bg'          => '#1a233a',
                'sheet_card_border'      => '#1e293b',
                'sheet_card_text'        => '#cbd5e1',
                'sheet_card_active_bg'   => '#38bdf8',
                'sheet_card_active_text' => '#0f172a',
                'sheet_radius'           => '24px',
                'sheet_pad'              => '18px',
                'sheet_gap'              => '10px',
                'sheet_icon'             => '56px',
                'sheet_card_radius'      => '16px',
                /* ── Search ── */
                'search_bg'        => '#131b2e',
                'search_border'    => '#1e293b',
                'search_icon'      => '#94a3b8',
                'search_placeholder' => '#64748b',
                'search_radius'    => '12px',
                /* ── Language / Theme toggle ── */
                'lang_bg'          => '#131b2e',
                'lang_text'        => '#f8fafc',
                'lang_border'      => '#1e293b',
                'lang_radius'      => '12px',
                'toggle_bg'        => '#1a233a',
                'toggle_icon'      => '#f8fafc',
                /* ── Language Entrance Popup Modal ── */
                'lang_modal_bg'             => '#131b2e',
                'lang_modal_backdrop'       => 'rgba(0, 0, 0, 0.82)',
                'lang_modal_blur'           => '8px',
                'lang_modal_radius'         => '24px',
                'lang_modal_title'          => '#f8fafc',
                'lang_modal_card_bg'        => '#1a233a',
                'lang_modal_card_border'    => '#1e293b',
                'lang_modal_card_text'      => '#f8fafc',
                'lang_modal_card_active_bg' => '#38bdf8',
                'lang_modal_card_active_text' => '#0f172a',
                /* ── Floating Cart ── */
                'cart_bg'          => '#131b2e',
                'cart_text'        => '#f8fafc',
                'cart_radius'      => '16px',
                'cart_badge_bg'    => '#38bdf8',
                'cart_badge_text'  => '#0f172a',
                'cart_dock'        => 'bottom',
                /* ── Catalog Selection List Popup (لیست انتخاب‌های من) ── */
                'catalog_backdrop'    => 'rgba(0, 0, 0, 0.72)',
                'catalog_blur'        => '8px',
                'catalog_bg'          => '#131b2e',
                'catalog_border'      => '#1e293b',
                'catalog_text'        => '#f8fafc',
                'catalog_muted'       => '#cbd5e1',
                'catalog_card_bg'     => '#1a233a',
                'catalog_card_border' => '#1e293b',
                'catalog_accent'      => '#38bdf8',
                'catalog_btn_bg'      => '#38bdf8',
                'catalog_btn_text'    => '#0f172a',
                'catalog_wa_bg'       => '#25d366',
                'catalog_radius'      => '22px',
                'catalog_card_radius' => '16px',
            ];
        }

        /* ── Light mode defaults ── */
        return [
            /* ── Base Colors ── */
            'primary'          => '#0284c7',
            'primary_hover'    => '#0369a1',
            'primary_soft'     => 'rgba(2, 132, 199, 0.12)',
            'on_primary'       => '#ffffff',
            'bg'               => '#f8fafc',
            'card'             => '#ffffff',
            'border'           => '#e2e8f0',
            'subtle'           => '#f1f5f9',
            'text'             => '#0f172a',
            'text_secondary'   => '#475569',
            'muted'            => '#94a3b8',
            'success'          => '#10b981',
            'danger'           => '#ef4444',
            'warning'          => '#f59e0b',
            /* ── Typography ── */
            'font_family'      => "'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
            'title_size'       => '16px',
            'desc_size'        => '13px',
            'price_size'       => '15px',
            'font_weight'      => '600',
            /* ── Shape ── */
            'radius_card'      => '16px',
            'radius_btn'       => '12px',
            'radius_input'     => '10px',
            /* ── Spacing ── */
            'pad_card'         => '16px',
            'gap_grid'         => '16px',
            /* ── Shadows ── */
            'shadow_card'      => '0 4px 16px -2px rgba(15, 23, 42, 0.06)',
            'shadow_btn'       => '0 2px 8px -1px rgba(2, 132, 199, 0.3)',
            /* ── Icons ── */
            'icon_color'       => '#64748b',
            'icon_size'        => '18px',
            /* ── Badge ── */
            'badge_bg'         => '#f59e0b',
            'badge_text'       => '#ffffff',
            'badge_radius'     => '999px',
            /* ── Food Card ── */
            'card_image_ratio' => '4 / 3',
            'card_image_height'=> '180px',
            'card_image_fit'   => 'cover',
            'card_image_radius'=> '12px',
            'card_image_list_size' => '100px',
            'card_layout'      => 'grid',
            'btn_pad_y'        => '8px',
            'btn_pad_x'        => '14px',
            'btn_style'        => 'solid',
            /* ── Modal ── */
            'modal_backdrop'   => 'rgba(15, 23, 42, 0.55)',
            'modal_blur'       => '8px',
            'modal_radius'     => '20px',
            'qty_bg'           => '#f1f5f9',
            /* ── Categories ── */
            'cat_style'        => 'pill',
            'cat_gap'          => '8px',
            'cat_icon'         => '18px',
            'cat_idle_bg'      => '#f1f5f9',
            'cat_idle_text'    => '#475569',
            'cat_active_bg'    => '#0284c7',
            'cat_active_text'  => '#ffffff',
            /* ── Category Sheet (popup category grid) ── */
            'sheet_bg'               => '#ffffff',
            'sheet_title'            => '#0f172a',
            'sheet_backdrop'         => 'rgba(15, 23, 42, 0.55)',
            'sheet_card_bg'          => '#f8fafc',
            'sheet_card_border'      => '#e2e8f0',
            'sheet_card_text'        => '#334155',
            'sheet_card_active_bg'   => '#0284c7',
            'sheet_card_active_text' => '#ffffff',
            'sheet_radius'           => '24px',
            'sheet_pad'              => '18px',
            'sheet_gap'              => '10px',
            'sheet_icon'             => '56px',
            'sheet_card_radius'      => '16px',
            /* ── Search ── */
            'search_bg'        => '#ffffff',
            'search_border'    => '#e2e8f0',
            'search_icon'      => '#94a3b8',
            'search_placeholder' => '#94a3b8',
            'search_radius'    => '12px',
            /* ── Language / Theme toggle ── */
            'lang_bg'          => '#ffffff',
            'lang_text'        => '#0f172a',
            'lang_border'      => '#e2e8f0',
            'lang_radius'      => '12px',
            'toggle_bg'        => '#f1f5f9',
            'toggle_icon'      => '#0f172a',
            /* ── Language Entrance Popup Modal ── */
            'lang_modal_bg'             => '#ffffff',
            'lang_modal_backdrop'       => 'rgba(15, 23, 42, 0.75)',
            'lang_modal_blur'           => '8px',
            'lang_modal_radius'         => '24px',
            'lang_modal_title'          => '#0f172a',
            'lang_modal_card_bg'        => '#f8fafc',
            'lang_modal_card_border'    => '#e2e8f0',
            'lang_modal_card_text'      => '#0f172a',
            'lang_modal_card_active_bg' => '#0284c7',
            'lang_modal_card_active_text' => '#ffffff',
            /* ── Floating Cart ── */
            'cart_bg'          => '#0f172a',
            'cart_text'        => '#ffffff',
            'cart_radius'      => '16px',
            'cart_badge_bg'    => '#0284c7',
            'cart_badge_text'  => '#ffffff',
            'cart_dock'        => 'bottom',
            /* ── Catalog Selection List Popup (لیست انتخاب‌های من) ── */
            'catalog_backdrop'    => 'rgba(15, 23, 42, 0.62)',
            'catalog_blur'        => '8px',
            'catalog_bg'          => '#ffffff',
            'catalog_border'      => '#e2e8f0',
            'catalog_text'        => '#0f172a',
            'catalog_muted'       => '#64748b',
            'catalog_card_bg'     => '#f8fafc',
            'catalog_card_border' => '#e2e8f0',
            'catalog_accent'      => '#0284c7',
            'catalog_btn_bg'      => '#0f172a',
            'catalog_btn_text'    => '#ffffff',
            'catalog_wa_bg'       => '#25d366',
            'catalog_radius'      => '22px',
            'catalog_card_radius' => '16px',
        ];
    }

    public static function get_default_settings() {
        return [
            'version'          => '1.0.0',
            'activeMode'       => 'light',
            'default_theme'    => 'dark',
            'elementor_bridge' => false,
            'light'            => self::get_default_tokens('light'),
            'dark'             => self::get_default_tokens('dark'),
            'saved_at'         => '',
        ];
    }

    /* ── Sanitizers ─────────────────────────────────────── */

    public static function sanitize_color($val, $fallback = '#0284c7') {
        if (!is_scalar($val)) {
            return $fallback;
        }
        $val = trim((string) $val);

        // HEX color (#rgb / #rrggbb / #rrggbbaa)
        if (preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6}|[a-fA-F0-9]{8})$/', $val)) {
            return $val;
        }

        // RGB or RGBA
        if (preg_match('/^rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*(?:,\s*([0-9]*\.?[0-9]+)\s*)?\)$/i', $val, $m)) {
            $r = min(255, max(0, (int) $m[1]));
            $g = min(255, max(0, (int) $m[2]));
            $b = min(255, max(0, (int) $m[3]));
            $a = isset($m[4]) ? min(1, max(0, (float) $m[4])) : 1;
            return "rgba({$r}, {$g}, {$b}, {$a})";
        }

        // CSS variable reference
        if (preg_match('/^var\(--[a-zA-Z0-9_-]+(?:,\s*#[a-fA-F0-9]{3,8})?\)$/', $val)) {
            return $val;
        }

        return $fallback;
    }

    public static function sanitize_css_size($val, $fallback = '16px') {
        if (!is_scalar($val)) {
            return $fallback;
        }
        $val = trim((string) $val);
        if ('auto' === strtolower($val)) {
            return 'auto';
        }
        if (preg_match('/^[0-9]+(\.[0-9]+)?(px|rem|em|%|vh|vw)$/i', $val)) {
            return $val;
        }
        if (is_numeric($val)) {
            return absint($val) . 'px';
        }
        return $fallback;
    }

    public static function sanitize_shadow($val, $fallback = 'none') {
        if (!is_scalar($val)) {
            return $fallback;
        }
        $val = trim((string) $val);
        if (preg_match('/[;<>{}]|expression|url|javascript/i', $val)) {
            return $fallback;
        }
        if ('none' === strtolower($val)) {
            return 'none';
        }
        // Accepts optional "inset", offset-x, offset-y, (blur, spread optional), color
        if (preg_match('/^(inset\s+)?(-?[0-9]+px\s+){2,4}(rgba?\([^)]+\)|#[0-9a-fA-F]{3,8}|transparent)/i', $val)) {
            return $val;
        }
        return $fallback;
    }

    public static function sanitize_font_family($val, $fallback = "'Vazirmatn', sans-serif") {
        if (!is_scalar($val)) {
            return $fallback;
        }
        $val = trim((string) $val);
        if (preg_match('/[;<>{}]|expression|url|javascript/i', $val)) {
            return $fallback;
        }
        if (preg_match("/^[a-zA-Z0-9\s,\-_'\"\\\\]+$/", $val)) {
            return $val;
        }
        return $fallback;
    }

    /**
     * Sanitize a CSS size value that may be "Npx" (blur, etc.).
     * Strips "px" suffix if present, ensures non-negative integer, re-adds "px".
     */
    public static function sanitize_px_number($val, $fallback = '8px') {
        if (!is_scalar($val)) {
            return $fallback;
        }
        $val = trim((string) $val);
        // Accept "Npx" or plain integer
        if (preg_match('/^([0-9]+)(px)?$/i', $val, $m)) {
            return ((int) $m[1]) . 'px';
        }
        return $fallback;
    }

    /**
     * Sanitize aspect-ratio value like "4 / 3" or "16 / 9" or "auto".
     */
    public static function sanitize_aspect_ratio($val, $fallback = '4 / 3') {
        if (!is_scalar($val)) {
            return $fallback;
        }
        $val = trim((string) $val);
        if ('auto' === strtolower($val)) {
            return 'auto';
        }
        if (preg_match('/^\d+\s*\/\s*\d+$/', $val)) {
            $parts = preg_split('/\s*\/\s*/', $val);
            return (int) $parts[0] . ' / ' . (int) $parts[1];
        }
        return $fallback;
    }

    /* ── Main sanitize_tokens ─────────────────────────── */

    public static function sanitize_tokens($raw, $mode = 'light') {
        $defaults = self::get_default_tokens($mode);
        if (!is_array($raw)) {
            return $defaults;
        }

        $clean = [];

        /* ── Base colors ── */
        $color_keys = [
            'primary', 'primary_hover', 'primary_soft', 'on_primary',
            'bg', 'card', 'border', 'subtle', 'text', 'text_secondary',
            'muted', 'success', 'danger', 'warning', 'icon_color',
            /* Badge */
            'badge_bg', 'badge_text',
            /* Categories */
            'cat_idle_bg', 'cat_idle_text', 'cat_active_bg', 'cat_active_text',
            /* Category Sheet */
            'sheet_bg', 'sheet_title', 'sheet_backdrop',
            'sheet_card_bg', 'sheet_card_border', 'sheet_card_text',
            'sheet_card_active_bg', 'sheet_card_active_text',
            /* Search */
            'search_bg', 'search_border', 'search_icon', 'search_placeholder',
            /* Language / toggle */
            'lang_bg', 'lang_text', 'lang_border',
            'toggle_bg', 'toggle_icon',
            /* Language Entrance Modal */
            'lang_modal_bg', 'lang_modal_backdrop', 'lang_modal_title',
            'lang_modal_card_bg', 'lang_modal_card_border', 'lang_modal_card_text',
            'lang_modal_card_active_bg', 'lang_modal_card_active_text',
            /* Floating cart */
            'cart_bg', 'cart_text', 'cart_badge_bg', 'cart_badge_text',
            /* Catalog Selection List Popup */
            'catalog_backdrop', 'catalog_bg', 'catalog_border', 'catalog_text', 'catalog_muted',
            'catalog_card_bg', 'catalog_card_border', 'catalog_accent',
            'catalog_btn_bg', 'catalog_btn_text', 'catalog_wa_bg',
            /* Modal */
            'modal_backdrop', 'qty_bg',
        ];
        foreach ($color_keys as $k) {
            $clean[$k] = isset($raw[$k])
                ? self::sanitize_color($raw[$k], $defaults[$k])
                : $defaults[$k];
        }

        /* ── Sizes & Spacing ── */
        $size_keys = [
            'title_size', 'desc_size', 'price_size',
            'radius_card', 'radius_btn', 'radius_input',
            'pad_card', 'gap_grid', 'icon_size',
            /* Badge */
            'badge_radius',
            /* Card Image */
            'card_image_height', 'card_image_radius', 'card_image_list_size',
            /* Button padding */
            'btn_pad_y', 'btn_pad_x',
            /* Categories */
            'cat_gap', 'cat_icon',
            /* Category Sheet */
            'sheet_radius', 'sheet_pad', 'sheet_gap', 'sheet_icon', 'sheet_card_radius',
            /* Search */
            'search_radius',
            /* Language */
            'lang_radius',
            /* Language Modal */
            'lang_modal_radius',
            /* Modal */
            'modal_radius',
            /* Cart */
            'cart_radius',
            /* Catalog Selection List Popup */
            'catalog_radius', 'catalog_card_radius',
        ];
        foreach ($size_keys as $k) {
            $clean[$k] = isset($raw[$k])
                ? self::sanitize_css_size($raw[$k], $defaults[$k])
                : $defaults[$k];
        }

        /* ── Px-only values (blur) ── */
        $clean['modal_blur'] = isset($raw['modal_blur'])
            ? self::sanitize_px_number($raw['modal_blur'], $defaults['modal_blur'])
            : $defaults['modal_blur'];

        $clean['lang_modal_blur'] = isset($raw['lang_modal_blur'])
            ? self::sanitize_px_number($raw['lang_modal_blur'], $defaults['lang_modal_blur'])
            : $defaults['lang_modal_blur'];

        $clean['catalog_blur'] = isset($raw['catalog_blur'])
            ? self::sanitize_px_number($raw['catalog_blur'], $defaults['catalog_blur'])
            : $defaults['catalog_blur'];

        /* ── Font weight ── */
        $allowed_weights = ['300', '400', '500', '600', '700', '800', '900', 'normal', 'bold'];
        $clean['font_weight'] = (isset($raw['font_weight']) && in_array((string) $raw['font_weight'], $allowed_weights, true))
            ? (string) $raw['font_weight']
            : $defaults['font_weight'];

        /* ── Font family ── */
        $clean['font_family'] = isset($raw['font_family'])
            ? self::sanitize_font_family($raw['font_family'], $defaults['font_family'])
            : $defaults['font_family'];

        /* ── Shadows ── */
        $shadow_keys = ['shadow_card', 'shadow_btn'];
        foreach ($shadow_keys as $k) {
            $clean[$k] = isset($raw[$k])
                ? self::sanitize_shadow($raw[$k], $defaults[$k])
                : $defaults[$k];
        }

        /* ── Aspect ratio ── */
        $clean['card_image_ratio'] = isset($raw['card_image_ratio'])
            ? self::sanitize_aspect_ratio($raw['card_image_ratio'], $defaults['card_image_ratio'])
            : $defaults['card_image_ratio'];

        /* ── Enum: card_image_fit ── */
        $allowed_fits = ['cover', 'contain', 'fill', 'none', 'scale-down'];
        $clean['card_image_fit'] = (isset($raw['card_image_fit']) && in_array($raw['card_image_fit'], $allowed_fits, true))
            ? $raw['card_image_fit']
            : $defaults['card_image_fit'];

        /* ── Enum: card_layout ── */
        $allowed_layouts = ['grid', 'list'];
        $clean['card_layout'] = (isset($raw['card_layout']) && in_array($raw['card_layout'], $allowed_layouts, true))
            ? $raw['card_layout']
            : $defaults['card_layout'];

        /* ── Enum: btn_style ── */
        $allowed_btn_styles = ['solid', 'outline'];
        $clean['btn_style'] = (isset($raw['btn_style']) && in_array($raw['btn_style'], $allowed_btn_styles, true))
            ? $raw['btn_style']
            : $defaults['btn_style'];

        /* ── Enum: cat_style ── */
        $allowed_cat_styles = ['pill', 'underline', 'button'];
        $clean['cat_style'] = (isset($raw['cat_style']) && in_array($raw['cat_style'], $allowed_cat_styles, true))
            ? $raw['cat_style']
            : $defaults['cat_style'];

        /* ── Enum: cart_dock ── */
        $allowed_docks = ['bottom', 'corner'];
        $clean['cart_dock'] = (isset($raw['cart_dock']) && in_array($raw['cart_dock'], $allowed_docks, true))
            ? $raw['cart_dock']
            : $defaults['cart_dock'];

        return $clean;
    }

    public static function sanitize_settings($raw) {
        $defaults = self::get_default_settings();
        if (!is_array($raw)) {
            return $defaults;
        }

        $clean = [
            'version'          => '1.0.0',
            'activeMode'       => (isset($raw['activeMode']) && $raw['activeMode'] === 'dark') ? 'dark' : 'light',
            'default_theme'    => (isset($raw['default_theme']) && $raw['default_theme'] === 'light') ? 'light' : 'dark',
            'elementor_bridge' => !empty($raw['elementor_bridge']),
            'light'            => isset($raw['light']) ? self::sanitize_tokens($raw['light'], 'light') : $defaults['light'],
            'dark'             => isset($raw['dark'])  ? self::sanitize_tokens($raw['dark'],  'dark')  : $defaults['dark'],
            'saved_at'         => gmdate('c'),
        ];

        return $clean;
    }
}
