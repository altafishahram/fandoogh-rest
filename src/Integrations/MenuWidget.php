<?php
namespace FandooghRest\Integrations;

defined('ABSPATH') || exit;

final class MenuWidget extends \Elementor\Widget_Base
{
    public function get_name(): string { return 'admincafe_menu'; }
    public function get_title(): string { return __('Fandoogh Rest menu', 'fandoogh-rest'); }
    public function get_icon(): string { return 'eicon-cart'; }
    public function get_categories(): array { return ['general']; }
    public function get_keywords(): array { return ['menu', 'restaurant', 'cafe', 'woocommerce', 'منو', 'کافه']; }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', ['label' => __('Restaurant menu', 'fandoogh-rest')]);
        $this->add_control('component', [
            'label' => __('Component', 'fandoogh-rest'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'menu',
            'options' => ['menu' => __('Complete menu', 'fandoogh-rest'), 'categories' => __('Category navigation', 'fandoogh-rest'), 'products' => __('Product cards', 'fandoogh-rest'), 'cart' => __('Order basket', 'fandoogh-rest')],
        ]);
        $this->add_control('category', ['label' => __('Category ID (0 for all)', 'fandoogh-rest'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 0, 'min' => 0]);
        $this->add_control('branch', ['label' => __('Branch ID or slug (empty for current branch)', 'fandoogh-rest'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '']);
        $this->add_control('mode', [
            'label' => __('Ordering', 'fandoogh-rest'), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'auto',
            'options' => ['auto' => __('Follow restaurant settings', 'fandoogh-rest'), 'menu' => __('View menu only', 'fandoogh-rest')],
            'description' => __('This widget cannot enable an ordering channel disabled by the manager.', 'fandoogh-rest'),
        ]);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $component = in_array($settings['component'] ?? '', ['menu', 'categories', 'products', 'cart'], true) ? $settings['component'] : 'menu';
        echo (new Builders())->shortcode(['category' => absint($settings['category'] ?? 0), 'mode' => $settings['mode'] ?? 'auto', 'branch' => $settings['branch'] ?? ''], $component);
    }
}
