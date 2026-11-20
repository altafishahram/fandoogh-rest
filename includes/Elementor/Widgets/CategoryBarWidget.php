<?php
namespace RMM\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class CategoryBarWidget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'rmm_category_bar';
    }

    public function get_title() {
        return __('نوار دسته‌بندی‌های چسبان (Sticky Bar)', 'restaurant-menu-manager');
    }

    public function get_icon() {
        return 'eicon-navigation-horizontal';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('تنظیمات دسته‌بندی‌ها', 'restaurant-menu-manager'),
            ]
        );

        $this->add_control(
            'is_sticky',
            [
                'label'        => __('چسبان در بالای صفحه هنگام اسکرول (Sticky)', 'restaurant-menu-manager'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_all_button',
            [
                'label'        => __('نمایش دکمه «همه غذاها»', 'restaurant-menu-manager'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE TAB ==================== //

        // 1. Container Style
        $this->start_controls_section(
            'section_style_container',
            [
                'label' => __('استایل کانتینر نوار دسته‌بندی', 'restaurant-menu-manager'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'container_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه نوار', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-category-nav-wrapper' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label'      => __('فاصله داخلی (Padding)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-scroll-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'container_shadow',
                'selector' => '{{WRAPPER}} .rmm-category-nav-wrapper.rmm-sticky-bar',
            ]
        );

        $this->end_controls_section();

        // 2. Category Pills Style
        $this->start_controls_section(
            'section_style_pills',
            [
                'label' => __('استایل دکمه‌های دسته‌بندی (Pills)', 'restaurant-menu-manager'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'pills_typography',
                'label'    => __('تایپوگرافی دکمه‌ها', 'restaurant-menu-manager'),
                'selector' => '{{WRAPPER}} .rmm-cat-pill',
            ]
        );

        $this->start_controls_tabs('tabs_pills_style');

        // Normal
        $this->start_controls_tab(
            'tab_pills_normal',
            [
                'label' => __('عادی', 'restaurant-menu-manager'),
            ]
        );

        $this->add_control(
            'pill_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-cat-pill' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'pill_text_color',
            [
                'label'     => __('رنگ متن', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-cat-pill' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'pill_border',
                'selector' => '{{WRAPPER}} .rmm-cat-pill',
            ]
        );

        $this->end_controls_tab();

        // Active / Hover
        $this->start_controls_tab(
            'tab_pills_active',
            [
                'label' => __('فعال و هاور (Active)', 'restaurant-menu-manager'),
            ]
        );

        $this->add_control(
            'pill_active_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه فعال', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-cat-pill.active, {{WRAPPER}} .rmm-cat-pill:hover' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'pill_active_text_color',
            [
                'label'     => __('رنگ متن فعال', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-cat-pill.active, {{WRAPPER}} .rmm-cat-pill:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'pill_active_shadow',
                'selector' => '{{WRAPPER}} .rmm-cat-pill.active, {{WRAPPER}} .rmm-cat-pill:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'image_position',
            [
                'label'                => __('موقعیت عکس نسبت به متن', 'restaurant-menu-manager'),
                'type'                 => \Elementor\Controls_Manager::CHOOSE,
                'separator'            => 'before',
                'options'              => [
                    'right'  => [
                        'title' => __('راست', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-h-align-right',
                    ],
                    'left'   => [
                        'title' => __('چپ', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'top'    => [
                        'title' => __('بالا (عمودی)', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-v-align-top',
                    ],
                    'bottom' => [
                        'title' => __('پایین', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-v-align-bottom',
                    ],
                ],
                'default'              => 'right',
                'selectors_dictionary' => [
                    'right'  => 'flex-direction: row; align-items: center;',
                    'left'   => 'flex-direction: row-reverse; align-items: center;',
                    'top'    => 'flex-direction: column; align-items: center; justify-content: center;',
                    'bottom' => 'flex-direction: column-reverse; align-items: center; justify-content: center;',
                ],
                'selectors'            => [
                    '{{WRAPPER}} .rmm-cat-pill' => '{{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'pill_alignment',
            [
                'label'     => __('تراز محتوای داخل دکمه', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::CHOOSE,
                'options'   => [
                    'flex-start' => [
                        'title' => __('راست / شروع', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                    'center'     => [
                        'title' => __('وسط‌چین', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'flex-end'   => [
                        'title' => __('چپ / پایان', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                ],
                'default'   => 'center',
                'selectors' => [
                    '{{WRAPPER}} .rmm-cat-pill' => 'justify-content: {{VALUE}}; text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pill_min_width',
            [
                'label'      => __('حداقل عرض دکمه (Min Width)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min' => 40,
                        'max' => 250,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-pill' => 'min-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label'      => __('فاصله تصویر تا متن', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-pill' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pill_border_radius',
            [
                'label'      => __('گردی لبه دکمه‌ها (Border Radius)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-pill' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pill_padding',
            [
                'label'      => __('فاصله داخلی دکمه‌ها (Padding)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-pill' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pills_gap',
            [
                'label'      => __('فاصله بین دکمه‌ها (Gap)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 2,
                        'max' => 40,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-scroll-container' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 3. Category Icons
        $this->start_controls_section(
            'section_style_icons',
            [
                'label' => __('استایل آیکون و تصویر دسته‌ها', 'restaurant-menu-manager'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => __('اندازه آیکون و عکس', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 12,
                        'max' => 80,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; min-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_border_radius',
            [
                'label'      => __('گردی آیکون و عکس', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $sticky_class = ($settings['is_sticky'] === 'yes') ? 'rmm-sticky-bar' : '';

        $active_lang = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa';
        $all_label = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('all_categories', $active_lang) : __('همه منو', 'restaurant-menu-manager');

        $categories = class_exists('\RMM\Menu\CategoryOrder')
            ? \RMM\Menu\CategoryOrder::get_ordered_categories(['hide_empty' => true])
            : get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
            ]);

        if (empty($categories) || is_wp_error($categories)) {
            return;
        }
        ?>
        <div class="rmm-category-nav-wrapper <?php echo esc_attr($sticky_class); ?>">
            <div class="rmm-cat-scroll-container">
                <?php if ($settings['show_all_button'] === 'yes') : ?>
                    <button class="rmm-cat-pill active" data-cat-id="all">
                        🍽️ <?php echo esc_html($all_label); ?>
                    </button>
                <?php endif; ?>

                <?php foreach ($categories as $cat) : ?>
                    <?php
                    $cat_name = $cat->name;
                    if (class_exists('\RMM\I18n\MultiLangManager') && $active_lang !== 'fa') {
                        $t_name = \RMM\I18n\MultiLangManager::get_term_translation($cat->term_id, $active_lang);
                        if (!empty($t_name)) {
                            $cat_name = $t_name;
                        }
                    }
                    $thumb_id = get_term_meta($cat->term_id, 'thumbnail_id', true);
                    $img_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : '';
                    ?>
                    <button class="rmm-cat-pill" data-cat-id="<?php echo esc_attr($cat->term_id); ?>" data-cat-slug="<?php echo esc_attr($cat->slug); ?>">
                        <?php if ($img_url) : ?>
                            <img src="<?php echo esc_url($img_url); ?>" class="rmm-cat-icon" alt="">
                        <?php endif; ?>
                        <span><?php echo esc_html($cat_name); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
