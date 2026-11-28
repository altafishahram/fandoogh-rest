<?php
namespace RMM\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class ThemeToggleWidget extends Widget_Base {

    public function get_name() {
        return 'rmm_theme_toggle';
    }

    public function get_title() {
        return __('سوئیچر روز و شب (Theme Toggle)', 'fondogh-online-menu');
    }

    public function get_icon() {
        return 'eicon-adjust';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    public function get_keywords() {
        return ['theme', 'dark mode', 'light mode', 'night', 'day', 'toggle', 'روز و شب', 'حالت تیره'];
    }

    public function get_script_depends() {
        return ['rmm-frontend-menu'];
    }

    public function get_style_depends() {
        return ['rmm-frontend-menu'];
    }

    protected function register_controls() {
        // ==================== CONTENT TAB ==================== //
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('تنظیمات سوئیچر تم', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'design_style',
            [
                'label'   => __('سبک و فرم دکمه', 'fondogh-online-menu'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'inline_circle',
                'options' => [
                    'inline_circle'   => __('دایره‌ای درون‌خطی (Inline Circle)', 'fondogh-online-menu'),
                    'luxury_gold'     => __('لوکس طلایی (Luxury Gold)', 'fondogh-online-menu'),
                    'glass_pill'      => __('گلس‌مورفیسم شفاف (Glassmorphism)', 'fondogh-online-menu'),
                    'minimal_icon'    => __('مینیمال بدون کادر (Minimal Icon)', 'fondogh-online-menu'),
                    'floating_corner' => __('شناور در گوشه صفحه (Floating Corner)', 'fondogh-online-menu'),
                ],
            ]
        );

        $this->add_control(
            'floating_pos',
            [
                'label'     => __('موقعیت دکمه شناور', 'fondogh-online-menu'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'bottom_left',
                'options'   => [
                    'bottom_left'  => __('پایین سمت چپ (Bottom Left)', 'fondogh-online-menu'),
                    'bottom_right' => __('پایین سمت راست (Bottom Right)', 'fondogh-online-menu'),
                    'top_left'     => __('بالا سمت چپ (Top Left)', 'fondogh-online-menu'),
                    'top_right'    => __('بالا سمت راست (Top Right)', 'fondogh-online-menu'),
                ],
                'condition' => [
                    'design_style' => 'floating_corner',
                ],
            ]
        );

        $this->add_control(
            'use_custom_icons',
            [
                'label'        => __('استفاده از آیکون‌های سفارشی (کتابخانه المنتور یا SVG)', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'icon_light',
            [
                'label'       => __('آیکون حالت روز (خورشید/روشن)', 'fondogh-online-menu'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-sun',
                    'library' => 'fa-solid',
                ],
                'condition'   => [
                    'use_custom_icons' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'icon_dark',
            [
                'label'       => __('آیکون حالت شب (ماه/تیره)', 'fondogh-online-menu'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-moon',
                    'library' => 'fa-solid',
                ],
                'condition'   => [
                    'use_custom_icons' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_label',
            [
                'label'        => __('نمایش برچسب متنی کنار آیکون', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'no',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'label_light',
            [
                'label'       => __('متن حالت روز', 'fondogh-online-menu'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('روز', 'fondogh-online-menu'),
                'condition'   => [
                    'show_label' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'label_dark',
            [
                'label'       => __('متن حالت شب', 'fondogh-online-menu'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('شب', 'fondogh-online-menu'),
                'condition'   => [
                    'show_label' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'align',
            [
                'label'     => __('تراز دکمه', 'fondogh-online-menu'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'right' => [
                        'title' => __('راست', 'fondogh-online-menu'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                    'center' => [
                        'title' => __('وسط', 'fondogh-online-menu'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'left' => [
                        'title' => __('چپ', 'fondogh-online-menu'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                ],
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .rmm-theme-toggle-widget-wrap' => 'text-align: {{VALUE}};',
                ],
                'condition' => [
                    'design_style!' => 'floating_corner',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE TAB ==================== //
        $this->start_controls_section(
            'section_style_btn',
            [
                'label' => __('ابعاد و ساختار دکمه', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'btn_size',
            [
                'label'      => __('اندازه دکمه (عرض و ارتفاع)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 28,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 44,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-theme-toggle-widget-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; min-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => __('اندازه آیکون', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 12,
                        'max' => 48,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-theme-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .rmm-theme-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'border_radius',
            [
                'label'      => __('گردی لبه‌ها (Border Radius)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-theme-toggle-widget-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'btn_padding',
            [
                'label'      => __('فاصله داخلی (Padding)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-theme-toggle-widget-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'label_typography',
                'label'     => __('تایپوگرافی متن برچسب', 'fondogh-online-menu'),
                'selector'  => '{{WRAPPER}} .rmm-theme-label',
                'condition' => [
                    'show_label' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ------------------ LIGHT MODE STYLING ------------------ //
        $this->start_controls_section(
            'section_style_light',
            [
                'label' => __('استایل در حالت روز (Light Mode)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'light_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-theme-toggle-widget-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_icon_color',
            [
                'label'     => __('رنگ آیکون (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#d97706',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-theme-toggle-widget-btn .rmm-theme-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_label_color',
            [
                'label'     => __('رنگ متن برچسب (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#1e293b',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-theme-label' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_label' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'light_border',
                'selector' => 'body.light-theme {{WRAPPER}} .rmm-theme-toggle-widget-btn',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'light_shadow',
                'selector' => 'body.light-theme {{WRAPPER}} .rmm-theme-toggle-widget-btn',
            ]
        );

        $this->end_controls_section();

        // ------------------ DARK MODE STYLING ------------------ //
        $this->start_controls_section(
            'section_style_dark',
            [
                'label' => __('استایل در حالت شب (Dark Mode)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'dark_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#271018',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-theme-toggle-widget-btn, body.dark-theme {{WRAPPER}} .rmm-theme-toggle-widget-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_icon_color',
            [
                'label'     => __('رنگ آیکون (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f59e0b',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-theme-toggle-widget-btn .rmm-theme-icon, body.dark-theme {{WRAPPER}} .rmm-theme-toggle-widget-btn .rmm-theme-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_label_color',
            [
                'label'     => __('رنگ متن برچسب (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f1f5f9',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-theme-label, body.dark-theme {{WRAPPER}} .rmm-theme-label' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_label' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'dark_border',
                'selector' => 'body:not(.light-theme) {{WRAPPER}} .rmm-theme-toggle-widget-btn, body.dark-theme {{WRAPPER}} .rmm-theme-toggle-widget-btn',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'dark_shadow',
                'selector' => 'body:not(.light-theme) {{WRAPPER}} .rmm-theme-toggle-widget-btn, body.dark-theme {{WRAPPER}} .rmm-theme-toggle-widget-btn',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings     = $this->get_settings_for_display();
        $style        = $settings['design_style'] ?? 'inline_circle';
        $floating_pos = $settings['floating_pos'] ?? 'bottom_left';
        $show_label   = ($settings['show_label'] === 'yes');
        $use_custom   = ($settings['use_custom_icons'] === 'yes');

        wp_enqueue_style('rmm-frontend-menu');
        wp_enqueue_script('rmm-frontend-menu');

        $classes = 'rmm-theme-toggle-widget-btn rmm-theme-toggle-btn rmm-toggle-style-' . esc_attr($style);
        if ($style === 'floating_corner') {
            $classes .= ' rmm-floating-pos-' . esc_attr($floating_pos);
        }
        ?>
        <div class="rmm-theme-toggle-widget-wrap">
            <button type="button" 
                    class="<?php echo esc_attr($classes); ?>" 
                    aria-label="<?php esc_attr_e('تغییر تم (روز / شب)', 'fondogh-online-menu'); ?>" 
                    title="<?php esc_attr_e('تغییر تم (روز / شب)', 'fondogh-online-menu'); ?>">
                
                <span class="rmm-theme-icon rmm-theme-icon-wrap" aria-hidden="true">
                    <?php if ($use_custom && !empty($settings['icon_light']['value']) && !empty($settings['icon_dark']['value'])) : ?>
                        <span class="rmm-icon-day">
                            <?php Icons_Manager::render_icon($settings['icon_light'], ['aria-hidden' => 'true']); ?>
                        </span>
                        <span class="rmm-icon-night">
                            <?php Icons_Manager::render_icon($settings['icon_dark'], ['aria-hidden' => 'true']); ?>
                        </span>
                    <?php else : ?>
                        <span class="rmm-icon-day"><i class="fas fa-sun" aria-hidden="true"></i></span>
                        <span class="rmm-icon-night"><i class="fas fa-moon" aria-hidden="true"></i></span>
                    <?php endif; ?>
                </span>

                <?php if ($show_label) : ?>
                    <span class="rmm-theme-label rmm-label-day"><?php echo esc_html($settings['label_light'] ?? __('روز', 'fondogh-online-menu')); ?></span>
                    <span class="rmm-theme-label rmm-label-night"><?php echo esc_html($settings['label_dark'] ?? __('شب', 'fondogh-online-menu')); ?></span>
                <?php endif; ?>
            </button>
        </div>
        <?php
    }
}
