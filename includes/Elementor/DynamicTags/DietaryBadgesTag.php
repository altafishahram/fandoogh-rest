<?php
namespace RMM\Elementor\DynamicTags;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Core\DynamicTags\Tag')) {
    return;
}

class DietaryBadgesTag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'rmm-dietary-badge';
    }

    public function get_title() {
        return __('نشان غذایی (TastyMenu Badge)', 'fondogh-online-menu');
    }

    public function get_group() {
        return 'woocommerce';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    public function render() {
        $product_id = get_the_ID();
        $badge = \RMM\Menu\FoodMeta::get_badge_info($product_id);
        if ($badge) {
            echo '<span class="rmm-food-badge ' . esc_attr($badge['class']) . '">' . esc_html($badge['icon'] . ' ' . $badge['label']) . '</span>';
        }
    }
}
