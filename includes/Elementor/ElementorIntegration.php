<?php
namespace RMM\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

class ElementorIntegration {

    public function __construct() {
        add_action('elementor/elements/categories_registered', [$this, 'register_category']);
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        add_action('elementor/dynamic_tags/register', [$this, 'register_dynamic_tags']);
    }

    public function register_category($elements_manager) {
        $elements_manager->add_category(
            'tastymenu-category',
            [
                'title' => __('منو آنلاین فندق (Fondogh)', 'fondogh-online-menu'),
                'icon'  => 'fa fa-cutlery',
            ]
        );
    }

    public function register_widgets($widgets_manager) {
        // Base & Navigation Widgets
        $widgets_manager->register(new Widgets\BranchSelectorWidget());
        $widgets_manager->register(new Widgets\CategoryBarWidget());
        $widgets_manager->register(new Widgets\SearchBarWidget());
        $widgets_manager->register(new Widgets\ThemeToggleWidget());
        $widgets_manager->register(new Widgets\LanguageSwitcherWidget());
        $widgets_manager->register(new Widgets\FloatingCartWidget());

        // Modular Food Items & Loop Widgets
        $widgets_manager->register(new Widgets\FoodCardWidget());
        $widgets_manager->register(new Widgets\FoodLoopWidget());
        $widgets_manager->register(new Widgets\FoodModalWidget());

        // Full All-in-One Menu Grid
        $widgets_manager->register(new Widgets\FoodMenuWidget());
    }

    public function register_dynamic_tags($dynamic_tags) {
        if (class_exists('RMM\\Elementor\\DynamicTags\\BranchPriceTag')) {
            $dynamic_tags->register(new DynamicTags\BranchPriceTag());
            $dynamic_tags->register(new DynamicTags\BranchStockTag());
            $dynamic_tags->register(new DynamicTags\DietaryBadgesTag());
            $dynamic_tags->register(new DynamicTags\CaloriesTag());
            if (class_exists('RMM\\Elementor\\DynamicTags\\IngredientsTag')) {
                $dynamic_tags->register(new DynamicTags\IngredientsTag());
            }
        }
    }
}
