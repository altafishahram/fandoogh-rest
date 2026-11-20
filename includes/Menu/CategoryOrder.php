<?php
namespace RMM\Menu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CategoryOrder
 * 
 * Manages category ordering and prioritization across the restaurant menu,
 * PWA management portal, and Elementor widgets.
 */
class CategoryOrder {

    const OPTION_KEY = 'rmm_category_order';

    /**
     * Get all product categories sorted by priority order
     *
     * @param array $args
     * @return array Array of \WP_Term objects
     */
    public static function get_ordered_categories(array $args = []) {
        $defaults = [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ];
        $parsed_args = wp_parse_args($args, $defaults);

        $terms = get_terms($parsed_args);
        if (empty($terms) || is_wp_error($terms)) {
            return [];
        }

        $saved_order = self::get_saved_order();

        usort($terms, function ($a, $b) use ($saved_order) {
            $pos_a = array_search(intval($a->term_id), $saved_order, true);
            $pos_b = array_search(intval($b->term_id), $saved_order, true);

            // Both found in saved explicit order
            if ($pos_a !== false && $pos_b !== false) {
                return $pos_a <=> $pos_b;
            }
            if ($pos_a !== false) {
                return -1;
            }
            if ($pos_b !== false) {
                return 1;
            }

            // Fallback to WooCommerce native term meta 'order'
            $order_a = get_term_meta($a->term_id, 'order', true);
            $order_b = get_term_meta($b->term_id, 'order', true);
            if ($order_a !== '' && $order_b !== '') {
                $diff = intval($order_a) <=> intval($order_b);
                if ($diff !== 0) {
                    return $diff;
                }
            } elseif ($order_a !== '') {
                return -1;
            } elseif ($order_b !== '') {
                return 1;
            }

            // Fallback to name alphabetical
            return strcasecmp($a->name, $b->name);
        });

        return $terms;
    }

    /**
     * Save category order list
     *
     * @param array $category_ids List of integer term IDs in order
     * @return bool
     */
    public static function save_category_order(array $category_ids) {
        $sanitized_ids = [];
        foreach ($category_ids as $id) {
            $id = absint($id);
            if ($id > 0 && !in_array($id, $sanitized_ids, true)) {
                $sanitized_ids[] = $id;
            }
        }

        update_option(self::OPTION_KEY, $sanitized_ids, false);

        // Sync with WooCommerce native term meta 'order'
        foreach ($sanitized_ids as $index => $term_id) {
            update_term_meta($term_id, 'order', $index + 1);
        }

        if (function_exists('clean_term_cache')) {
            clean_term_cache($sanitized_ids, 'product_cat');
        }

        return true;
    }

    /**
     * Get saved order array of term IDs
     *
     * @return array
     */
    public static function get_saved_order() {
        $saved = get_option(self::OPTION_KEY, []);
        if (!is_array($saved)) {
            return [];
        }
        return array_map('intval', $saved);
    }

    /**
     * Get priority of a specific category (1-based index)
     *
     * @param int $term_id
     * @return int
     */
    public static function get_category_priority($term_id) {
        $saved = self::get_saved_order();
        $pos = array_search(intval($term_id), $saved, true);
        if ($pos !== false) {
            return $pos + 1;
        }

        $order_meta = get_term_meta($term_id, 'order', true);
        if ($order_meta !== '') {
            return intval($order_meta);
        }

        return 999;
    }
}
