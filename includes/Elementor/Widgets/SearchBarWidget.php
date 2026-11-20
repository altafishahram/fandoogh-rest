<?php
namespace RMM\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class SearchBarWidget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'rmm_menu_search';
    }

    public function get_title() {
        return __('جستجوی زنده منو غذا (Menu Search Bar)', 'restaurant-menu-manager');
    }

    public function get_icon() {
        return 'eicon-search';
    }

    public function get_categories() {
        return ['tastymenu-category'];
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
                'label' => __('تنظیمات باکس جستجو', 'restaurant-menu-manager'),
            ]
        );

        $this->add_control(
            'placeholder',
            [
                'label'       => __('متن راهنما (Placeholder)', 'restaurant-menu-manager'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'default'     => '',
                'placeholder' => __('جستجو در منو (مثلاً پیتزا، برگر، سالاد...)', 'restaurant-menu-manager'),
                'description' => __('اگر خالی بگذارید، متن راهنما به طور خودکار طبق زبان فعال منو بارگذاری می‌شود.', 'restaurant-menu-manager'),
            ]
        );

        $this->add_control(
            'show_icon',
            [
                'label'        => __('نمایش آیکون ذره‌بین', 'restaurant-menu-manager'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_clear_btn',
            [
                'label'        => __('نمایش دکمه پاک‌کردن متن (×)', 'restaurant-menu-manager'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'search_max_width',
            [
                'label'      => __('حداکثر عرض باکس جستجو', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min' => 200,
                        'max' => 1200,
                    ],
                    '%' => [
                        'min' => 20,
                        'max' => 100,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-standalone-search-wrap' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label'     => __('تراز در بخش', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [
                        'title' => __('چپ', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('وسط', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => __('راست', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .rmm-search-widget-container' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE TAB ==================== //
        $this->start_controls_section(
            'section_style_input',
            [
                'label' => __('استایل فیلد ورودی جستجو', 'restaurant-menu-manager'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'input_typography',
                'label'    => __('تایپوگرافی متن', 'restaurant-menu-manager'),
                'selector' => '{{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->add_control(
            'input_text_color',
            [
                'label'     => __('رنگ متن تایپ‌شده', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => [
                    '{{WRAPPER}} .rmm-menu-search-field' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_placeholder_color',
            [
                'label'     => __('رنگ متن راهنما (Placeholder)', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#94a3b8',
                'selectors' => [
                    '{{WRAPPER}} .rmm-menu-search-field::placeholder' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rmm-menu-search-field' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'input_border',
                'selector' => '{{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->add_responsive_control(
            'input_border_radius',
            [
                'label'      => __('گردی لبه‌ها (Border Radius)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'      => 30,
                    'right'    => 30,
                    'bottom'   => 30,
                    'left'     => 30,
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-menu-search-field' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'input_padding',
            [
                'label'      => __('فاصله داخلی (Padding)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'      => 14,
                    'right'    => 48,
                    'bottom'   => 14,
                    'left'     => 44,
                    'unit'     => 'px',
                    'isLinked' => false,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-menu-search-field' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'input_shadow',
                'selector' => '{{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->add_control(
            'icon_style_heading',
            [
                'label'     => __('استایل آیکون ذره‌بین', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'icon_size',
            [
                'label'      => __('اندازه آیکون', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 12,
                        'max' => 32,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 18,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-search-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label'     => __('رنگ آیکون', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#94a3b8',
                'selectors' => [
                    '{{WRAPPER}} .rmm-search-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $active_lang = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa';
        $default_placeholder = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('search_placeholder', $active_lang) : __('جستجو در منو (مثلاً پیتزا، برگر، سالاد...)', 'restaurant-menu-manager');

        $placeholder = !empty($settings['placeholder']) ? $settings['placeholder'] : $default_placeholder;
        $show_icon = ($settings['show_icon'] === 'yes');
        $show_clear = ($settings['show_clear_btn'] === 'yes');

        wp_enqueue_style('rmm-frontend-menu');
        wp_enqueue_script('rmm-frontend-menu');
        ?>
        <div class="rmm-search-widget-container">
            <div class="rmm-standalone-search-wrap rmm-live-search-bar" style="display:inline-block; width:100%; text-align:right;">
                <?php if ($show_icon) : ?>
                    <span class="rmm-search-icon">🔍</span>
                <?php endif; ?>
                <input type="text" class="rmm-menu-search-field rmm-standalone-search-input" placeholder="<?php echo esc_attr($placeholder); ?>" autocomplete="off" aria-label="<?php echo esc_attr($placeholder); ?>">
                <?php if ($show_clear) : ?>
                    <button type="button" class="rmm-search-clear-btn" style="display:none;" aria-label="<?php esc_attr_e('پاک کردن متن جستجو', 'restaurant-menu-manager'); ?>">&times;</button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
