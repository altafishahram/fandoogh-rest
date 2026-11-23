<?php
namespace RMM\Elementor\DynamicTags;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Core\DynamicTags\Tag')) {
    return;
}

class CaloriesTag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'rmm-food-calories';
    }

    public function get_title() {
        return __('کالری و زمان پخت (TastyMenu)', 'fondogh-online-menu');
    }

    public function get_group() {
        return 'woocommerce';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    public function render() {
        $product_id = get_the_ID();
        $calories = get_post_meta($product_id, '_rmm_calories', true);
        $prep_time = get_post_meta($product_id, '_rmm_prep_time', true);

        $out = [];
        if ($calories) {
            $out[] = '🔥 ' . esc_html($calories);
        }
        if ($prep_time) {
            $out[] = '⏱️ ' . sprintf(esc_html__('%d دقیقه', 'fondogh-online-menu'), $prep_time);
        }

        echo esc_html(implode(' | ', $out));
    }
}
