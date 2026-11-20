<?php
namespace RMM\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;

class ThemeToggleWidget extends Widget_Base {

    public function get_name() {
        return 'rmm_theme_toggle';
    }

    public function get_title() {
        return __('سوئیچر روز و شب (Theme Toggle)', 'restaurant-menu-manager');
    }

    public function get_icon() {
        return 'eicon-adjust';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    public function get_keywords() {
        return ['theme', 'dark mode', 'light mode', 'night', 'day', 'toggle', 'شرقی', 'روز و شب'];
    }

    protected function register_controls() {
        // Tab: Content
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('تنظیمات سوئیچر تم', 'restaurant-menu-manager'),
            ]
        );

        $this->add_control(
            'design_style',
            [
                'label'   => __('سبک و حالت دکمه', 'restaurant-menu-manager'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'inline_circle',
                'options' => [
                    'inline_circle'   => __('دایره‌ای درون‌خطی (Inline Circle)', 'restaurant-menu-manager'),
                    'luxury_gold'     => __('لوکس طلایی شرقی (Luxury Gold)', 'restaurant-menu-manager'),
                    'glass_pill'      => __('گلس‌مورفیسم شفاف (Glassmorphism)', 'restaurant-menu-manager'),
                    'minimal_icon'    => __('مینیمال بدون کادر (Minimal Icon)', 'restaurant-menu-manager'),
                    'floating_corner' => __('شناور در گوشه صفحه (Floating Corner)', 'restaurant-menu-manager'),
                ],
            ]
        );

        $this->add_control(
            'floating_pos',
            [
                'label'     => __('موقعیت شناور', 'restaurant-menu-manager'),
                'type'      => Controls_Manager::SELECT,
                'default'   => 'bottom_left',
                'options'   => [
                    'bottom_left'  => __('پایین سمت چپ (Bottom Left)', 'restaurant-menu-manager'),
                    'bottom_right' => __('پایین سمت راست (Bottom Right)', 'restaurant-menu-manager'),
                    'top_left'     => __('بالا سمت چپ (Top Left)', 'restaurant-menu-manager'),
                    'top_right'    => __('بالا سمت راست (Top Right)', 'restaurant-menu-manager'),
                ],
                'condition' => [
                    'design_style' => 'floating_corner',
                ],
            ]
        );

        $this->add_control(
            'icon_type',
            [
                'label'   => __('نوع آیکون‌های روز و شب', 'restaurant-menu-manager'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'half_circle',
                'options' => [
                    'half_circle' => '🌓 / 🌗 (نیم‌دایره روز و شب)',
                    'moon_sun'    => '🌙 / ☀️ (ماه و خورشید)',
                    'sun_stars'   => '☀️ / ✨ (خورشید و ستاره)',
                    'lantern'     => '🏮 / 💡 (فانوس شرقی و لامپ)',
                ],
            ]
        );

        $this->add_responsive_control(
            'align',
            [
                'label'     => __('تراز دکمه', 'restaurant-menu-manager'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'right' => [
                        'title' => __('راست', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                    'center' => [
                        'title' => __('وسط', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'left' => [
                        'title' => __('چپ', 'restaurant-menu-manager'),
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

        // Tab: Style
        $this->start_controls_section(
            'section_style_btn',
            [
                'label' => __('استایل دکمه و آیکون', 'restaurant-menu-manager'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'btn_size',
            [
                'label'      => __('ابعاد دکمه (عرض و ارتفاع)', 'restaurant-menu-manager'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 28,
                        'max' => 80,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 44,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-theme-toggle-widget-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => __('اندازه آیکون', 'restaurant-menu-manager'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 14,
                        'max' => 48,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-theme-toggle-widget-btn .rmm-theme-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'dark_mode_heading',
            [
                'label'     => __('تنظیمات در حالت شب (Dark)', 'restaurant-menu-manager'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'dark_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه (حالت شب)', 'restaurant-menu-manager'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-theme-toggle-widget-btn' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'dark_icon_color',
            [
                'label'     => __('رنگ آیکون (حالت شب)', 'restaurant-menu-manager'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-theme-toggle-widget-btn .rmm-theme-icon' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'light_mode_heading',
            [
                'label'     => __('تنظیمات در حالت روز (Light)', 'restaurant-menu-manager'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'light_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه (حالت روز)', 'restaurant-menu-manager'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-theme-toggle-widget-btn' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'light_icon_color',
            [
                'label'     => __('رنگ آیکون (حالت روز)', 'restaurant-menu-manager'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-theme-toggle-widget-btn .rmm-theme-icon' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'btn_border',
                'selector' => '{{WRAPPER}} .rmm-theme-toggle-widget-btn',
            ]
        );

        $this->add_responsive_control(
            'border_radius',
            [
                'label'      => __('گردی لبه‌ها (Border Radius)', 'restaurant-menu-manager'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-theme-toggle-widget-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'btn_shadow',
                'selector' => '{{WRAPPER}} .rmm-theme-toggle-widget-btn',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings     = $this->get_settings_for_display();
        $style        = $settings['design_style'] ?? 'inline_circle';
        $icon_type    = $settings['icon_type'] ?? 'half_circle';
        $floating_pos = $settings['floating_pos'] ?? 'bottom_left';

        // Enqueue frontend scripts & styles
        wp_enqueue_style('rmm-frontend-menu');
        wp_enqueue_script('rmm-frontend-menu');

        $initial_icon = '🌓';
        if ($icon_type === 'moon_sun') {
            $initial_icon = '🌙';
        } elseif ($icon_type === 'sun_stars') {
            $initial_icon = '✨';
        } elseif ($icon_type === 'lantern') {
            $initial_icon = '🏮';
        }

        $classes = 'rmm-theme-toggle-widget-btn rmm-theme-toggle-btn rmm-toggle-style-' . esc_attr($style);
        if ($style === 'floating_corner') {
            $classes .= ' rmm-floating-pos-' . esc_attr($floating_pos);
        }
        ?>
        <div class="rmm-theme-toggle-widget-wrap">
            <button type="button" 
                    class="<?php echo esc_attr($classes); ?>" 
                    data-icon-type="<?php echo esc_attr($icon_type); ?>"
                    aria-label="<?php esc_attr_e('تغییر تم (روز / شب)', 'restaurant-menu-manager'); ?>" 
                    title="<?php esc_attr_e('تغییر تم (روز / شب)', 'restaurant-menu-manager'); ?>">
                <span class="rmm-theme-icon"><?php echo esc_html($initial_icon); ?></span>
            </button>
        </div>
        <?php
    }
}
