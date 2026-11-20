<?php
namespace RMM\Elementor\DynamicTags;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Core\DynamicTags\Tag')) {
    return;
}

class BranchPriceTag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'rmm-branch-price';
    }

    public function get_title() {
        return __('قیمت شعبه فعال (TastyMenu)', 'restaurant-menu-manager');
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

        $price = $product->get_price();
        echo wc_price($price);
    }
}
