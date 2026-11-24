<?php
namespace RMM\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Safe SVG upload support.
 *
 * WordPress blocks image/svg+xml by default, which breaks the Elementor icon
 * controls ("Upload SVG" tab) and the food badge image picker. This module
 * whitelists the MIME type for trusted users only and sanitizes the uploaded
 * markup before it reaches the media library.
 */
class SvgSupport {

    const MAX_BYTES = 524288; // 512 KB

    /** @var string[] Elements that can execute code or load remote content. */
    private static $blocked_tags = [
        'script',
        'foreignobject',
        'iframe',
        'embed',
        'object',
        'audio',
        'video',
        'handler',
        'listener',
        'animate',
        'set',
        'discard',
    ];

    /** @var string[] Dangerous CSS fragments. */
    private static $blocked_css = [
        'javascript:',
        'expression(',
        '@import',
        'behavior:',
        '-moz-binding',
    ];

    public function __construct() {
        add_filter('upload_mimes', [$this, 'allow_svg_mime']);
        add_filter('wp_check_filetype_and_ext', [$this, 'fix_svg_filetype'], 10, 4);
        add_filter('wp_handle_upload_prefilter', [$this, 'sanitize_svg_upload']);
    }

    /**
     * Whether the current user may upload SVG files.
     * Defaults to administrators; filterable for custom restaurant roles.
     *
     * @return bool
     */
    public static function can_upload_svg() {
        if (!is_user_logged_in() || !current_user_can('upload_files')) {
            return false;
        }

        $capability = apply_filters('rmm_svg_upload_capability', 'manage_options');

        return current_user_can($capability);
    }

    /**
     * Whitelist the SVG MIME type for trusted users.
     *
     * @param array $mimes
     * @return array
     */
    public function allow_svg_mime($mimes) {
        if (!is_array($mimes)) {
            return $mimes;
        }

        if (self::can_upload_svg()) {
            $mimes['svg'] = 'image/svg+xml';
        }

        return $mimes;
    }

    /**
     * Resolve the real file type for SVGs; PHP's finfo rarely reports
     * image/svg+xml, which makes core reject the upload as a mismatch.
     *
     * @param array       $data
     * @param string      $file
     * @param string      $filename
     * @param array|null  $mimes
     * @return array
     */
    public function fix_svg_filetype($data, $file, $filename, $mimes) {
        if (!is_array($data) || !self::can_upload_svg()) {
            return $data;
        }

        if (strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION)) !== 'svg') {
            return $data;
        }

        if (empty($data['ext']) || empty($data['type'])) {
            $data['ext']  = 'svg';
            $data['type'] = 'image/svg+xml';

            if (!isset($data['proper_filename'])) {
                $data['proper_filename'] = false;
            }
        }

        return $data;
    }

    /**
     * Sanitize an SVG before WordPress stores it.
     *
     * @param array $file
     * @return array
     */
    public function sanitize_svg_upload($file) {
        if (!is_array($file) || empty($file['name'])) {
            return $file;
        }

        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'svg') {
            return $file;
        }

        if (!self::can_upload_svg()) {
            $file['error'] = __('بارگذاری فایل SVG فقط برای مدیران سایت مجاز است.', 'fondogh-online-menu');
            return $file;
        }

        $tmp = isset($file['tmp_name']) ? $file['tmp_name'] : '';
        if ($tmp === '' || !is_readable($tmp)) {
            return $file;
        }

        $size = filesize($tmp);
        if ($size === false || $size > self::MAX_BYTES) {
            $file['error'] = __('حجم فایل SVG نباید بیشتر از ۵۱۲ کیلوبایت باشد.', 'fondogh-online-menu');
            return $file;
        }

        $raw   = file_get_contents($tmp);
        $clean = self::sanitize($raw);

        if ($clean === null) {
            $file['error'] = __('فایل SVG نامعتبر یا ناامن است و بارگذاری نشد.', 'fondogh-online-menu');
            return $file;
        }

        if (file_put_contents($tmp, $clean) === false) {
            $file['error'] = __('پاک‌سازی فایل SVG ممکن نشد؛ دوباره تلاش کنید.', 'fondogh-online-menu');
            return $file;
        }

        $file['type'] = 'image/svg+xml';

        return $file;
    }

    /**
     * Sanitize raw SVG markup.
     *
     * @param string $raw
     * @return string|null Clean markup, or null when the input is not a safe SVG.
     */
    public static function sanitize($raw) {
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        // Reject documents that declare a DTD or entities (XXE / billion laughs).
        if (stripos($raw, '<!DOCTYPE') !== false || stripos($raw, '<!ENTITY') !== false) {
            return null;
        }

        if (!class_exists('\DOMDocument')) {
            return null;
        }

        $previous = libxml_use_internal_errors(true);

        $dom                     = new \DOMDocument();
        $dom->preserveWhiteSpace = true;

        $loaded = $dom->loadXML($raw, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$dom->documentElement) {
            return null;
        }

        if (strtolower($dom->documentElement->nodeName) !== 'svg') {
            return null;
        }

        self::clean_node($dom->documentElement);

        $output = $dom->saveXML();

        return (is_string($output) && trim($output) !== '') ? $output : null;
    }

    /**
     * Recursively strip dangerous elements, attributes and CSS.
     *
     * @param \DOMNode $node
     * @return void
     */
    private static function clean_node($node) {
        if (!($node instanceof \DOMElement)) {
            return;
        }

        $tag = strtolower($node->nodeName);

        if (in_array($tag, self::$blocked_tags, true)) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            return;
        }

        if ($tag === 'style' && self::is_dangerous_css($node->textContent)) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            return;
        }

        if ($node->hasAttributes()) {
            $remove = [];

            foreach ($node->attributes as $attribute) {
                $name  = strtolower($attribute->nodeName);
                $value = (string) $attribute->nodeValue;

                if (strpos($name, 'on') === 0) {
                    $remove[] = $attribute;
                    continue;
                }

                if ($name === 'href' || $name === 'xlink:href' || $name === 'src') {
                    $target = strtolower(trim($value));

                    if (strpos($target, '#') !== 0 && strpos($target, 'data:image/') !== 0) {
                        $remove[] = $attribute;
                    }
                    continue;
                }

                if ($name === 'style' && self::is_dangerous_css($value)) {
                    $remove[] = $attribute;
                }
            }

            foreach ($remove as $attribute) {
                if ($attribute->ownerElement) {
                    $attribute->ownerElement->removeAttributeNode($attribute);
                }
            }
        }

        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            self::clean_node($child);
        }
    }

    /**
     * @param string $css
     * @return bool
     */
    private static function is_dangerous_css($css) {
        $css = strtolower(preg_replace('/\s+/', '', (string) $css));

        foreach (self::$blocked_css as $needle) {
            if (strpos($css, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
