<?php
namespace RMM\Elementor\DynamicTags;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Core\DynamicTags\Tag')) {
    return;
}

class IngredientsTag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'rmm-food-ingredients';
    }

    public function get_title() {
        return __('ترکیبات و مواد اولیه غذا (TastyMenu)', 'fondogh-online-menu');
    }

    public function get_group() {
        return 'woocommerce';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    public function render() {
        $product_id = get_the_ID();
        if (!$product_id) {
            return;
        }

        $ingredients = get_post_meta($product_id, '_rmm_ingredients', true);

        // Multilingual fallback
        if (empty($ingredients) && class_exists('RMM\\I18n\\MultiLangManager')) {
            $ingredients = \RMM\I18n\MultiLangManager::get_product_translation($product_id, 'ingredients');
        }

        echo esc_html($ingredients);
    }
}
