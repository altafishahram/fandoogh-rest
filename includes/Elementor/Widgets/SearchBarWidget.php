<?php
namespace RMM\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Icons_Manager;

class SearchBarWidget extends Widget_Base {

    public function get_name() {
        return 'rmm_menu_search';
    }

    public function get_title() {
        return __('جستجوی زنده منو غذا (Menu Search Bar)', 'fondogh-online-menu');
    }

    public function get_icon() {
        return 'eicon-search';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    public function get_keywords() {
        return ['search', 'menu', 'food', 'جستجو', 'منو', 'غذا'];
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
                'label' => __('تنظیمات باکس جستجو', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'placeholder',
            [
                'label'       => __('متن راهنما (Placeholder)', 'fondogh-online-menu'),
                'type'        => Controls_Manager::TEXT,
                'default'     => '',
                'placeholder' => __('جستجو در منو (مثلاً پیتزا، برگر، سالاد...)', 'fondogh-online-menu'),
                'description' => __('اگر خالی بگذارید، متن راهنما به طور خودکار طبق زبان فعال منو بارگذاری می‌شود.', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'show_icon',
            [
                'label'        => __('نمایش آیکون جستجو', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'search_icon',
            [
                'label'       => __('انتخاب آیکون جستجو', 'fondogh-online-menu'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-search',
                    'library' => 'fa-solid',
                ],
                'condition'   => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_clear_btn',
            [
                'label'        => __('نمایش دکمه پاک‌کردن متن (Clear)', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'clear_icon',
            [
                'label'       => __('آیکون دکمه پاک‌کردن', 'fondogh-online-menu'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-times',
                    'library' => 'fa-solid',
                ],
                'condition'   => [
                    'show_clear_btn' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'search_max_width',
            [
                'label'      => __('حداکثر عرض باکس جستجو', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
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
                'label'     => __('تراز در بخش', 'fondogh-online-menu'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [
                        'title' => __('چپ', 'fondogh-online-menu'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('وسط', 'fondogh-online-menu'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => __('راست', 'fondogh-online-menu'),
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
                'label' => __('استایل باکس جستجو (پایه و ابعاد)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'input_typography',
                'label'    => __('تایپوگرافی متن', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->add_responsive_control(
            'input_border_radius',
            [
                'label'      => __('گردی لبه‌ها (Border Radius)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
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
                'label'      => __('فاصله داخلی (Padding)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
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

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => __('اندازه آیکون', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 12,
                        'max' => 36,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 18,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-search-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .rmm-search-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ------------------ LIGHT MODE STYLING ------------------ //
        $this->start_controls_section(
            'section_style_light_mode',
            [
                'label' => __('استایل حالت روشن (Light Mode)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'light_input_bg',
            [
                'label'     => __('رنگ پس‌زمینه (حالت روشن)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-menu-search-field, {{WRAPPER}} .rmm-menu-search-field' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_input_text_color',
            [
                'label'     => __('رنگ متن (حالت روشن)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-menu-search-field, {{WRAPPER}} .rmm-menu-search-field' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_input_placeholder_color',
            [
                'label'     => __('رنگ متن راهنما (حالت روشن)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#94a3b8',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-menu-search-field::placeholder, {{WRAPPER}} .rmm-menu-search-field::placeholder' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_icon_color',
            [
                'label'     => __('رنگ آیکون (حالت روشن)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#94a3b8',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-search-icon, {{WRAPPER}} .rmm-search-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'light_input_border',
                'selector' => 'body.light-theme {{WRAPPER}} .rmm-menu-search-field, {{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'light_input_shadow',
                'selector' => 'body.light-theme {{WRAPPER}} .rmm-menu-search-field, {{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->end_controls_section();

        // ------------------ DARK MODE STYLING ------------------ //
        $this->start_controls_section(
            'section_style_dark_mode',
            [
                'label' => __('استایل حالت تیره (Dark Mode)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'dark_input_bg',
            [
                'label'     => __('رنگ پس‌زمینه (حالت تیره)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#1e1418',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-menu-search-field, body.dark-theme {{WRAPPER}} .rmm-menu-search-field' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_input_text_color',
            [
                'label'     => __('رنگ متن (حالت تیره)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f8fafc',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-menu-search-field, body.dark-theme {{WRAPPER}} .rmm-menu-search-field' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_input_placeholder_color',
            [
                'label'     => __('رنگ متن راهنما (حالت تیره)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#a1a1aa',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-menu-search-field::placeholder, body.dark-theme {{WRAPPER}} .rmm-menu-search-field::placeholder' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_icon_color',
            [
                'label'     => __('رنگ آیکون (حالت تیره)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#d4af37',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-search-icon, body.dark-theme {{WRAPPER}} .rmm-search-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'dark_input_border',
                'selector' => 'body:not(.light-theme) {{WRAPPER}} .rmm-menu-search-field, body.dark-theme {{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'dark_input_shadow',
                'selector' => 'body:not(.light-theme) {{WRAPPER}} .rmm-menu-search-field, body.dark-theme {{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $active_lang = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa';
        $default_placeholder = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('search_placeholder', $active_lang) : __('جستجو در منو (مثلاً پیتزا، برگر، سالاد...)', 'fondogh-online-menu');

        $placeholder = !empty($settings['placeholder']) ? $settings['placeholder'] : $default_placeholder;
        $show_icon   = ($settings['show_icon'] === 'yes');
        $show_clear  = ($settings['show_clear_btn'] === 'yes');

        wp_enqueue_style('rmm-frontend-menu');
        wp_enqueue_script('rmm-frontend-menu');
        ?>
        <div class="rmm-search-widget-container">
            <div class="rmm-standalone-search-wrap rmm-live-search-bar">
                <?php if ($show_icon) : ?>
                    <span class="rmm-search-icon" aria-hidden="true">
                        <?php
                        if (!empty($settings['search_icon']['value'])) {
                            Icons_Manager::render_icon($settings['search_icon'], ['aria-hidden' => 'true']);
                        } else {
                            echo '<i class="fas fa-search" aria-hidden="true"></i>';
                        }
                        ?>
                    </span>
                <?php endif; ?>
                <input type="text" class="rmm-menu-search-field rmm-standalone-search-input" placeholder="<?php echo esc_attr($placeholder); ?>" autocomplete="off" aria-label="<?php echo esc_attr($placeholder); ?>">
                <?php if ($show_clear) : ?>
                    <button type="button" class="rmm-search-clear-btn" style="display:none;" aria-label="<?php esc_attr_e('پاک کردن متن جستجو', 'fondogh-online-menu'); ?>">
                        <?php
                        if (!empty($settings['clear_icon']['value'])) {
                            Icons_Manager::render_icon($settings['clear_icon'], ['aria-hidden' => 'true']);
                        } else {
                            echo '&times;';
                        }
                        ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
