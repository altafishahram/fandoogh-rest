<?php
namespace RMM\Elementor\DynamicTags;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Core\DynamicTags\Tag')) {
    return;
}

class BranchStockTag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'rmm-branch-stock';
    }

    public function get_title() {
        return __('وضعیت موجودی شعبه (TastyMenu)', 'fondogh-online-menu');
    }

    public function get_group() {
        return 'woocommerce';
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    public function render() {
        $product = wc_get_product(get_the_ID());
        if (!$product) {
            return;
        }

        $in_stock = $product->is_in_stock();
        if ($in_stock) {
            echo '<span class="rmm-stock-in">' . esc_html__('موجود', 'fondogh-online-menu') . '</span>';
        } else {
            echo '<span class="rmm-stock-out">' . esc_html__('ناموجود', 'fondogh-online-menu') . '</span>';
        }
    }
}
