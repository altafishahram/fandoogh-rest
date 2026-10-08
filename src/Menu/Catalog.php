<?php
namespace FandooghRest\Menu;

use FandooghRest\Core\Settings;

final class Catalog
{
    public function register(): void {}

    public static function menu(?string $language = null): array
    {
        $language = in_array($language, ['fa', 'en', 'zh', 'tr'], true) ? $language : 'fa';
        $selected = array_map('absint', (array) Settings::get('menu_category_ids', []));
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        $categories = [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                if (!$selected || in_array((int) $term->term_id, $selected, true)) {
                    $categories[] = self::category($term, $language);
                }
            }
        }
        usort($categories, fn($a, $b) => [$a['order'], $a['id']] <=> [$b['order'], $b['id']]);
        $products = [];
        foreach (wc_get_products(['status' => 'publish', 'limit' => -1, 'type' => ['simple', 'variable'], 'orderby' => 'menu_order', 'order' => 'ASC']) as $product) {
            if ($product->get_meta('_admincafe_visible') === 'no' || ($selected && !array_intersect($selected, $product->get_category_ids()))) {
                continue;
            }
            $products[] = self::serialize($product, $language);
        }
        return ['products' => $products, 'categories' => $categories];
    }

    public static function category($term, ?string $language = null): array
    {
        $id = (int) $term->term_id;
        $stored_order = get_term_meta($id, '_admincafe_product_order', true);
        $translations = self::storedTranslations(get_term_meta($id, '_admincafe_translations', true), ['name']);
        return ['id' => $id, 'name' => $language && $language !== 'fa' ? ($translations[$language]['name'] ?? $term->name) : $term->name, 'parent' => (int) $term->parent,
            'image' => wp_get_attachment_image_url((int) get_term_meta($id, 'thumbnail_id', true), 'medium') ?: '',
            'image_id' => (int) get_term_meta($id, 'thumbnail_id', true),
            'icon' => (string) get_term_meta($id, '_admincafe_icon', true),
            'order' => (int) get_term_meta($id, '_admincafe_order', true),
            'product_order' => is_array($stored_order) ? array_values(array_filter(array_map('absint', $stored_order))) : [],
            'translations' => $language === null ? (object) $translations : (object) []];
    }

    public static function product(int $id): array|\WP_Error
    {
        $product = wc_get_product($id);
        return $product ? self::serialize($product) : new \WP_Error('product_not_found', __('Product not found.', 'fandoogh-rest'), ['status' => 404]);
    }

    public static function serialize($product, ?string $language = null): array
    {
        $variations = [];
        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $id) {
                $variant = wc_get_product($id);
                if ($variant && $variant->get_status() === 'publish') {
                    $variations[] = ['id' => $id, 'name' => self::localizedName($variant, $language ?? 'fa'), 'price' => $variant->get_price(), 'regular_price' => $variant->get_regular_price(), 'sale_price' => $variant->get_sale_price(),
                        'available' => $variant->is_purchasable() && $variant->is_in_stock(), 'attributes' => $variant->get_attributes(),
                        'translations' => $language === null ? (object) self::storedTranslations($variant->get_meta('_admincafe_translations')) : (object) []];
                }
            }
        }
        return ['id' => $product->get_id(), 'type' => $product->get_type(), 'name' => self::localizedName($product, $language ?? 'fa'),
            'description' => self::localizedText($product, 'description', $language ?? 'fa'), 'short_description' => self::localizedText($product, 'short_description', $language ?? 'fa'),
            'price' => $product->get_price(), 'regular_price' => $product->get_regular_price(), 'sale_price' => $product->get_sale_price(),
            'currency' => get_woocommerce_currency(), 'currency_symbol' => html_entity_decode(get_woocommerce_currency_symbol()),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'large') ?: '', 'image_id' => $product->get_image_id(),
            'available' => $product->is_purchasable() && $product->is_in_stock(), 'sku' => $product->get_sku(),
            'category_ids' => $product->get_category_ids(), 'order' => $product->get_menu_order(), 'variations' => $variations,
            'visible' => $product->get_meta('_admincafe_visible') !== 'no', 'status' => $product->get_status(),
            'translations' => $language === null ? (object) self::storedTranslations($product->get_meta('_admincafe_translations')) : (object) []];
    }

    /** Canonical Woo content is Persian; untranslated fields always fall back individually. */
    public static function localizedName($product, string $language): string
    {
        return self::localizedText($product, 'name', $language);
    }
    public static function localizedText($product, string $field, string $language): string
    {
        if (!in_array($field, ['name', 'description', 'short_description'], true)) { return ''; }
        $canonical = (string) $product->{'get_' . $field}();
        $translations = self::storedTranslations($product->get_meta('_admincafe_translations'));
        $value = $language !== 'fa' ? ($translations[$language][$field] ?? $canonical) : $canonical;
        return wp_strip_all_tags($value);
    }
    /** Strict nested-map validation, reusable by category REST writes before any mutation. */
    public static function validateTranslations(mixed $input, array $fields = ['name', 'description', 'short_description']): array|\WP_Error
    {
        if (!is_array($input) || count($input) > 3) { return new \WP_Error('invalid_translations', __('Invalid content translations.', 'fandoogh-rest'), ['status' => 400]); }
        $result = [];
        foreach ($input as $language => $values) {
            if (!in_array($language, ['en', 'zh', 'tr'], true) || !is_array($values) || count($values) > count($fields)) { return new \WP_Error('invalid_translations', __('Invalid content translations.', 'fandoogh-rest'), ['status' => 400]); }
            $result[$language] = [];
            foreach ($values as $field => $value) {
                $maximum = $field === 'name' ? 250 : ($field === 'short_description' ? 5000 : 20000);
                if (!in_array($field, $fields, true) || !is_string($value) || mb_strlen($value) > $maximum) { return new \WP_Error('invalid_translations', __('Invalid content translations.', 'fandoogh-rest'), ['status' => 400]); }
                $result[$language][$field] = $field === 'name' ? sanitize_text_field($value) : sanitize_textarea_field(wp_strip_all_tags($value));
            }
        }
        return $result;
    }
    public static function storedTranslations(mixed $value, array $fields = ['name', 'description', 'short_description']): array
    {
        if (!is_array($value)) { return []; }
        $checked = self::validateTranslations($value, $fields);
        if (is_wp_error($checked)) { return []; }
        return self::mergeTranslations([], $checked);
    }
    public static function mergeTranslations(array $existing, array $patch): array
    {
        foreach ($patch as $language => $fields) {
            foreach ($fields as $field => $value) {
                if ($value === '') { unset($existing[$language][$field]); }
                else { $existing[$language][$field] = $value; }
            }
            if (empty($existing[$language])) { unset($existing[$language]); }
        }
        return $existing;
    }

    public static function saveProduct(array $input, int $id = 0, ?string $importMarker = null): array|\WP_Error
    {
        foreach (['name', 'description', 'short_description', 'sku', 'price', 'regular_price', 'sale_price', 'image_id', 'order', 'status', 'available', 'visible'] as $field) {
            if (array_key_exists($field, $input) && !is_scalar($input[$field]) && $input[$field] !== null) {
                return new \WP_Error('invalid_field', sprintf(__('Invalid product field: %s', 'fandoogh-rest'), $field), ['status' => 400]);
            }
        }
        if (!$id && isset($input['type']) && $input['type'] !== 'simple') {
            return new \WP_Error('unsupported_product', __('Create simple products here; configure variable products in WooCommerce.', 'fandoogh-rest'), ['status' => 400]);
        }
        $product = $id ? wc_get_product($id) : new \WC_Product_Simple();
        if (!$product) {
            return new \WP_Error('product_not_found', __('Product not found.', 'fandoogh-rest'), ['status' => 404]);
        }
        if (!in_array($product->get_type(), ['simple', 'variable', 'variation'], true)) {
            return new \WP_Error('unsupported_product', __('Unsupported product type.', 'fandoogh-rest'), ['status' => 400]);
        }
        $translation_patch = null;
        if (array_key_exists('translations', $input)) {
            $translation_patch = self::validateTranslations($input['translations']);
            if (is_wp_error($translation_patch)) { return $translation_patch; }
        }
        $variation_updates = [];
        $seen_variations = [];
        if (array_key_exists('variation_translations', $input)) {
            if (!$product->is_type('variable') || !is_array($input['variation_translations']) || count($input['variation_translations']) > 200) { return new \WP_Error('invalid_translations', __('Invalid content translations.', 'fandoogh-rest'), ['status' => 400]); }
            foreach ($input['variation_translations'] as $variation_id => $map) {
                if (!preg_match('/^[1-9][0-9]*$/D', (string) $variation_id)) { return new \WP_Error('invalid_translations', __('Invalid content translations.', 'fandoogh-rest'), ['status' => 400]); }
                $variant = wc_get_product((int) $variation_id);
                if (!$variant || !$variant->is_type('variation') || $variant->get_parent_id() !== $product->get_id()) { return new \WP_Error('invalid_variation', __('Every choice must belong to this product and appear once.', 'fandoogh-rest'), ['status' => 400]); }
                $checked = self::validateTranslations($map);
                if (is_wp_error($checked)) { return $checked; }
                $variation_updates[(int) $variation_id] = ['translations' => $checked];
            }
        }
        if (array_key_exists('variations', $input)) {
            if (!$product->is_type('variable') || !is_array($input['variations']) || count($input['variations']) > 200) {
                return new \WP_Error('invalid_variations', __('Supply existing choices of this variable product.', 'fandoogh-rest'), ['status' => 400]);
            }
            foreach ($input['variations'] as $row) {
                if (!is_array($row) || empty($row['id']) || !is_scalar($row['id'])) { return new \WP_Error('invalid_variation', __('A variation ID is required.', 'fandoogh-rest'), ['status' => 400]); }
                $variation_id = absint($row['id']);
                $variation = wc_get_product($variation_id);
                if (!$variation || !$variation->is_type('variation') || $variation->get_parent_id() !== $product->get_id() || isset($seen_variations[$variation_id])) {
                    return new \WP_Error('invalid_variation', __('Every choice must belong to this product and appear once.', 'fandoogh-rest'), ['status' => 400]);
                }
                $fields = array_intersect_key($row, array_flip(['price', 'regular_price', 'sale_price', 'available']));
                foreach ($fields as $key => $value) {
                    if (!is_scalar($value) && $value !== null) { return new \WP_Error('invalid_variation', __('Invalid choice field.', 'fandoogh-rest'), ['status' => 400]); }
                    if ($key !== 'available' && $value !== '' && (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0)) {
                        return new \WP_Error('invalid_price', __('Choice price must be a non-negative number.', 'fandoogh-rest'), ['status' => 400]);
                    }
                }
                $seen_variations[$variation_id] = true;
                $variation_updates[$variation_id] = array_merge($variation_updates[$variation_id] ?? [], $fields);
            }
        }
        if (!$id && empty(trim((string) ($input['name'] ?? '')))) {
            return new \WP_Error('invalid_name', __('Product name is required.', 'fandoogh-rest'), ['status' => 400]);
        }
        foreach (['price', 'regular_price', 'sale_price'] as $field) {
            if (isset($input[$field]) && $input[$field] !== '' && (!is_numeric($input[$field]) || !is_finite((float) $input[$field]) || (float) $input[$field] < 0)) {
                return new \WP_Error('invalid_price', __('Price must be a non-negative number.', 'fandoogh-rest'), ['status' => 400]);
            }
        }
        if (isset($input['status']) && !in_array($input['status'], ['publish', 'draft', 'private'], true)) {
            return new \WP_Error('invalid_status', __('Invalid product status.', 'fandoogh-rest'), ['status' => 400]);
        }
        if (isset($input['image_id']) && (int) $input['image_id'] !== 0 && !wp_attachment_is_image(absint($input['image_id']))) {
            return new \WP_Error('invalid_image', __('Choose an image attachment.', 'fandoogh-rest'), ['status' => 400]);
        }
        if (isset($input['category_ids'])) {
            if (!is_array($input['category_ids'])) {
                return new \WP_Error('invalid_categories', __('Categories must be an array.', 'fandoogh-rest'), ['status' => 400]);
            }
            foreach ($input['category_ids'] as $term) {
                if (!term_exists(absint($term), 'product_cat')) {
                    return new \WP_Error('invalid_category', __('Category not found.', 'fandoogh-rest'), ['status' => 400]);
                }
            }
        }
        try {
            if ($translation_patch !== null) { $product->update_meta_data('_admincafe_translations', self::mergeTranslations(self::storedTranslations($product->get_meta('_admincafe_translations')), $translation_patch)); }
            foreach (['name', 'sku'] as $field) {
                if (isset($input[$field])) { $product->{'set_' . $field}(sanitize_text_field($input[$field])); }
            }
            foreach (['description', 'short_description'] as $field) {
                if (isset($input[$field])) { $product->{'set_' . $field}(wp_kses_post($input[$field])); }
            }
            if (isset($input['status'])) { $product->set_status($input['status']); }
            elseif (!$id) { $product->set_status('publish'); }
            if (isset($input['category_ids']) && !$product->is_type('variation')) { $product->set_category_ids(array_map('absint', $input['category_ids'])); }
            if (isset($input['image_id'])) { $product->set_image_id(absint($input['image_id'])); }
            if (isset($input['order'])) { $product->set_menu_order(max(0, (int) $input['order'])); }
            if (array_key_exists('visible', $input)) { $product->update_meta_data('_admincafe_visible', rest_sanitize_boolean($input['visible']) ? 'yes' : 'no'); }
            if (array_key_exists('available', $input)) { $product->set_stock_status(rest_sanitize_boolean($input['available']) ? 'instock' : 'outofstock'); }
            if (!$product->is_type('variable')) {
                foreach (['regular_price', 'sale_price'] as $field) {
                    if (array_key_exists($field, $input)) { $product->{'set_' . $field}(wc_format_decimal($input[$field])); }
                }
                if (array_key_exists('price', $input)) { $product->set_regular_price(wc_format_decimal($input['price'])); }
                if ($product->get_sale_price('edit') !== '' && $product->get_regular_price('edit') !== '' && (float) $product->get_sale_price('edit') >= (float) $product->get_regular_price('edit')) { $product->set_sale_price(''); }
                $product->set_price($product->is_on_sale('edit') ? $product->get_sale_price('edit') : $product->get_regular_price('edit'));
            }
            if ($importMarker !== null) { $product->update_meta_data('_admincafe_import_row', $importMarker); }
            $product->save();
            if ($translation_patch !== null) { do_action('admincafe_translation_manual_input', 'product', $product->get_id(), $translation_patch); }
            if ($product->is_type('variation')) { \WC_Product_Variable::sync($product->get_parent_id()); }
            foreach ($variation_updates as $variation_id => $fields) {
                $updated = self::saveProduct($fields, $variation_id);
                if (is_wp_error($updated)) { return $updated; }
            }
            if ($variation_updates) { \WC_Product_Variable::sync($product->get_id()); $product = wc_get_product($product->get_id()); }
            return self::serialize($product);
        } catch (\Exception $e) {
            return new \WP_Error('product_save_failed', $e->getMessage(), ['status' => 400]);
        }
    }
}
