<?php
namespace RMM\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class FloatingCartWidget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'rmm_floating_cart';
    }

    public function get_title() {
        return __('سبد خرید شناور منو (Floating Mini Cart)', 'restaurant-menu-manager');
    }

    public function get_icon() {
        return 'eicon-cart';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('تنظیمات سبد خرید شناور', 'restaurant-menu-manager'),
            ]
        );

        $this->add_control(
            'cart_btn_text',
            [
                'label'   => __('متن دکمه تکمیل سفارش', 'restaurant-menu-manager'),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __('مشاهده سفارش و تسویه‌حساب', 'restaurant-menu-manager'),
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE TAB ==================== //

        $this->start_controls_section(
            'section_style_cart_btn',
            [
                'label' => __('استایل دکمه سبد خرید شناور', 'restaurant-menu-manager'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'cart_bar_bg',
            [
                'label'     => __('رنگ پس‌زمینه نوار سبد', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-floating-cart-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'cart_bar_text_color',
            [
                'label'     => __('رنگ متن و مبلغ', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-floating-cart-btn, {{WRAPPER}} .rmm-cart-total' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'cart_typography',
                'label'    => __('تایپوگرافی متن سبد', 'restaurant-menu-manager'),
                'selector' => '{{WRAPPER}} .rmm-cart-label, {{WRAPPER}} .rmm-cart-total',
            ]
        );

        $this->add_control(
            'badge_heading',
            [
                'label'     => __('شمارنده اقلام (Badge)', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'badge_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه شمارنده', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-cart-badge-count' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'badge_text_color',
            [
                'label'     => __('رنگ عدد شمارنده', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-cart-badge-count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'cart_border_radius',
            [
                'label'      => __('گردی کادر سبد (Border Radius)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'separator'  => 'before',
                'selectors'  => [
                    '{{WRAPPER}} .rmm-floating-cart-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'cart_box_shadow',
                'selector' => '{{WRAPPER}} .rmm-floating-cart-btn',
            ]
        );

        $this->add_control(
            'cart_padding',
            [
                'label'      => __('فاصله داخلی (Padding)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-floating-cart-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        // Hide completely if in view_only mode
        $active_branch_id = \RMM\Branches\BranchContext::get_active_branch_id();
        $branch_mode = \RMM\Branches\BranchContext::get_branch_mode($active_branch_id);
        if ($branch_mode === 'view_only') {
            return;
        }

        $settings = $this->get_settings_for_display();
        $count = (WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
        $total = (WC()->cart) ? WC()->cart->get_cart_subtotal() : '0';
        $checkout_url = wc_get_checkout_url();

        $hidden_class = ($count === 0) ? 'rmm-hidden-cart' : '';
        ?>
        <div id="rmm-floating-cart-bar" class="rmm-floating-cart-wrapper <?php echo esc_attr($hidden_class); ?>">
            <a href="<?php echo esc_url($checkout_url); ?>" class="rmm-floating-cart-btn">
                <div class="rmm-cart-left">
                    <span class="rmm-cart-badge-count" id="rmm-cart-count"><?php echo esc_html($count); ?></span>
                    <span class="rmm-cart-label"><?php echo esc_html($settings['cart_btn_text']); ?></span>
                </div>
                <div class="rmm-cart-right">
                    <span class="rmm-cart-total" id="rmm-cart-total"><?php echo wp_kses_post($total); ?></span>
                    <span class="rmm-cart-arrow">&rarr;</span>
                </div>
            </a>
        </div>
        <?php
    }
}
