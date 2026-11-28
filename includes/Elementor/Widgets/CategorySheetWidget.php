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

/**
 * Category Sheet Widget
 *
 * A trigger button that opens a responsive grid of product categories
 * (images / icons + labels). Clicking a card applies the same instant
 * filter used by the classic Category Bar, then closes the sheet.
 *
 * The classic Category Bar widget is untouched; this is an independent widget.
 */
class CategorySheetWidget extends Widget_Base {

    public function get_name() {
        return 'rmm_category_sheet';
    }

    public function get_title() {
        return __('دسته‌بندی تصویری (Category Sheet)', 'fondogh-online-menu');
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    public function get_keywords() {
        return ['category', 'sheet', 'popup', 'grid', 'دسته‌بندی', 'پاپ‌آپ', 'شیت'];
    }

    public function get_style_depends() {
        return ['rmm-category-sheet'];
    }

    public function get_script_depends() {
        return ['rmm-category-sheet'];
    }

    protected function register_controls() {
        /* ==================== CONTENT TAB ==================== */

        $this->start_controls_section(
            'section_content',
            [
                'label' => __('دکمه بازکننده و شیت', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'trigger_text',
            [
                'label'       => __('متن دکمه', 'fondogh-online-menu'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('دسته‌بندی‌ها', 'fondogh-online-menu'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'trigger_icon',
            [
                'label'   => __('آیکون دکمه', 'fondogh-online-menu'),
                'type'    => Controls_Manager::ICONS,
                'default' => [
                    'value'   => 'fas fa-th-large',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'trigger_position',
            [
                'label'   => __('نحوه قرارگیری دکمه', 'fondogh-online-menu'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'inline',
                'options' => [
                    'inline'   => __('داخل صفحه (همان‌جا که ویجت را گذاشتید)', 'fondogh-online-menu'),
                    'floating' => __('شناور گوشه صفحه', 'fondogh-online-menu'),
                ],
            ]
        );

        $this->add_control(
            'show_all_card',
            [
                'label'        => __('نمایش کارت «همه دسته‌ها»', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'separator'    => 'before',
            ]
        );

        $this->add_control(
            'all_label',
            [
                'label'       => __('متن کارت «همه دسته‌ها»', 'fondogh-online-menu'),
                'type'        => Controls_Manager::TEXT,
                'default'     => '',
                'placeholder' => __('خالی بگذارید تا از ترجمه خودکار افزونه استفاده شود', 'fondogh-online-menu'),
                'label_block' => true,
                'condition'   => [
                    'show_all_card' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'all_icon',
            [
                'label'     => __('آیکون کارت «همه دسته‌ها»', 'fondogh-online-menu'),
                'type'      => Controls_Manager::ICONS,
                'default'   => [
                    'value'   => 'fas fa-utensils',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_all_card' => 'yes',
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
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'sheet_heading',
            [
                'label'     => __('شیت دسته‌بندی', 'fondogh-online-menu'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'sheet_title',
            [
                'label'       => __('عنوان شیت', 'fondogh-online-menu'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('دسته‌بندی‌ها', 'fondogh-online-menu'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'show_sheet_title',
            [
                'label'        => __('نمایش عنوان در بالای شیت', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_secondary_label',
            [
                'label'        => __('نمایش نام زبان دوم زیر هر کارت', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => '',
                'return_value' => 'yes',
                'description'  => __('اگر برای دسته ترجمه ثبت شده باشد، نام زبان دیگر با حروف کوچک نمایش داده می‌شود.', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'close_on_select',
            [
                'label'        => __('بستن شیت بعد از انتخاب دسته', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->end_controls_section();

        /* ---------- Layout ---------- */

        $this->start_controls_section(
            'section_layout',
            [
                'label' => __('ستون‌ها، ابعاد و فاصله‌ها', 'fondogh-online-menu'),
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label'              => __('تعداد ستون‌ها', 'fondogh-online-menu'),
                'type'               => Controls_Manager::SELECT,
                'default'            => '4',
                'tablet_default'     => '3',
                'mobile_default'     => '3',
                'options'            => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'selectors'          => [
                    '{{WRAPPER}} .rmm-catsheet-grid' => '--rmm-cs-cols: {{VALUE}};',
                ],
                'frontend_available' => true,
            ]
        );

        $this->add_responsive_control(
            'media_size',
            [
                'label'      => __('اندازه تصویر / آیکون دسته', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 28,
                        'max' => 120,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 56,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-catsheet-media' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'media_shape',
            [
                'label'              => __('شکل تصویر / آیکون', 'fondogh-online-menu'),
                'type'               => Controls_Manager::SELECT,
                'default'            => 'circle',
                'options'            => [
                    'circle'  => __('دایره', 'fondogh-online-menu'),
                    'rounded' => __('مربع گرد', 'fondogh-online-menu'),
                    'square'  => __('مربع', 'fondogh-online-menu'),
                ],
                'selectors_dictionary' => [
                    'circle'  => '50%',
                    'rounded' => '14px',
                    'square'  => '4px',
                ],
                'selectors'          => [
                    '{{WRAPPER}} .rmm-catsheet-media' => 'border-radius: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_gap',
            [
                'label'      => __('فاصله بین کارت‌ها', 'fondogh-online-menu'),
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
                    '{{WRAPPER}} .rmm-catsheet-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'sheet_max_width',
            [
                'label'      => __('حداکثر عرض شیت', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min' => 320,
                        'max' => 1000,
                    ],
                    '%'  => [
                        'min' => 50,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 560,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-catsheet-panel' => '--rmm-cs-max-width: {{SIZE}}{{UNIT}};',
                ],
                'separator'  => 'before',
            ]
        );

        $this->add_responsive_control(
            'panel_max_height',
            [
                'label'      => __('حداکثر ارتفاع شیت', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['vh', 'px'],
                'range'      => [
                    'vh' => [
                        'min' => 40,
                        'max' => 95,
                    ],
                    'px' => [
                        'min' => 300,
                        'max' => 1200,
                    ],
                ],
                'default'    => [
                    'unit' => 'vh',
                    'size' => 85,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-catsheet-panel' => 'max-height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'sheet_padding',
            [
                'label'      => __('فاصله داخلی شیت', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'      => '18',
                    'right'    => '18',
                    'bottom'   => '18',
                    'left'     => '18',
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-catsheet-panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label'      => __('فاصله داخلی کارت‌ها', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'      => '12',
                    'right'    => '8',
                    'bottom'   => '12',
                    'left'     => '8',
                    'unit'     => 'px',
                    'isLinked' => false,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-catsheet-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_radius',
            [
                'label'      => __('گردی گوشه کارت‌ها', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'      => '16',
                    'right'    => '16',
                    'bottom'   => '16',
                    'left'     => '16',
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-catsheet-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        /* ==================== STYLE TAB ==================== */

        /* ---------- Trigger ---------- */

        $this->start_controls_section(
            'section_style_trigger',
            [
                'label' => __('دکمه بازکننده', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'trigger_typography',
                'label'    => __('تایپوگرافی دکمه', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-catsheet-trigger',
            ]
        );

        $this->add_responsive_control(
            'trigger_icon_size',
            [
                'label'      => __('اندازه آیکون دکمه', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => [
                        'min' => 10,
                        'max' => 40,
                    ],
                    'em' => [
                        'min' => 0.8,
                        'max' => 2,
                        'step' => 0.1,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 16,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-catsheet-trigger-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'trigger_padding',
            [
                'label'      => __('فاصله داخلی دکمه', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'      => '10',
                    'right'    => '18',
                    'bottom'   => '10',
                    'left'     => '18',
                    'unit'     => 'px',
                    'isLinked' => false,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-catsheet-trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'trigger_radius',
            [
                'label'      => __('گردی گوشه دکمه', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'      => '12',
                    'right'    => '12',
                    'bottom'   => '12',
                    'left'     => '12',
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-catsheet-trigger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('tabs_trigger_state');

        $this->start_controls_tab(
            'tab_trigger_normal',
            [
                'label' => __('عادی', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'trigger_bg',
            [
                'label'     => __('پس‌زمینه', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-trigger' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'trigger_color',
            [
                'label'     => __('رنگ متن و آیکون', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-trigger' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_trigger_hover',
            [
                'label' => __('هاور', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'trigger_bg_hover',
            [
                'label'     => __('پس‌زمینه', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-trigger:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'trigger_color_hover',
            [
                'label'     => __('رنگ متن و آیکون', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-trigger:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'trigger_border_color_hover',
            [
                'label'     => __('رنگ حاشیه', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-trigger:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'      => 'trigger_border',
                'label'     => __('حاشیه دکمه', 'fondogh-online-menu'),
                'selector'  => '{{WRAPPER}} .rmm-catsheet-trigger',
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'trigger_shadow',
                'label'    => __('سایه دکمه', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-catsheet-trigger',
            ]
        );

        $this->end_controls_section();

        /* ---------- Panel ---------- */

        $this->start_controls_section(
            'section_style_panel',
            [
                'label' => __('پنل شیت', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'panel_bg',
            [
                'label'     => __('پس‌زمینه شیت', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-panel' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'backdrop_color',
            [
                'label'     => __('رنگ پرده پشت شیت', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-backdrop' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'panel_radius',
            [
                'label'      => __('گردی گوشه شیت', 'fondogh-online-menu'),
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
                    '{{WRAPPER}} .rmm-catsheet-panel' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'panel_shadow',
                'label'    => __('سایه شیت', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-catsheet-panel',
            ]
        );

        $this->add_control(
            'title_heading',
            [
                'label'     => __('عنوان شیت', 'fondogh-online-menu'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'sheet_title_typography',
                'label'    => __('تایپوگرافی عنوان', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-catsheet-title',
            ]
        );

        $this->add_control(
            'sheet_title_color',
            [
                'label'     => __('رنگ عنوان', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'close_heading',
            [
                'label'     => __('دکمه بستن', 'fondogh-online-menu'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'close_color',
            [
                'label'     => __('رنگ آیکون بستن', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-close' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .rmm-catsheet-handle' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'close_size',
            [
                'label'      => __('اندازه دکمه بستن', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 20,
                        'max' => 60,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 32,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-catsheet-close' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        /* ---------- Cards ---------- */

        $this->start_controls_section(
            'section_style_cards',
            [
                'label' => __('کارت‌های دسته', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_bg',
            [
                'label'     => __('پس‌زمینه کارت', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_border_color',
            [
                'label'     => __('رنگ حاشیه کارت', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-card' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_active_bg',
            [
                'label'     => __('پس‌زمینه کارت فعال', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-card.is-active' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_active_text',
            [
                'label'     => __('رنگ متن کارت فعال', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-card.is-active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'media_heading',
            [
                'label'     => __('تصویر و آیکون', 'fondogh-online-menu'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'media_bg',
            [
                'label'     => __('پس‌زمینه دایره تصویر', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-media' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'media_color',
            [
                'label'     => __('رنگ آیکون دسته', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-media' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'label_heading',
            [
                'label'     => __('متن کارت', 'fondogh-online-menu'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'card_label_typography',
                'label'    => __('تایپوگرافی نام دسته', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-catsheet-label',
            ]
        );

        $this->add_control(
            'card_label_color',
            [
                'label'     => __('رنگ نام دسته', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_label_active_color',
            [
                'label'     => __('رنگ نام دسته فعال', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-card.is-active .rmm-catsheet-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'card_sub_typography',
                'label'    => __('تایپوگرافی نام زبان دوم', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-catsheet-sub',
            ]
        );

        $this->add_control(
            'card_sub_color',
            [
                'label'     => __('رنگ نام زبان دوم', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-catsheet-sub' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $active_lang    = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa';
        $secondary_lang = ($active_lang === 'fa') ? 'en' : 'fa';

        $categories = class_exists('\RMM\Menu\CategoryOrder')
            ? \RMM\Menu\CategoryOrder::get_ordered_categories(['hide_empty' => true])
            : get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => true,
            ]);

        if (empty($categories) || is_wp_error($categories)) {
            if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="rmm-empty-cat-notice" >' . esc_html__('دسته‌بندی تصویری: هیچ دسته‌بندی غذای فعالی در سیستم یافت نشد.', 'fondogh-online-menu') . '</div>';
            }
            return;
        }

        $uid             = 'rmm-catsheet-' . $this->get_id();
        $title_id        = $uid . '-title';
        $position        = !empty($settings['trigger_position']) ? $settings['trigger_position'] : 'inline';
        $trigger_label   = !empty($settings['trigger_text']) ? $settings['trigger_text'] : __('دسته‌بندی‌ها', 'fondogh-online-menu');
        $sheet_title     = !empty($settings['sheet_title']) ? $settings['sheet_title'] : __('دسته‌بندی‌ها', 'fondogh-online-menu');
        $show_title      = ($settings['show_sheet_title'] === 'yes');
        $show_secondary  = ($settings['show_secondary_label'] === 'yes');
        $close_on_select = ($settings['close_on_select'] === 'yes');
        $show_all        = ($settings['show_all_card'] === 'yes');

        $all_label = !empty($settings['all_label'])
            ? $settings['all_label']
            : (class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('all_categories', $active_lang) : '');
        if (empty($all_label)) {
            $all_label = __('همه دسته‌ها', 'fondogh-online-menu');
        }

        $all_label_secondary = '';
        if ($show_secondary && class_exists('\RMM\I18n\MultiLangManager')) {
            $all_label_secondary = \RMM\I18n\MultiLangManager::get_ui_string('all_categories', $secondary_lang);
        }

        $close_label = class_exists('\RMM\I18n\MultiLangManager')
            ? \RMM\I18n\MultiLangManager::get_ui_string('close', $active_lang)
            : '';
        if (empty($close_label)) {
            $close_label = __('بستن', 'fondogh-online-menu');
        }
        ?>
        <div class="rmm-catsheet-widget rmm-catsheet-widget--<?php echo esc_attr($position); ?>"
             data-catsheet-root
             data-catsheet-id="<?php echo esc_attr($uid); ?>"
             data-close-on-select="<?php echo $close_on_select ? '1' : '0'; ?>">

            <button type="button"
                    class="rmm-catsheet-trigger"
                    data-catsheet-open
                    aria-haspopup="dialog"
                    aria-expanded="false"
                    aria-controls="<?php echo esc_attr($uid); ?>">
                <?php if (!empty($settings['trigger_icon']['value'])) : ?>
                    <span class="rmm-catsheet-trigger-icon" aria-hidden="true">
                        <?php Icons_Manager::render_icon($settings['trigger_icon'], ['aria-hidden' => 'true']); ?>
                    </span>
                <?php endif; ?>
                <span class="rmm-catsheet-trigger-label"><?php echo esc_html($trigger_label); ?></span>
            </button>

            <div class="rmm-catsheet-overlay"
                 id="<?php echo esc_attr($uid); ?>"
                 aria-hidden="true"
                 hidden>
                <div class="rmm-catsheet-backdrop" data-catsheet-close aria-hidden="true"></div>

                <div class="rmm-catsheet-panel"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="<?php echo esc_attr($title_id); ?>">

                    <div class="rmm-catsheet-grabber" aria-hidden="true"></div>

                    <div class="rmm-catsheet-head">
                        <h3 class="rmm-catsheet-title<?php echo $show_title ? '' : ' rmm-catsheet-sr-only'; ?>" id="<?php echo esc_attr($title_id); ?>">
                            <?php echo esc_html($sheet_title); ?>
                        </h3>
                        <button type="button" class="rmm-catsheet-close" data-catsheet-close aria-label="<?php echo esc_attr($close_label); ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="rmm-catsheet-grid">
                        <?php if ($show_all) : ?>
                            <button type="button"
                                    class="rmm-catsheet-card is-active"
                                    data-cat-id="all"
                                    data-category="all"
                                    aria-pressed="true">
                                <span class="rmm-catsheet-media" aria-hidden="true">
                                    <?php
                                    if (!empty($settings['all_icon']['value'])) {
                                        Icons_Manager::render_icon($settings['all_icon'], ['aria-hidden' => 'true']);
                                    } else {
                                        echo '<i class="fas fa-utensils" aria-hidden="true"></i>';
                                    }
                                    ?>
                                </span>
                                <span class="rmm-catsheet-label"><?php echo esc_html($all_label); ?></span>
                                <?php if ($show_secondary && !empty($all_label_secondary)) : ?>
                                    <span class="rmm-catsheet-sub"><?php echo esc_html($all_label_secondary); ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endif; ?>

                        <?php foreach ($categories as $cat) :
                            $thumb_id  = get_term_meta($cat->term_id, 'thumbnail_id', true);
                            $image_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : '';
                            $t_name    = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_term_translation($cat->term_id, $active_lang) : '';
                            $title     = !empty($t_name) ? $t_name : $cat->name;

                            $secondary = '';
                            if ($show_secondary) {
                                if ($secondary_lang === 'fa') {
                                    $secondary = $cat->name;
                                } elseif (class_exists('\RMM\I18n\MultiLangManager')) {
                                    $secondary = \RMM\I18n\MultiLangManager::get_term_translation($cat->term_id, $secondary_lang);
                                }
                                if ($secondary === $title) {
                                    $secondary = '';
                                }
                            }
                            ?>
                            <button type="button"
                                    class="rmm-catsheet-card"
                                    data-cat-id="<?php echo esc_attr($cat->term_id); ?>"
                                    data-category="<?php echo esc_attr($cat->slug); ?>"
                                    aria-pressed="false">
                                <span class="rmm-catsheet-media" aria-hidden="true">
                                    <?php if ($image_url) : ?>
                                        <img src="<?php echo esc_url($image_url); ?>"
                                             alt=""
                                             loading="lazy">
                                    <?php elseif (!empty($settings['default_cat_icon']['value'])) : ?>
                                        <?php Icons_Manager::render_icon($settings['default_cat_icon'], ['aria-hidden' => 'true']); ?>
                                    <?php else : ?>
                                        <i class="fas fa-bowl-food" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="rmm-catsheet-label"><?php echo esc_html($title); ?></span>
                                <?php if ($secondary) : ?>
                                    <span class="rmm-catsheet-sub"><?php echo esc_html($secondary); ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="rmm-catsheet-handle" data-catsheet-close aria-label="<?php echo esc_attr($close_label); ?>"></button>
                </div>
            </div>
        </div>
        <?php
    }
}
