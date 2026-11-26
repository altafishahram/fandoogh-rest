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

    /** @var string[] Strict allowlist of safe SVG elements (lowercase localName). */
    private static $allowed_tags = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc', 'metadata', 'switch', 'a',
        'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon',
        'text', 'tspan', 'textpath',
        'lineargradient', 'radialgradient', 'stop', 'pattern', 'clippath', 'mask', 'marker',
        'filter', 'feblend', 'fecolormatrix', 'fecomponenttransfer', 'fecomposite',
        'feconvolvematrix', 'fediffuselighting', 'fedisplacementmap', 'fedistantlight',
        'fedropshadow', 'feflood', 'fefunca', 'fefuncb', 'fefuncg', 'fefuncr',
        'fegaussianblur', 'feimage', 'femerge', 'femergenode', 'femorphology',
        'feoffset', 'fepointlight', 'fespecularlighting', 'fespotlight', 'fetile', 'feturbulence',
        'image', 'style',
    ];

    /** @var string[] Strict allowlist of safe SVG attributes (lowercase localName). */
    private static $allowed_attributes = [
        'id', 'class', 'style', 'tabindex', 'lang', 'xmlns', 'viewbox', 'preserveaspectratio',
        'width', 'height', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'dx', 'dy', 'd', 'points', 'pathlength',
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-linecap',
        'stroke-linejoin', 'stroke-miterlimit', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity',
        'opacity', 'color', 'stop-color', 'stop-opacity', 'offset',
        'gradientunits', 'gradienttransform', 'spreadmethod',
        'patternunits', 'patterncontentunits', 'patterntransform',
        'clippathunits', 'clip-path', 'clip-rule', 'mask', 'maskunits', 'maskcontentunits',
        'filter', 'filterunits', 'primitiveunits',
        'transform', 'transform-origin', 'display', 'visibility', 'overflow',
        'font-family', 'font-size', 'font-style', 'font-weight', 'text-anchor',
        'dominant-baseline', 'alignment-baseline', 'letter-spacing', 'word-spacing',
        'text-decoration', 'direction', 'unicode-bidi',
        'marker-start', 'marker-mid', 'marker-end', 'markerwidth', 'markerheight', 'refx', 'refy', 'orient', 'markerunits',
        'href', 'src', 'target', 'aria-hidden', 'aria-label', 'aria-labelledby', 'role', 'focusable',
        'space', 'stddeviation', 'in', 'in2', 'result', 'mode', 'type', 'values',
        'slope', 'intercept', 'amplitude', 'exponent', 'tablevalues', 'k1', 'k2', 'k3', 'k4',
        'operator', 'radius', 'edgemode', 'flood-color', 'flood-opacity', 'lighting-color',
        'surfacescale', 'specularconstant', 'specularexponent', 'diffuseconstant',
        'azimuth', 'elevation', 'pointsatx', 'pointsaty', 'pointsatz', 'limitingconeangle',
        'scale', 'xchannelselector', 'ychannelselector', 'basefrequency', 'numoctaves', 'seed', 'stitchtiles',
        'order', 'kernelmatrix', 'divisor', 'bias', 'targetx', 'targety', 'kernelunitlength', 'preservealpha',
        'fx', 'fy', 'fr', 'startoffset', 'method', 'spacing',
        'vector-effect', 'paint-order', 'shape-rendering', 'image-rendering', 'text-rendering',
        'color-interpolation', 'color-interpolation-filters', 'mix-blend-mode', 'isolation', 'pointer-events', 'cursor',
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
        add_filter('wp_handle_sideload_prefilter', [$this, 'sanitize_svg_upload']);
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

        if (!$loaded || !$dom->documentElement || $dom->doctype !== null) {
            return null;
        }

        // Strip document-level processing instructions (e.g. xml-stylesheet) and comments.
        $doc_children = [];
        foreach ($dom->childNodes as $doc_child) {
            $doc_children[] = $doc_child;
        }
        foreach ($doc_children as $doc_child) {
            if ($doc_child instanceof \DOMProcessingInstruction || $doc_child instanceof \DOMComment) {
                $dom->removeChild($doc_child);
            }
        }

        $root = $dom->documentElement;
        if (strtolower((string) ($root->localName ?: $root->nodeName)) !== 'svg') {
            return null;
        }
        if ($root->namespaceURI !== null && $root->namespaceURI !== '' && $root->namespaceURI !== 'http://www.w3.org/2000/svg') {
            return null;
        }

        self::clean_node($root);

        if (!$dom->documentElement) {
            return null;
        }

        $output = $dom->saveXML();

        return (is_string($output) && trim($output) !== '') ? $output : null;
    }

    /**
     * Recursively enforce SVG element/attribute allowlist and strip dangerous CSS.
     *
     * @param \DOMNode $node
     * @return void
     */
    private static function clean_node($node) {
        if ($node instanceof \DOMProcessingInstruction || $node instanceof \DOMComment) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            return;
        }

        if ($node instanceof \DOMCdataSection) {
            $parent_tag = ($node->parentNode instanceof \DOMElement)
                ? strtolower((string) ($node->parentNode->localName ?: $node->parentNode->nodeName))
                : '';
            if ($parent_tag !== 'style' || self::is_dangerous_css($node->data)) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
            return;
        }

        if (!($node instanceof \DOMElement)) {
            return;
        }

        $ns = (string) $node->namespaceURI;
        if ($ns !== '' && $ns !== 'http://www.w3.org/2000/svg') {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
            return;
        }

        $tag = strtolower((string) ($node->localName ?: $node->nodeName));

        if (!in_array($tag, self::$allowed_tags, true)) {
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
            $url_ref_attrs = ['fill', 'stroke', 'clip-path', 'mask', 'filter', 'marker-start', 'marker-mid', 'marker-end'];

            foreach ($node->attributes as $attribute) {
                $local   = strtolower((string) ($attribute->localName ?: $attribute->nodeName));
                $attr_ns = (string) $attribute->namespaceURI;
                $value   = (string) $attribute->nodeValue;

                if (strpos($local, 'on') === 0) {
                    $remove[] = $attribute;
                    continue;
                }

                if ($local === 'href') {
                    $target = trim($value);
                    $is_internal = strpos($target, '#') === 0;
                    $is_safe_img = ($tag === 'image') && preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/i', $target);
                    if (!$is_internal && !$is_safe_img) {
                        $remove[] = $attribute;
                    }
                    continue;
                }

                if ($local === 'src') {
                    $is_safe_img = ($tag === 'image') && preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/i', trim($value));
                    if (!$is_safe_img) {
                        $remove[] = $attribute;
                    }
                    continue;
                }

                if ($local === 'style') {
                    if (self::is_dangerous_css($value)) {
                        $remove[] = $attribute;
                    }
                    continue;
                }

                if ($attr_ns !== '' && $attr_ns !== 'http://www.w3.org/2000/svg' && $attr_ns !== 'http://www.w3.org/XML/1998/namespace') {
                    $remove[] = $attribute;
                    continue;
                }

                if (!in_array($local, self::$allowed_attributes, true) && strpos($local, 'data-') !== 0) {
                    $remove[] = $attribute;
                    continue;
                }

                if (in_array($local, $url_ref_attrs, true) && stripos($value, 'url(') !== false) {
                    if (!preg_match('/^url\(\s*[\'"]?#[a-zA-Z0-9_-]+[\'"]?\s*\)$/i', trim($value))) {
                        $remove[] = $attribute;
                    }
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
