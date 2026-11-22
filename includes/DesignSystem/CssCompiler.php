<?php
namespace RMM\DesignSystem;

if (!defined('ABSPATH')) {
    exit;
}

class CssCompiler {

    /**
     * Build a CSS custom-property block for the given selector.
     * All token keys become --rmm-ds-* variables.
     */
    public static function build_variables_block($tokens, $selector) {
        $lines   = [];
        $lines[] = "{$selector} {";

        foreach ($tokens as $key => $val) {
            $css_var  = '--rmm-ds-' . str_replace('_', '-', $key);
            $safe_val = str_replace([';', '{', '}', '<', '>'], '', (string) $val);
            $lines[]  = "  {$css_var}: {$safe_val};";
        }

        $lines[] = '}';
        return implode("\n", $lines);
    }

    /**
     * Full component mapping CSS.
     * Uses only --rmm-ds-* variables and .rmm-* selectors.
     * Never touches WooCommerce / Elementor global selectors.
     */
    public static function get_component_mapping_css() {
        return <<<CSS
/* ==========================================================================
   Fondogh Online Menu — Component Design System Mapping v2.2
   Strictly scoped to .rmm-* selectors. Never overrides WooCommerce globals.
   ========================================================================== */

/* ── Font family on all RMM wrappers ───────────────────────────────────── */
.rmm-menu-widget-wrapper,
.rmm-food-card,
.rmm-modal-backdrop,
.rmm-modal-content,
.rmm-food-details-modal,
.rmm-category-nav-wrapper,
.rmm-live-search-bar,
.rmm-floating-cart-bar,
.rmm-theme-toggle-widget-wrap,
.rmm-lang-switcher,
.rmm-entrance-modal-backdrop,
.rmm-entrance-card {
  font-family: var(--rmm-ds-font-family);
  box-sizing: border-box;
}

.rmm-menu-widget-wrapper {
  gap: var(--rmm-ds-gap-grid);
}

/* ── Food Card (Grid & List) ────────────────────────────────────────────── */
.rmm-food-card {
  background:    var(--rmm-ds-card)        !important;
  border:        1px solid var(--rmm-ds-border) !important;
  border-radius: var(--rmm-ds-radius-card) !important;
  padding:       var(--rmm-ds-pad-card)    !important;
  box-shadow:    var(--rmm-ds-shadow-card) !important;
  color:         var(--rmm-ds-text)        !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.rmm-food-card:hover {
  border-color: var(--rmm-ds-primary)    !important;
  box-shadow:   var(--rmm-ds-shadow-btn) !important;
}

/* Card image ratio, height & fit */
.rmm-food-card .rmm-food-image,
.rmm-food-card .rmm-card-img-wrap,
.rmm-food-card .rmm-card-image-wrap {
  aspect-ratio: var(--rmm-ds-card-image-ratio);
  height: var(--rmm-ds-card-image-height);
  overflow: hidden;
  border-radius: var(--rmm-ds-card-image-radius);
  position: relative;
  margin-bottom: 10px;
  background: var(--rmm-ds-subtle);
}
.rmm-food-card .rmm-card-img,
.rmm-food-card .rmm-food-image img {
  width: 100%;
  height: 100%;
  object-fit: var(--rmm-ds-card-image-fit);
  display: block;
  border-radius: var(--rmm-ds-card-image-radius);
}

/* List view image dimensions */
.rmm-food-card.rmm-card-layout--list .rmm-card-image-wrap,
[data-card-layout="list"] .rmm-food-card .rmm-card-image-wrap {
  width: var(--rmm-ds-card-image-list-size) !important;
  min-width: var(--rmm-ds-card-image-list-size) !important;
  height: var(--rmm-ds-card-image-list-size) !important;
  aspect-ratio: 1 / 1 !important;
}

/* Card titles & text */
.rmm-food-card .rmm-food-title,
.rmm-food-card h3.rmm-title {
  color:       var(--rmm-ds-text)        !important;
  font-size:   var(--rmm-ds-title-size)  !important;
  font-weight: var(--rmm-ds-font-weight) !important;
  margin-bottom: 6px;
}
.rmm-food-card .rmm-food-desc,
.rmm-food-card .rmm-desc {
  color:     var(--rmm-ds-text-secondary) !important;
  font-size: var(--rmm-ds-desc-size)      !important;
  line-height: 1.5;
}
.rmm-food-card .rmm-food-price,
.rmm-food-card .rmm-price {
  color:       var(--rmm-ds-primary)    !important;
  font-size:   var(--rmm-ds-price-size) !important;
  font-weight: 700                      !important;
}

/* Badge (discount / chef special) */
.rmm-food-card .rmm-badge,
.rmm-badge,
.rmm-card-badge,
.rmm-food-card .rmm-card-badge {
  background:    var(--rmm-ds-badge-bg)     !important;
  color:         var(--rmm-ds-badge-text)   !important;
  border-radius: var(--rmm-ds-badge-radius) !important;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
}

/* Add button */
.rmm-food-card .rmm-add-btn,
.rmm-food-card .rmm-card-action-row .rmm-add-btn,
.rmm-food-card .rmm-card-action-row .rmm-btn-order,
.rmm-modal-add-btn,
.rmm-btn-primary {
  background:    var(--rmm-ds-primary)     !important;
  color:         var(--rmm-ds-on-primary)  !important;
  border:        none                      !important;
  border-radius: var(--rmm-ds-radius-btn) !important;
  box-shadow:    var(--rmm-ds-shadow-btn);
  padding: var(--rmm-ds-btn-pad-y) var(--rmm-ds-btn-pad-x) !important;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.15s ease;
}
/* Outline style */
.rmm-food-card.rmm-btn-style--outline .rmm-add-btn,
.rmm-food-card.rmm-btn-style--outline .rmm-card-action-row .rmm-add-btn,
.rmm-btn-primary.rmm-btn-style--outline {
  background:    transparent                !important;
  color:         var(--rmm-ds-primary)      !important;
  border:        2px solid var(--rmm-ds-primary) !important;
}
.rmm-food-card .rmm-add-btn:hover,
.rmm-food-card .rmm-card-action-row .rmm-add-btn:hover,
.rmm-food-card .rmm-card-action-row .rmm-btn-order:hover,
.rmm-modal-add-btn:hover,
.rmm-btn-primary:hover {
  background: var(--rmm-ds-primary-hover) !important;
}

.rmm-food-card .rmm-food-meta-row,
.rmm-food-card .rmm-food-ingredients {
  color: var(--rmm-ds-muted) !important;
  font-size: var(--rmm-ds-desc-size) !important;
}

/* ── Food Details Modal / Popup ─────────────────────────────────────────── */
.rmm-modal-backdrop {
  background:     var(--rmm-ds-modal-backdrop) !important;
  backdrop-filter: blur(var(--rmm-ds-modal-blur)) !important;
  -webkit-backdrop-filter: blur(var(--rmm-ds-modal-blur)) !important;
  position: fixed;
  inset: 0;
  z-index: 9000;
  display: flex;
  align-items: center;
  justify-content: center;
}
.rmm-modal-content,
.rmm-food-details-modal,
.rmm-modal-box {
  background:    var(--rmm-ds-card)        !important;
  border:        1px solid var(--rmm-ds-border) !important;
  border-radius: var(--rmm-ds-modal-radius) !important;
  box-shadow:    var(--rmm-ds-shadow-card)  !important;
  color:         var(--rmm-ds-text)         !important;
  padding:       calc(var(--rmm-ds-pad-card) * 1.25) !important;
  max-width: 540px;
  width: 94vw;
  position: relative;
  font-family:   var(--rmm-ds-font-family);
}
.rmm-modal-content .rmm-modal-title,
.rmm-modal-food-title {
  color:       var(--rmm-ds-text)                   !important;
  font-size:   calc(var(--rmm-ds-title-size) * 1.2) !important;
  font-weight: var(--rmm-ds-font-weight)            !important;
}
.rmm-modal-content .rmm-modal-close,
.rmm-modal-close-btn {
  color:     var(--rmm-ds-icon-color) !important;
  font-size: var(--rmm-ds-icon-size)  !important;
}
.rmm-modal-food-price,
.rmm-modal-sale-price,
.rmm-modal-regular-price {
  color:     var(--rmm-ds-primary)    !important;
  font-size: var(--rmm-ds-price-size) !important;
}
.rmm-modal-desc-text,
.rmm-modal-ingredients-text {
  color:     var(--rmm-ds-text-secondary) !important;
  font-size: var(--rmm-ds-desc-size)      !important;
}
.rmm-modal-section-box {
  background:    var(--rmm-ds-subtle)     !important;
  border:        1px solid var(--rmm-ds-border) !important;
  border-radius: var(--rmm-ds-radius-input) !important;
}
.rmm-modal-section-title {
  color: var(--rmm-ds-text) !important;
}

/* Quantity counter inside modal */
.rmm-qty-counter,
.rmm-qty-controls {
  background:    var(--rmm-ds-qty-bg)      !important;
  border-radius: var(--rmm-ds-radius-btn)  !important;
}
.rmm-qty-btn {
  color: var(--rmm-ds-primary) !important;
}
.rmm-qty-val {
  color: var(--rmm-ds-text) !important;
}

/* ── Category Navigation Bar ────────────────────────────────────────────── */
.rmm-category-nav-wrapper {
  background:    var(--rmm-ds-card)   !important;
  border-color:  var(--rmm-ds-border) !important;
  border-bottom: 1px solid var(--rmm-ds-border);
  gap:           var(--rmm-ds-cat-gap);
  display:       flex;
  align-items:   center;
  overflow-x:    auto;
}

/* pill style (default) */
.rmm-category-nav-wrapper .rmm-cat-pill,
.rmm-category-nav-wrapper .rmm-cat-item {
  background:    var(--rmm-ds-cat-idle-bg)   !important;
  color:         var(--rmm-ds-cat-idle-text) !important;
  border-radius: var(--rmm-ds-radius-btn)    !important;
  border:        1px solid transparent;
  transition:    all 0.2s ease;
  gap: 6px;
}
.rmm-category-nav-wrapper .rmm-cat-pill.active,
.rmm-category-nav-wrapper .rmm-cat-item.active {
  background: var(--rmm-ds-cat-active-bg)   !important;
  color:      var(--rmm-ds-cat-active-text) !important;
}
/* underline style */
.rmm-cat-style--underline .rmm-cat-pill,
.rmm-cat-style--underline .rmm-cat-item {
  background:    transparent !important;
  border-radius: 0          !important;
  border-bottom: 2px solid transparent;
}
.rmm-cat-style--underline .rmm-cat-pill.active,
.rmm-cat-style--underline .rmm-cat-item.active {
  background:    transparent                     !important;
  color:         var(--rmm-ds-cat-active-bg)     !important;
  border-bottom: 2px solid var(--rmm-ds-cat-active-bg);
}
/* button style */
.rmm-cat-style--button .rmm-cat-pill,
.rmm-cat-style--button .rmm-cat-item {
  border:        1px solid var(--rmm-ds-border)  !important;
  border-radius: var(--rmm-ds-radius-btn)        !important;
}
.rmm-cat-style--button .rmm-cat-pill.active,
.rmm-cat-style--button .rmm-cat-item.active {
  background: var(--rmm-ds-cat-active-bg)   !important;
  color:      var(--rmm-ds-cat-active-text) !important;
  border-color: var(--rmm-ds-cat-active-bg) !important;
}

/* Category icon size */
.rmm-category-nav-wrapper .rmm-cat-icon,
.rmm-cat-pill .rmm-cat-icon {
  font-size: var(--rmm-ds-cat-icon);
}

/* ── Live Search Bar ────────────────────────────────────────────────────── */
.rmm-live-search-bar,
.rmm-standalone-search-wrap {
  position: relative;
}
.rmm-live-search-bar input[type="text"],
.rmm-live-search-bar input[type="search"],
.rmm-live-search-bar input,
.rmm-standalone-search-wrap input {
  background:    var(--rmm-ds-search-bg)         !important;
  border:        1px solid var(--rmm-ds-search-border) !important;
  border-radius: var(--rmm-ds-search-radius)     !important;
  color:         var(--rmm-ds-text)              !important;
  font-family:   var(--rmm-ds-font-family);
  font-size:     var(--rmm-ds-desc-size);
  padding: 10px 14px;
  width: 100%;
}
.rmm-live-search-bar input::placeholder,
.rmm-standalone-search-wrap input::placeholder {
  color: var(--rmm-ds-search-placeholder) !important;
}
.rmm-live-search-bar .rmm-search-icon,
.rmm-standalone-search-wrap .rmm-search-icon {
  color: var(--rmm-ds-search-icon) !important;
}
.rmm-live-search-bar input:focus,
.rmm-standalone-search-wrap input:focus {
  border-color: var(--rmm-ds-primary)       !important;
  box-shadow:   0 0 0 3px var(--rmm-ds-primary-soft) !important;
  outline:      none;
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

/* ── Language Switcher ──────────────────────────────────────────────────── */
.rmm-lang-switcher,
.rmm-lang-widget-wrap .rmm-lang-trigger-btn,
.rmm-lang-widget-wrap .rmm-lang-pills-component {
  background:    var(--rmm-ds-lang-bg)     !important;
  color:         var(--rmm-ds-lang-text)   !important;
  border:        1px solid var(--rmm-ds-lang-border) !important;
  border-radius: var(--rmm-ds-lang-radius) !important;
}
.rmm-lang-widget-wrap .rmm-lang-pill-item.is-active {
  background: var(--rmm-ds-primary)    !important;
  color:      var(--rmm-ds-on-primary) !important;
}
.rmm-lang-widget-wrap .rmm-lang-pill-item {
  color: var(--rmm-ds-lang-text) !important;
}

/* ── Entrance Language Popup Modal ─────────────────────────────────────── */
.rmm-entrance-modal-backdrop {
  background: var(--rmm-ds-lang-modal-backdrop) !important;
  backdrop-filter: blur(var(--rmm-ds-lang-modal-blur)) !important;
  -webkit-backdrop-filter: blur(var(--rmm-ds-lang-modal-blur)) !important;
}
.rmm-entrance-card {
  background: var(--rmm-ds-lang-modal-bg) !important;
  border-radius: var(--rmm-ds-lang-modal-radius) !important;
  box-shadow: var(--rmm-ds-shadow-card) !important;
  border: 1px solid var(--rmm-ds-border) !important;
  color: var(--rmm-ds-text) !important;
}
.rmm-entrance-title {
  color: var(--rmm-ds-lang-modal-title) !important;
}
.rmm-entrance-subtitle {
  color: var(--rmm-ds-text-secondary) !important;
}
.rmm-entrance-lang-btn {
  background: var(--rmm-ds-lang-modal-card-bg) !important;
  border: 1.5px solid var(--rmm-ds-lang-modal-card-border) !important;
  border-radius: var(--rmm-ds-radius-btn) !important;
  color: var(--rmm-ds-lang-modal-card-text) !important;
}
.rmm-entrance-lang-btn .rmm-entrance-lang-native,
.rmm-entrance-lang-btn .rmm-entrance-lang-name {
  color: var(--rmm-ds-lang-modal-card-text) !important;
}
.rmm-entrance-lang-btn.is-active,
.rmm-entrance-lang-btn:hover {
  background: var(--rmm-ds-lang-modal-card-active-bg) !important;
  border-color: var(--rmm-ds-lang-modal-card-active-bg) !important;
  color: var(--rmm-ds-lang-modal-card-active-text) !important;
}
.rmm-entrance-lang-btn.is-active .rmm-entrance-lang-native,
.rmm-entrance-lang-btn.is-active .rmm-entrance-lang-name,
.rmm-entrance-lang-btn:hover .rmm-entrance-lang-native,
.rmm-entrance-lang-btn:hover .rmm-entrance-lang-name {
  color: var(--rmm-ds-lang-modal-card-active-text) !important;
}
.rmm-entrance-close {
  background: var(--rmm-ds-subtle) !important;
  color: var(--rmm-ds-muted) !important;
}
.rmm-remember-checkbox span {
  color: var(--rmm-ds-text-secondary) !important;
}

/* ── Theme Toggle ───────────────────────────────────────────────────────── */
.rmm-theme-toggle-widget-wrap .rmm-theme-toggle-btn,
.rmm-theme-toggle-widget-btn,
.rmm-theme-toggle-btn {
  background:    var(--rmm-ds-toggle-bg)   !important;
  color:         var(--rmm-ds-toggle-icon) !important;
  border:        1px solid var(--rmm-ds-border) !important;
  border-radius: var(--rmm-ds-radius-btn)  !important;
  padding: 8px;
}

/* ── Floating Cart Bar ──────────────────────────────────────────────────── */
.rmm-floating-cart-bar,
.rmm-floating-cart-wrapper .rmm-floating-cart-btn {
  background:    var(--rmm-ds-cart-bg)     !important;
  color:         var(--rmm-ds-cart-text)   !important;
  border-radius: var(--rmm-ds-cart-radius) !important;
  box-shadow:    var(--rmm-ds-shadow-card) !important;
  border:        1px solid var(--rmm-ds-border) !important;
}
/* dock positions */
.rmm-floating-cart-bar.rmm-cart-dock--bottom {
  position: fixed;
  bottom: 16px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 8000;
  min-width: 280px;
}
.rmm-floating-cart-bar.rmm-cart-dock--corner {
  position: fixed;
  bottom: 24px;
  left: 24px;
  z-index: 8000;
}
.rmm-floating-cart-bar .rmm-cart-badge,
.rmm-cart-badge-count {
  background:    var(--rmm-ds-cart-badge-bg)   !important;
  color:         var(--rmm-ds-cart-badge-text) !important;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  min-width: 20px;
  text-align: center;
}
.rmm-cart-label,
.rmm-cart-total,
.rmm-cart-arrow {
  color: var(--rmm-ds-cart-text) !important;
}
CSS;
    }

    /**
     * Compile full CSS from settings array.
     */
    public static function compile($settings) {
        $default_theme = isset($settings['default_theme']) && $settings['default_theme'] === 'light' ? 'light' : 'dark';
        $light_tokens  = isset($settings['light']) ? $settings['light'] : TokenSchema::get_default_tokens('light');
        $dark_tokens   = isset($settings['dark'])  ? $settings['dark']  : TokenSchema::get_default_tokens('dark');

        if ($default_theme === 'light') {
            $light_css = self::build_variables_block(
                $light_tokens,
                ':root, [data-theme="light"], body.light-theme, body:not(.dark-theme), .rmm-menu-widget-wrapper:not(.dark-theme)'
            );
            $dark_css  = self::build_variables_block(
                $dark_tokens,
                '[data-theme="dark"], body.dark-theme, .rmm-menu-widget-wrapper.dark-theme'
            );
        } else {
            $dark_css  = self::build_variables_block(
                $dark_tokens,
                ':root, [data-theme="dark"], body.dark-theme, body:not(.light-theme), .rmm-menu-widget-wrapper:not(.light-theme)'
            );
            $light_css = self::build_variables_block(
                $light_tokens,
                '[data-theme="light"], body.light-theme, .rmm-menu-widget-wrapper.light-theme'
            );
        }

        $components_css = self::get_component_mapping_css();

        $output  = "/* Fondogh Online Menu - Generated Design System */\n";
        $output .= $light_css . "\n\n";
        $output .= $dark_css  . "\n\n";
        $output .= $components_css . "\n";

        return $output;
    }

    public static function minify($css) {
        // Strip comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
        $css = str_replace(';}', '}', $css);
        return trim($css);
    }

    public static function compile_and_save($settings) {
        $raw_css = self::compile($settings);
        $min_css = self::minify($raw_css);

        $upload_dir = wp_upload_dir();
        if (!empty($upload_dir['error'])) {
            return false;
        }

        $target_dir = trailingslashit($upload_dir['basedir']) . 'fondogh-menu';
        if (!is_dir($target_dir) && !wp_mkdir_p($target_dir)) {
            return false;
        }

        $target_file = trailingslashit($target_dir) . 'fondogh-style.css';
        $written = @file_put_contents($target_file, $min_css, LOCK_EX);

        if (false === $written) {
            return false;
        }

        $file_url = trailingslashit($upload_dir['baseurl']) . 'fondogh-menu/fondogh-style.css';
        $version  = filemtime($target_file);

        return [
            'file_url'  => $file_url,
            'file_path' => $target_file,
            'version'   => $version,
            'css'        => $min_css,
        ];
    }

    public static function get_compiled_css_info() {
        $upload_dir = wp_upload_dir();
        if (!empty($upload_dir['error'])) {
            return false;
        }

        $target_file = trailingslashit($upload_dir['basedir']) . 'fondogh-menu/fondogh-style.css';
        if (!file_exists($target_file)) {
            return false;
        }

        return [
            'file_url'  => trailingslashit($upload_dir['baseurl']) . 'fondogh-menu/fondogh-style.css',
            'file_path' => $target_file,
            'version'   => filemtime($target_file),
        ];
    }
}
