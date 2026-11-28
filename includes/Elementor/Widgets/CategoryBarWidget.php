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

class CategoryBarWidget extends Widget_Base {

    public function get_name() {
        return 'rmm_category_bar';
    }

    public function get_title() {
        return __('نوار دسته‌بندی‌های منو (Category Bar)', 'fondogh-online-menu');
    }

    public function get_icon() {
        return 'eicon-navigation-horizontal';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    public function get_keywords() {
        return ['category', 'bar', 'pills', 'tabs', 'دسته‌بندی', 'نوار'];
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
                'label' => __('تنظیمات دسته‌بندی‌ها', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'is_sticky',
            [
                'label'        => __('چسبان در بالای صفحه هنگام اسکرول (Sticky)', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_all_button',
            [
                'label'        => __('نمایش دکمه «همه غذاها»', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'all_icon',
            [
                'label'       => __('آیکون دکمه «همه غذاها»', 'fondogh-online-menu'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-utensils',
                    'library' => 'fa-solid',
                ],
                'condition'   => [
                    'show_all_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'default_cat_icon',
            [
                'label'       => __('آیکون پیش‌فرض دسته‌ها (در صورت نداشتن تصویر)', 'fondogh-online-menu'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-bowl-food',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_responsive_control(
            'image_position',
            [
                'label'                => __('موقعیت آیکون نسبت به متن', 'fondogh-online-menu'),
                'type'                 => Controls_Manager::CHOOSE,
                'separator'            => 'before',
                'options'              => [
                    'right'  => [
                        'title' => __('راست', 'fondogh-online-menu'),
                        'icon'  => 'eicon-h-align-right',
                    ],
                    'left'   => [
                        'title' => __('چپ', 'fondogh-online-menu'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'top'    => [
                        'title' => __('بالا (عمودی)', 'fondogh-online-menu'),
                        'icon'  => 'eicon-v-align-top',
                    ],
                    'bottom' => [
                        'title' => __('پایین', 'fondogh-online-menu'),
                        'icon'  => 'eicon-v-align-bottom',
                    ],
                ],
                'default'              => 'right',
                'selectors_dictionary' => [
                    'right'  => 'row',
                    'left'   => 'row-reverse',
                    'top'    => 'column',
                    'bottom' => 'column-reverse',
                ],
                'selectors'            => [
                    '{{WRAPPER}} .rmm-cat-pill' => 'flex-direction: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pill_gap',
            [
                'label'      => __('فاصله بین دکمه‌های دسته‌بندی', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 2,
                        'max' => 40,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-scroll-container' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE TAB ==================== //
        // 1. Container Style
        $this->start_controls_section(
            'section_style_container',
            [
                'label' => __('استایل کانتینر نوار دسته‌بندی', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label'      => __('فاصله داخلی (Padding)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-scroll-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_radius',
            [
                'label'      => __('گردی لبه کانتینر', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-category-nav-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 2. Pills Base & Typography
        $this->start_controls_section(
            'section_style_pills_base',
            [
                'label' => __('تایپوگرافی و ابعاد دکمه‌ها', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'pills_typography',
                'label'    => __('تایپوگرافی دکمه‌ها', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-cat-pill',
            ]
        );

        $this->add_responsive_control(
            'pill_padding',
            [
                'label'      => __('فاصله داخلی دکمه (Padding)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'      => '8',
                    'right'    => '16',
                    'bottom'   => '8',
                    'left'     => '16',
                    'unit'     => 'px',
                    'isLinked' => false,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-pill' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pill_border_radius',
            [
                'label'      => __('گردی لبه دکمه (Border Radius)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'      => '24',
                    'right'    => '24',
                    'bottom'   => '24',
                    'left'     => '24',
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-pill' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => __('اندازه آیکون دسته‌ها', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 12,
                        'max' => 60,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-cat-icon'     => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; min-width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .rmm-cat-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .rmm-cat-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 3. LIGHT MODE STYLING
        $this->start_controls_section(
            'section_style_pills_light',
            [
                'label' => __('استایل حالت روشن (Light Mode)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'light_container_bg',
            [
                'label'     => __('رنگ پس‌زمینه کانتینر (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(255, 255, 255, 0.95)',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-category-nav-wrapper, {{WRAPPER}} .rmm-category-nav-wrapper' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_pill_bg',
            [
                'label'     => __('رنگ دکمه عادی (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f1f5f9',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-cat-pill, {{WRAPPER}} .rmm-cat-pill' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_pill_text_color',
            [
                'label'     => __('رنگ متن عادی (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#334155',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-cat-pill, {{WRAPPER}} .rmm-cat-pill' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_pill_active_bg',
            [
                'label'     => __('رنگ دکمه فعال/هاور (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#c82333',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-cat-pill.active, body.light-theme {{WRAPPER}} .rmm-cat-pill:hover, {{WRAPPER}} .rmm-cat-pill.active, {{WRAPPER}} .rmm-cat-pill:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_pill_active_text_color',
            [
                'label'     => __('رنگ متن فعال/هاور (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-cat-pill.active, body.light-theme {{WRAPPER}} .rmm-cat-pill:hover, {{WRAPPER}} .rmm-cat-pill.active, {{WRAPPER}} .rmm-cat-pill:hover' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 4. DARK MODE STYLING
        $this->start_controls_section(
            'section_style_pills_dark',
            [
                'label' => __('استایل حالت تیره (Dark Mode)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'dark_container_bg',
            [
                'label'     => __('رنگ پس‌زمینه کانتینر (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(26, 6, 10, 0.95)',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-category-nav-wrapper, body.dark-theme {{WRAPPER}} .rmm-category-nav-wrapper' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_pill_bg',
            [
                'label'     => __('رنگ دکمه عادی (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(255, 255, 255, 0.08)',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-cat-pill, body.dark-theme {{WRAPPER}} .rmm-cat-pill' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_pill_text_color',
            [
                'label'     => __('رنگ متن عادی (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e2e8f0',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-cat-pill, body.dark-theme {{WRAPPER}} .rmm-cat-pill' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_pill_active_bg',
            [
                'label'     => __('رنگ دکمه فعال/هاور (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#d4af37',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-cat-pill.active, body:not(.light-theme) {{WRAPPER}} .rmm-cat-pill:hover, body.dark-theme {{WRAPPER}} .rmm-cat-pill.active, body.dark-theme {{WRAPPER}} .rmm-cat-pill:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_pill_active_text_color',
            [
                'label'     => __('رنگ متن فعال/هاور (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#120305',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-cat-pill.active, body:not(.light-theme) {{WRAPPER}} .rmm-cat-pill:hover, body.dark-theme {{WRAPPER}} .rmm-cat-pill.active, body.dark-theme {{WRAPPER}} .rmm-cat-pill:hover' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings     = $this->get_settings_for_display();
        $sticky_class = ($settings['is_sticky'] === 'yes') ? 'rmm-sticky-bar' : '';

        $active_lang  = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa';
        $all_label    = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('all_categories', $active_lang) : __('همه منو', 'fondogh-online-menu');

        $categories = class_exists('\RMM\Menu\CategoryOrder')
            ? \RMM\Menu\CategoryOrder::get_ordered_categories(['hide_empty' => true])
            : get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
            ]);

        if (empty($categories) || is_wp_error($categories)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="rmm-empty-cat-notice" >' . esc_html__('نوار دسته‌بندی: هیچ دسته‌بندی غذای فعالی در سیستم یافت نشد.', 'fondogh-online-menu') . '</div>';
            }
            return;
        }
        ?>
        <nav class="rmm-category-nav-wrapper <?php echo esc_attr($sticky_class); ?>" aria-label="<?php esc_attr_e('دسته‌بندی‌های منو', 'fondogh-online-menu'); ?>">
            <div class="rmm-cat-scroll-container">
                <?php if ($settings['show_all_button'] === 'yes') : ?>
                    <button type="button" class="rmm-cat-pill active" data-category="all" data-cat-id="all">
                        <span class="rmm-cat-icon" aria-hidden="true">
                            <?php
                            if (!empty($settings['all_icon']['value'])) {
                                Icons_Manager::render_icon($settings['all_icon'], ['aria-hidden' => 'true']);
                            } else {
                                echo '<i class="fas fa-utensils" aria-hidden="true"></i>';
                            }
                            ?>
                        </span>
                        <span class="rmm-cat-title"><?php echo esc_html($all_label); ?></span>
                    </button>
                <?php endif; ?>

                <?php foreach ($categories as $cat) :
                    $thumb_id  = get_term_meta($cat->term_id, 'thumbnail_id', true);
                    $image_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : '';
                    $t_name    = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_term_translation($cat->term_id, $active_lang) : '';
                    $title     = !empty($t_name) ? $t_name : $cat->name;
                    ?>
                    <button type="button" class="rmm-cat-pill" data-category="<?php echo esc_attr($cat->slug); ?>" data-cat-id="<?php echo esc_attr($cat->term_id); ?>">
                        <span class="rmm-cat-icon" aria-hidden="true">
                            <?php if ($image_url) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" width="24" height="24">
                            <?php elseif (!empty($settings['default_cat_icon']['value'])) : ?>
                                <?php Icons_Manager::render_icon($settings['default_cat_icon'], ['aria-hidden' => 'true']); ?>
                            <?php else : ?>
                                <i class="fas fa-bowl-food" aria-hidden="true"></i>
                            <?php endif; ?>
                        </span>
                        <span class="rmm-cat-title"><?php echo esc_html($title); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </nav>
        <?php
    }
}
