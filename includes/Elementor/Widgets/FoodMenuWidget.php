<?php
namespace RMM\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class FoodMenuWidget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'rmm_food_menu_grid';
    }

    public function get_title() {
        return __('شبکه و لیست منوی غذا (TastyMenu Grid)', 'fondogh-online-menu');
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
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

    private function get_elementor_templates_options() {
        $options = [
            '0' => __('— انتخاب قالب المنتور —', 'fondogh-online-menu'),
        ];
        $posts = get_posts([
            'post_type'      => 'elementor_library',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
        ]);
        if (!empty($posts)) {
            foreach ($posts as $post) {
                $options[$post->ID] = $post->post_title . ' (ID: ' . $post->ID . ')';
            }
        }
        return $options;
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_layout',
            [
                'label' => __('طراحی و چیدمان منو', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'max_products',
            [
                'label'       => __('حداکثر تعداد محصولات', 'fondogh-online-menu'),
                'type'        => \Elementor\Controls_Manager::NUMBER,
                'min'         => 10,
                'max'         => 500,
                'step'        => 10,
                'default'     => 200,
                'description' => __('سقف مجاز بارگذاری یکباره غذاها (برای منوهای خیلی بزرگ پیشنهاد می‌شود از ویجت Loop به همراه صفحه‌بندی استفاده فرمایید).', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'layout_style',
            [
                'label'   => __('سبک نمایش منو', 'fondogh-online-menu'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'modern_grid',
                'options' => [
                    'modern_grid' => __('شبکه مدرن (Modern Grid)', 'fondogh-online-menu'),
                    'oriental'    => __('تم شرقی (Oriental / Asian Luxury)', 'fondogh-online-menu'),
                    'app_list'    => __('لیستی اپلیکیشنی (اسنپ‌فودی / UberEats)', 'fondogh-online-menu'),
                    'fine_dining' => __('لوکس و کافه‌ای (Fine Dining / Minimal)', 'fondogh-online-menu'),
                    'compact_grid'=> __('کارت فشرده (Compact)', 'fondogh-online-menu'),
                ],
            ]
        );

        // Responsive Columns for Grid Mode
        $this->add_responsive_control(
            'columns',
            [
                'label'   => __('تعداد ستون‌های حالت شبکه‌ای (Grid)', 'fondogh-online-menu'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '۱ ستون',
                    '2' => '۲ ستون',
                    '3' => '۳ ستون',
                    '4' => '۴ ستون',
                    '5' => '۵ ستون',
                    '6' => '۶ ستون',
                ],
                'selectors' => [
                    '{{WRAPPER}} .rmm-menu-container.rmm-view-grid, {{WRAPPER}} .rmm-menu-container:not(.rmm-view-list)' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr)) !important;',
                ],
            ]
        );

        // Responsive Columns for List Mode
        $this->add_responsive_control(
            'list_columns',
            [
                'label'   => __('تعداد ستون‌های حالت لیستی (List)', 'fondogh-online-menu'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '2',
                'tablet_default' => '1',
                'mobile_default' => '1',
                'options' => [
                    '1' => '۱ ستون (تمام عرض)',
                    '2' => '۲ ستون (جفت کنار هم)',
                    '3' => '۳ ستون',
                    '4' => '۴ ستون',
                ],
                'selectors' => [
                    '{{WRAPPER}} .rmm-menu-container.rmm-view-list' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr)) !important;',
                ],
            ]
        );

        // Live Grid / List Toggle Switcher for Customers
        $this->add_control(
            'enable_view_switcher',
            [
                'label'        => __('دکمه تغییر زنده حالت شبکه / لیست برای مشتری', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'description'  => __('به مشتری اجازه می‌دهد بین نمای شبکه‌ای و نمای لیستی سریع جابجا شود.', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'default_view',
            [
                'label'   => __('نمای پیش‌فرض منو', 'fondogh-online-menu'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __('شبکه‌ای (Grid)', 'fondogh-online-menu'),
                    'list' => __('لیستی (List)', 'fondogh-online-menu'),
                ],
            ]
        );

        $this->add_control(
            'show_search',
            [
                'label'        => __('نمایش باکس جستجوی زنده غذا', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_badges',
            [
                'label'        => __('نمایش نشان‌های رژیمی (تند، گیاهی و...)', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_calories',
            [
                'label'        => __('نمایش کالری و زمان آماده‌سازی', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_ingredients',
            [
                'label'        => __('نمایش ترکیبات و مواد اولیه در کارت', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'desc_line_clamp',
            [
                'label'   => __('تعداد خطوط توضیحات غذا', 'fondogh-online-menu'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '1',
                'options' => [
                    '1'    => __('۱ خط (خلاصه)', 'fondogh-online-menu'),
                    '2'    => __('۲ خط', 'fondogh-online-menu'),
                    'full' => __('نمایش کامل', 'fondogh-online-menu'),
                ],
            ]
        );

        $this->add_control(
            'hide_out_of_stock',
            [
                'label'        => __('مخفی‌سازی غذاهای ناموجود', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'description'  => __('در صورت غیرفعال بودن موجودی غذا، کارت آن از منوی مشتریان حذف می‌شود.', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'show_category_headers',
            [
                'label'        => __('نمایش عناوین دسته‌بندی‌ها به عنوان جداکننده', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'description'  => __('در ابتدای هر بخش از منو، عنوان و نشان دسته‌بندی با اولویت تنظیم‌شده در مدیریت نمایش داده می‌شود.', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'show_cat_count',
            [
                'label'        => __('نمایش تعداد غذاها در عنوان دسته', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'condition'    => [
                    'show_category_headers' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_cat_thumb',
            [
                'label'        => __('نمایش تصویر دسته‌بندی در کنار عنوان', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'condition'    => [
                    'show_category_headers' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_cat_desc',
            [
                'label'        => __('نمایش توضیحات دسته‌بندی', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'condition'    => [
                    'show_category_headers' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_theme_toggle',
            [
                'label'        => __('دکمه تغییر تم (روز / شب)', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'no',
                'return_value' => 'yes',
                'description'  => __('دکمه شناور تغییر حالت تیره و روشن را برای کل منو فعال می‌کند (می‌توانید از ویجت اختصاصی سوئیچر روز و شب نیز استفاده کنید).', 'fondogh-online-menu'),
            ]
        );

        $this->end_controls_section();

        // Section: Card Click Action
        $this->start_controls_section(
            'section_card_click',
            [
                'label' => __('رفتار کلیک روی کارت غذا (پاپ‌آپ جزئیات)', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'card_click_action',
            [
                'label'   => __('عملیات با لمس / کلیک روی کارت', 'fondogh-online-menu'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'default_modal',
                'options' => [
                    'default_modal'      => __('✨ باز شدن پاپ‌آپ هوشمند فندق (استایل پیش‌فرض و کامل)', 'fondogh-online-menu'),
                    'elementor_template' => __('🎨 بارگذاری قالب ذخیره‌شده المنتور (قالب اختصاصی)', 'fondogh-online-menu'),
                    'elementor_popup'    => __('🚀 باز شدن پاپ‌آپ المنتور پرو (Elementor Pro Popup)', 'fondogh-online-menu'),
                    'none'               => __('غیرفعال (فقط دکمه خرید داخل کارت کار کند)', 'fondogh-online-menu'),
                ],
            ]
        );

        $this->add_control(
            'elementor_template_id',
            [
                'label'     => __('انتخاب قالب المنتور برای پاپ‌آپ', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => '0',
                'options'   => $this->get_elementor_templates_options(),
                'condition' => [
                    'card_click_action' => 'elementor_template',
                ],
                'description' => __('می‌توانید هر قالبی را در قالب‌های ذخیره‌شده المنتور طراحی کنید؛ اطلاعات غذا (تصویر، عنوان، قیمت، کالری، ترکیبات) با تگ‌های داینامیک فندق به صورت زنده در آن لود می‌شوند.', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'elementor_popup_id',
            [
                'label'       => __('شناسه پاپ‌آپ المنتور پرو (Popup ID)', 'fondogh-online-menu'),
                'type'        => \Elementor\Controls_Manager::TEXT,
                'placeholder' => '1234',
                'condition'   => [
                    'card_click_action' => 'elementor_popup',
                ],
                'description' => __('شناسه پاپ‌آپ المنتور پرو خود را اینجا وارد کنید.', 'fondogh-online-menu'),
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE TAB ==================== //

        // 1. Search Bar Style
        $this->start_controls_section(
            'section_style_search',
            [
                'label' => __('استایل باکس جستجو', 'fondogh-online-menu'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_search' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'search_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه جستجو', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-menu-search-field' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'search_text_color',
            [
                'label'     => __('رنگ متن جستجو', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-menu-search-field' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'search_icon_color',
            [
                'label'     => __('رنگ آیکون ذره‌بین', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-search-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'search_typography',
                'label'    => __('تایپوگرافی متن جستجو', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'search_border',
                'selector' => '{{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->add_control(
            'search_border_radius',
            [
                'label'      => __('گردی کادر جستجو (Border Radius)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-menu-search-field' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'search_box_shadow',
                'selector' => '{{WRAPPER}} .rmm-menu-search-field',
            ]
        );

        $this->end_controls_section();

        // 2. Food Card Style
        $this->start_controls_section(
            'section_style_cards',
            [
                'label' => __('استایل کارت غذاها', 'fondogh-online-menu'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه کارت', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-food-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_bg_hover_color',
            [
                'label'     => __('رنگ پس‌زمینه در هاور', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-food-card:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .rmm-food-card',
            ]
        );

        $this->add_responsive_control(
            'card_border_radius',
            [
                'label'      => __('انحنای لبه کارت (Border Radius)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-food-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_box_shadow',
                'selector' => '{{WRAPPER}} .rmm-food-card',
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label'      => __('فاصله داخلی کارت (Padding)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-food-card .rmm-card-details' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'list_card_padding',
            [
                'label'      => __('فاصله داخلی کارت در حالت لیست (Padding)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-view-list .rmm-food-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'cards_gap',
            [
                'label'      => __('فاصله بین کارت‌ها (Grid Gap)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 60,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-menu-container' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Card Image Controls
        $this->add_control(
            'card_image_heading',
            [
                'label'     => __('تصویر کارت غذا', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'card_image_height',
            [
                'label'      => __('ارتفاع تصویر (حالت شبکه)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range'      => [
                    'px' => [
                        'min' => 100,
                        'max' => 450,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-view-grid .rmm-card-image-wrap, {{WRAPPER}} .rmm-menu-container:not(.rmm-view-list) .rmm-card-image-wrap' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'list_image_width',
            [
                'label'      => __('عرض تصویر (حالت لیست)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 50,
                        'max' => 280,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-view-list .rmm-card-image-wrap' => 'width: {{SIZE}}{{UNIT}}; min-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'list_image_height',
            [
                'label'      => __('ارتفاع تصویر (حالت لیست)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 50,
                        'max' => 280,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-view-list .rmm-card-image-wrap' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_image_border_radius',
            [
                'label'      => __('گردی کادر تصویر', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-card-image-wrap, {{WRAPPER}} .rmm-card-img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 3. Typography & Colors (Title, Price, Ingredients)
        $this->start_controls_section(
            'section_style_typography',
            [
                'label' => __('تایپوگرافی و رنگ‌های متن', 'fondogh-online-menu'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Title
        $this->add_control(
            'title_heading',
            [
                'label'     => __('عنوان غذا', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __('رنگ عنوان غذا', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-food-card .rmm-food-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'label'    => __('تایپوگرافی عنوان', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-food-card .rmm-food-title',
            ]
        );

        // Price
        $this->add_control(
            'price_heading',
            [
                'label'     => __('قیمت غذا', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label'     => __('رنگ قیمت', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-food-card .rmm-food-price, {{WRAPPER}} .rmm-food-card .rmm-food-price .woocommerce-Price-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'price_typography',
                'label'    => __('تایپوگرافی قیمت', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-food-card .rmm-food-price',
            ]
        );

        // Ingredients
        $this->add_control(
            'ingredients_heading',
            [
                'label'     => __('ترکیبات و توضیحات', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'ingredients_color',
            [
                'label'     => __('رنگ متن ترکیبات و توضیحات', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-food-card .rmm-food-ingredients, {{WRAPPER}} .rmm-food-card .rmm-food-desc' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'ingredients_typography',
                'label'    => __('تایپوگرافی ترکیبات و توضیحات', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-food-card .rmm-food-ingredients, {{WRAPPER}} .rmm-food-card .rmm-food-desc',
            ]
        );

        // Meta (Calories & Time)
        $this->add_control(
            'meta_heading',
            [
                'label'     => __('کالری و زمان پخت', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'meta_color',
            [
                'label'     => __('رنگ اطلاعات کالری و زمان', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-food-meta-row' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'meta_typography',
                'label'    => __('تایپوگرافی کالری و زمان', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-food-meta-row',
            ]
        );

        $this->end_controls_section();

        // 4. Badges Style
        $this->start_controls_section(
            'section_style_badges',
            [
                'label' => __('استایل نشان‌های رژیمی', 'fondogh-online-menu'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_badges' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'badge_typography',
                'label'    => __('تایپوگرافی نشان‌ها', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-card-badge',
            ]
        );

        $this->add_control(
            'badge_border_radius',
            [
                'label'      => __('انحنای لبه نشان‌ها', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-card-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'badge_padding',
            [
                'label'      => __('فاصله داخلی نشان‌ها', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-card-badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'badge_box_shadow',
            [
                'label'     => __('سایه نشان‌ها', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::SWITCHER,
                'default'   => 'yes',
                'selectors' => [
                    '{{WRAPPER}} .rmm-card-badge' => 'box-shadow: 0 2px 8px rgba(0,0,0,0.18);',
                ],
            ]
        );

        // Out of Stock Badge
        $this->add_control(
            'out_of_stock_heading',
            [
                'label'     => __('نشان «ناموجود»', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'out_of_stock_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه ناموجود', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-out-badge' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'out_of_stock_text_color',
            [
                'label'     => __('رنگ متن ناموجود', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-out-badge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'out_of_stock_radius',
            [
                'label'      => __('گردی کادر ناموجود', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-out-badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 5. Action Buttons Style
        $this->start_controls_section(
            'section_style_button',
            [
                'label' => __('استایل دکمه خرید و سفارش', 'fondogh-online-menu'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'btn_typography',
                'label'    => __('تایپوگرافی دکمه', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-btn-order',
            ]
        );

        $this->start_controls_tabs('tabs_button_style');

        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => __('عادی', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'btn_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه دکمه', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-btn-order' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'btn_text_color',
            [
                'label'     => __('رنگ متن دکمه', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-btn-order' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => __('هاور (Hover)', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'btn_bg_hover_color',
            [
                'label'     => __('رنگ پس‌زمینه در هاور', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-btn-order:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'btn_text_hover_color',
            [
                'label'     => __('رنگ متن در هاور', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-btn-order:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'btn_border_radius',
            [
                'label'      => __('گردی لبه دکمه (Border Radius)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'separator'  => 'before',
                'selectors'  => [
                    '{{WRAPPER}} .rmm-btn-order' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'btn_padding',
            [
                'label'      => __('فاصله داخلی دکمه (Padding)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-btn-order' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'btn_box_shadow',
                'selector' => '{{WRAPPER}} .rmm-btn-order',
            ]
        );

        $this->end_controls_section();

        // 6. Food Details Modal Style
        $this->start_controls_section(
            'section_style_modal',
            [
                'label' => __('استایل پاپ‌آپ جزئیات غذا (Food Modal Style)', 'fondogh-online-menu'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'modal_backdrop_bg',
            [
                'label'     => __('رنگ پرده پس‌زمینه (Backdrop)', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-backdrop' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_box_bg',
            [
                'label'     => __('رنگ پس‌زمینه پنجره پاپ‌آپ', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-box' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'modal_box_border',
                'label'    => __('حاشیه کادر پاپ‌آپ', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-modal-box',
            ]
        );

        $this->add_control(
            'modal_box_radius',
            [
                'label'      => __('گردی لبه پنجره (Border Radius)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-modal-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'modal_box_padding',
            [
                'label'      => __('فاصله داخلی کادر پاپ‌آپ (Padding)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-modal-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'modal_box_shadow',
                'selector' => '{{WRAPPER}} .rmm-modal-box',
            ]
        );

        // Modal Icons & Close Button
        $this->add_control(
            'modal_icons_heading',
            [
                'label'     => __('آیکون‌ها و دکمه بستن پاپ‌آپ', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'modal_close_bg',
            [
                'label'     => __('پس‌زمینه دکمه بستن', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-close-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_close_color',
            [
                'label'     => __('رنگ آیکون بستن', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-close-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_close_size',
            [
                'label'      => __('اندازه آیکون بستن', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 14, 'max' => 42]],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-modal-close-btn' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'modal_chip_bg',
            [
                'label'     => __('پس‌زمینه برچسب‌ها و چیپ‌ها (کالری/زمان)', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-chip' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_chip_color',
            [
                'label'     => __('رنگ متن و آیکون چیپ‌ها', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-chip' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'modal_chip_typography',
                'label'    => __('تایپوگرافی چیپ‌ها', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-modal-chip',
            ]
        );

        $this->add_control(
            'modal_title_heading',
            [
                'label'     => __('عنوان غذا در پاپ‌آپ', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'modal_title_color',
            [
                'label'     => __('رنگ عنوان غذا', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-food-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'modal_title_typography',
                'selector' => '{{WRAPPER}} .rmm-modal-food-title',
            ]
        );

        $this->add_control(
            'modal_price_heading',
            [
                'label'     => __('قیمت در پاپ‌آپ', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'modal_price_color',
            [
                'label'     => __('رنگ قیمت', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-food-price, {{WRAPPER}} .rmm-modal-sale-price, {{WRAPPER}} .rmm-modal-regular-price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'modal_price_typography',
                'selector' => '{{WRAPPER}} .rmm-modal-food-price',
            ]
        );

        $this->add_control(
            'modal_desc_heading',
            [
                'label'     => __('توضیحات و ترکیبات در پاپ‌آپ', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'modal_desc_color',
            [
                'label'     => __('رنگ متن توضیحات و ترکیبات', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-desc-text, {{WRAPPER}} .rmm-modal-ingredients-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'modal_desc_typography',
                'selector' => '{{WRAPPER}} .rmm-modal-desc-text, {{WRAPPER}} .rmm-modal-ingredients-text',
            ]
        );

        // Section Boxes (Portions, Addons, Note)
        $this->add_control(
            'modal_sections_heading',
            [
                'label'     => __('کادرهای بخش سایز، مخلفات و یادداشت', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'modal_section_bg',
            [
                'label'     => __('پس‌زمینه کادر بخش‌ها', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-section-box' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'modal_section_border',
                'label'    => __('حاشیه کادر بخش‌ها', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-modal-section-box',
            ]
        );

        $this->add_control(
            'modal_section_radius',
            [
                'label'      => __('گردی کادر بخش‌ها', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-modal-section-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'modal_section_title_color',
            [
                'label'     => __('رنگ عنوان بخش‌ها', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-section-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'modal_section_title_typography',
                'label'    => __('تایپوگرافی عنوان و گزینه‌های بخش', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-modal-section-title, {{WRAPPER}} .rmm-modal-portion-item, {{WRAPPER}} .rmm-modal-addon-item',
            ]
        );

        $this->add_control(
            'modal_note_bg',
            [
                'label'     => __('پس‌زمینه فیلد یادداشت آشپزخانه', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-kitchen-note input' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_note_color',
            [
                'label'     => __('رنگ متن یادداشت آشپزخانه', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-kitchen-note input' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Quantity +/- Controls
        $this->add_control(
            'modal_qty_heading',
            [
                'label'     => __('دکمه‌های کم و زیاد تعداد (+ / −)', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'modal_qty_wrap_bg',
            [
                'label'     => __('پس‌زمینه کادر تعداد', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-qty-controls' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_qty_btn_bg',
            [
                'label'     => __('رنگ پس‌زمینه دکمه‌های + و −', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-qty-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_qty_btn_color',
            [
                'label'     => __('رنگ آیکون/متن دکمه‌های + و −', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-qty-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_qty_btn_size',
            [
                'label'      => __('اندازه کادر دکمه‌های + و −', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 22, 'max' => 54]],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-qty-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'modal_qty_btn_font_size',
            [
                'label'      => __('اندازه فونت آیکون + و −', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 12, 'max' => 30]],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-qty-btn' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'modal_qty_btn_radius',
            [
                'label'      => __('گردی دکمه‌های + و −', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-qty-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'modal_qty_val_color',
            [
                'label'     => __('رنگ عدد تعداد', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-qty-val' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'modal_qty_val_typography',
                'label'    => __('فونت و اندازه عدد تعداد', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-qty-val',
            ]
        );

        // Submit Button inside Modal
        $this->add_control(
            'modal_submit_heading',
            [
                'label'     => __('دکمه ثبت / افزودن به سبد داخل پاپ‌آپ', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'modal_submit_typography',
                'label'    => __('فونت و اندازه فونت دکمه', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-modal-btn-submit',
            ]
        );

        $this->add_control(
            'modal_submit_bg',
            [
                'label'     => __('رنگ پس‌زمینه دکمه', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-btn-submit' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_submit_color',
            [
                'label'     => __('رنگ متن دکمه', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-btn-submit' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_submit_bg_hover',
            [
                'label'     => __('پس‌زمینه دکمه در هاور', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-modal-btn-submit:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'modal_submit_radius',
            [
                'label'      => __('گردی لبه دکمه', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-modal-btn-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'modal_submit_padding',
            [
                'label'      => __('فاصله داخلی دکمه (Padding)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-modal-btn-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 6b. Catalog Selection List Popup Style (لیست انتخاب‌های من)
        $this->start_controls_section(
            'section_style_catalog_modal',
            [
                'label' => __('استایل پاپ‌آپ لیست انتخاب‌های من (Catalog Popup)', 'fondogh-online-menu'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'catalog_backdrop_bg',
            [
                'label'     => __('رنگ پرده پس‌زمینه (Backdrop)', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_box_bg',
            [
                'label'     => __('پس‌زمینه کادر پاپ‌آپ', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-drawer' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'catalog_box_border',
                'label'    => __('حاشیه کادر پاپ‌آپ', 'fondogh-online-menu'),
                'selector' => '.rmm-catalog-drawer-backdrop .rmm-catalog-drawer',
            ]
        );

        $this->add_control(
            'catalog_box_radius',
            [
                'label'      => __('گردی لبه پاپ‌آپ', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-drawer' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'catalog_box_shadow',
                'selector' => '.rmm-catalog-drawer-backdrop .rmm-catalog-drawer',
            ]
        );

        // Header & Icons
        $this->add_control(
            'catalog_header_heading',
            [
                'label'     => __('هدر، عنوان و آیکون بستن', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'catalog_title_color',
            [
                'label'     => __('رنگ عنوان لیست', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-drawer-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'catalog_title_typography',
                'label'    => __('فونت و اندازه عنوان', 'fondogh-online-menu'),
                'selector' => '.rmm-catalog-drawer-backdrop .rmm-catalog-drawer-title',
            ]
        );

        $this->add_control(
            'catalog_close_bg',
            [
                'label'     => __('پس‌زمینه آیکون بستن', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-close-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_close_color',
            [
                'label'     => __('رنگ آیکون بستن', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-close-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_close_size',
            [
                'label'      => __('اندازه آیکون بستن', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 14, 'max' => 40]],
                'selectors'  => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-close-btn' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Item Cards
        $this->add_control(
            'catalog_items_heading',
            [
                'label'     => __('کارت‌های غذا داخل لیست', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'catalog_item_bg',
            [
                'label'     => __('پس‌زمینه کارت آیتم', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-item-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'catalog_item_border',
                'label'    => __('حاشیه کارت آیتم', 'fondogh-online-menu'),
                'selector' => '.rmm-catalog-drawer-backdrop .rmm-catalog-item-card',
            ]
        );

        $this->add_control(
            'catalog_item_radius',
            [
                'label'      => __('گردی کارت آیتم', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-item-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_item_title_color',
            [
                'label'     => __('رنگ عنوان آیتم', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-item-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'catalog_item_title_typography',
                'label'    => __('فونت و اندازه عنوان آیتم', 'fondogh-online-menu'),
                'selector' => '.rmm-catalog-drawer-backdrop .rmm-catalog-item-title',
            ]
        );

        $this->add_control(
            'catalog_item_price_color',
            [
                'label'     => __('رنگ مبلغ آیتم', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-sub-val' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Quantity +/- Buttons in Catalog
        $this->add_control(
            'catalog_qty_heading',
            [
                'label'     => __('دکمه‌های کم و زیاد تعداد (+ / −)', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'catalog_qty_wrap_bg',
            [
                'label'     => __('پس‌زمینه کادر تعداد', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-item-qty' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_qty_btn_bg',
            [
                'label'     => __('پس‌زمینه دکمه‌های + و −', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-cat-qty-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_qty_btn_color',
            [
                'label'     => __('رنگ آیکون/متن دکمه‌های + و −', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-cat-qty-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_qty_btn_size',
            [
                'label'      => __('اندازه کادر دکمه‌های + و −', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 20, 'max' => 48]],
                'selectors'  => [
                    '.rmm-catalog-drawer-backdrop .rmm-cat-qty-btn' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_qty_btn_font_size',
            [
                'label'      => __('اندازه فونت آیکون + و −', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 12, 'max' => 28]],
                'selectors'  => [
                    '.rmm-catalog-drawer-backdrop .rmm-cat-qty-btn' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_qty_btn_radius',
            [
                'label'      => __('گردی دکمه‌های + و −', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '.rmm-catalog-drawer-backdrop .rmm-cat-qty-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_qty_num_color',
            [
                'label'     => __('رنگ عدد تعداد', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-cat-qty-num' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'catalog_qty_num_typography',
                'label'    => __('فونت و اندازه عدد تعداد', 'fondogh-online-menu'),
                'selector' => '.rmm-catalog-drawer-backdrop .rmm-cat-qty-num',
            ]
        );

        // Footer & Action Buttons
        $this->add_control(
            'catalog_footer_heading',
            [
                'label'     => __('فوتر، جمع کل و دکمه‌ها', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'catalog_total_box_bg',
            [
                'label'     => __('پس‌زمینه کادر جمع کل', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-grand-total-row' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_total_color',
            [
                'label'     => __('رنگ مبلغ جمع کل', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-grand-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'catalog_total_typography',
                'label'    => __('فونت و اندازه جمع کل', 'fondogh-online-menu'),
                'selector' => '.rmm-catalog-drawer-backdrop .rmm-catalog-grand-label, .rmm-catalog-drawer-backdrop .rmm-catalog-grand-amount',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'catalog_btn_typography',
                'label'    => __('فونت و اندازه فونت دکمه‌ها', 'fondogh-online-menu'),
                'selector' => '.rmm-catalog-drawer-backdrop .rmm-catalog-wa-btn, .rmm-catalog-drawer-backdrop .rmm-catalog-done-btn, .rmm-catalog-drawer-backdrop .rmm-catalog-clear-btn',
            ]
        );

        $this->add_control(
            'catalog_wa_btn_bg',
            [
                'label'     => __('رنگ دکمه واتساپ', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-wa-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_wa_btn_color',
            [
                'label'     => __('رنگ متن دکمه واتساپ', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-wa-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_done_btn_bg',
            [
                'label'     => __('رنگ دکمه ادامه مشاهده منو', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-done-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_done_btn_color',
            [
                'label'     => __('رنگ متن دکمه ادامه', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-done-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_btn_radius',
            [
                'label'      => __('گردی لبه دکمه‌ها', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-wa-btn, .rmm-catalog-drawer-backdrop .rmm-catalog-done-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'catalog_btn_padding',
            [
                'label'      => __('فاصله داخلی دکمه‌ها (Padding)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '.rmm-catalog-drawer-backdrop .rmm-catalog-wa-btn, .rmm-catalog-drawer-backdrop .rmm-catalog-done-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 7. Category Section Headers Style
        $this->start_controls_section(
            'section_style_cat_headers',
            [
                'label'     => __('استایل عناوین دسته‌بندی‌ها (Category Headers)', 'fondogh-online-menu'),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_category_headers' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'cat_title_color',
            [
                'label'     => __('رنگ عنوان دسته‌بندی', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-category-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'cat_title_typography',
                'label'    => __('تایپوگرافی عنوان دسته‌بندی', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-category-title',
            ]
        );

        $this->add_control(
            'cat_badge_bg',
            [
                'label'     => __('رنگ پس‌زمینه نشان تعداد', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-cat-count-badge' => 'background: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'cat_badge_color',
            [
                'label'     => __('رنگ متن نشان تعداد', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-cat-count-badge' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'cat_divider_color',
            [
                'label'     => __('رنگ خط جداکننده دسته‌بندی', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-cat-divider-line' => 'background: {{VALUE}} !important;',
                    '{{WRAPPER}} .rmm-cat-divider-ornament' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'cat_section_spacing',
            [
                'label'      => __('فاصله بالای هر دسته‌بندی', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 10,
                        'max' => 80,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-category-section' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $style = $settings['layout_style'] ?? 'modern_grid';
        $columns = $settings['columns'] ?? '3';

        $active_branch_id = \RMM\Branches\BranchContext::get_active_branch_id();
        $branch_mode = \RMM\Branches\BranchContext::get_branch_mode($active_branch_id);
        $is_view_only = ($branch_mode === 'view_only');

        $card_action = $settings['card_click_action'] ?? 'default_modal';
        $template_id = $settings['elementor_template_id'] ?? 0;
        $popup_id    = $settings['elementor_popup_id'] ?? '';

        // Multilingual active strings
        $active_lang = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa';
        $search_placeholder = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('search_placeholder', $active_lang) : __('جستجو در منو (مثلاً پیتزا، برگر، سالاد...)', 'fondogh-online-menu');
        $str_minutes = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('minutes', $active_lang) : __('دقیقه', 'fondogh-online-menu');
        $str_out_of_stock = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('out_of_stock', $active_lang) : __('ناموجود در این شعبه', 'fondogh-online-menu');
        $str_order_wa = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('order_whatsapp', $active_lang) : __('سفارش در واتساپ', 'fondogh-online-menu');
        $str_call = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('call_order', $active_lang) : __('تماس تلفنی', 'fondogh-online-menu');
        $str_options = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('choose_options', $active_lang) : __('انتخاب مخلفات', 'fondogh-online-menu');
        $str_add = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('add_to_cart', $active_lang) : __('افزودن به سبد', 'fondogh-online-menu');
        $str_digital = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('digital_menu', $active_lang) : __('منو دیجیتال', 'fondogh-online-menu');
        $str_grid_view = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('grid_view', $active_lang) : __('نمایش شبکه‌ای', 'fondogh-online-menu');
        $str_list_view = class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('list_view', $active_lang) : __('نمایش لیستی', 'fondogh-online-menu');

        $enable_switcher = ($settings['enable_view_switcher'] ?? 'yes') === 'yes';
        $default_view = $settings['default_view'] ?? ($style === 'app_list' ? 'list' : 'grid');



        $max_items = absint($settings['max_products'] ?? 200) ?: 200;

        $args = [
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'posts_per_page'         => $max_items,
            'orderby'                => 'menu_order',
            'order'                  => 'ASC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        ];

        $query = new \WP_Query($args);
        if (!$query->have_posts()) {
            echo '<p class="rmm-no-foods">' . esc_html__('هیچ غذایی در این منو یافت نشد.', 'fondogh-online-menu') . '</p>';
            return;
        }

        $view_class = ($default_view === 'list') ? 'rmm-view-list' : 'rmm-view-grid';
        $desc_lines = $settings['desc_line_clamp'] ?? '1';
        $desc_class = ($desc_lines === 'full') ? 'rmm-desc-lines-full' : (($desc_lines === '2') ? 'rmm-desc-lines-2' : 'rmm-desc-lines-1');
        $grid_class = 'rmm-menu-container rmm-style-' . esc_attr($style) . ' ' . $view_class . ' rmm-cols-' . esc_attr($columns) . ' ' . $desc_class;
        $wrapper_class = 'rmm-menu-widget-wrapper' . ($style === 'oriental' ? ' rmm-theme-oriental' : '');

        $context_data = [
            'is_view_only'      => $is_view_only,
            'card_action'       => $card_action,
            'template_id'       => $template_id,
            'popup_id'          => $popup_id,
            'active_lang'       => $active_lang,
            'show_ingredients'  => ($settings['show_ingredients'] ?? 'yes') === 'yes',
            'str_digital'       => $str_digital,
            'str_out_of_stock'  => $str_out_of_stock,
            'str_order_wa'      => $str_order_wa,
            'str_call'          => $str_call,
            'str_options'       => $str_options,
            'str_add'           => $str_add,
            'str_minutes'       => $str_minutes,
        ];

        // Group products by category and filter stock
        $hide_out_of_stock = ($settings['hide_out_of_stock'] ?? 'yes') === 'yes';
        $products_by_cat = [];
        $uncategorized_products = [];
        $all_active_products = [];
        $branch_id = class_exists('\RMM\Branches\BranchContext') ? \RMM\Branches\BranchContext::get_active_branch_id() : 0;

        // Bulk prefetch branch inventory for all query products to eliminate N+1 queries
        if ($branch_id && class_exists('\RMM\Branches\InventoryRepository') && !empty($query->posts)) {
            $product_ids = wp_list_pluck($query->posts, 'ID');
            \RMM\Branches\InventoryRepository::get_bulk_branch_inventory($branch_id, $product_ids);
        }

        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            if (!$product) continue;

            $p_id = $product->get_id();
            $in_stock = $product->is_in_stock();

            if ($branch_id && class_exists('\RMM\Branches\InventoryRepository')) {
                $inv_item = \RMM\Branches\InventoryRepository::get_item($p_id, $branch_id);
                if ($inv_item && (!$inv_item->is_available || $inv_item->stock_status === 'outofstock')) {
                    $in_stock = false;
                }
            }

            if ($hide_out_of_stock && !$in_stock) {
                continue;
            }

            $all_active_products[] = $product;
            $cat_ids = wp_get_post_terms($p_id, 'product_cat', ['fields' => 'ids']);
            if (empty($cat_ids)) {
                $uncategorized_products[] = $product;
            } else {
                foreach ($cat_ids as $c_id) {
                    $products_by_cat[$c_id][] = $product;
                }
            }
        }
        wp_reset_postdata();

        if (empty($all_active_products)) {
            echo '<p class="rmm-no-foods">' . esc_html__('هیچ غذای موجودی در این منو یافت نشد.', 'fondogh-online-menu') . '</p>';
            return;
        }

        $show_category_headers = ($settings['show_category_headers'] ?? 'yes') === 'yes';
        $ordered_categories = class_exists('\RMM\Menu\CategoryOrder')
            ? \RMM\Menu\CategoryOrder::get_ordered_categories(['hide_empty' => true])
            : get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
        ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>">
            
            <div class="rmm-top-controls-bar">
                <?php if ($settings['show_search'] === 'yes') : ?>
                    <div class="rmm-live-search-bar">
                        <span class="rmm-search-icon">🔍</span>
                        <input type="text" class="rmm-menu-search-field" placeholder="<?php echo esc_attr($search_placeholder); ?>">
                    </div>
                <?php endif; ?>

                <?php if ($enable_switcher) : ?>
                    <div class="rmm-view-switcher" role="group" aria-label="<?php esc_attr_e('حالت نمایش منو', 'fondogh-online-menu'); ?>">
                        <button type="button" class="rmm-view-btn rmm-btn-grid <?php echo ($default_view === 'grid') ? 'is-active' : ''; ?>" data-view="grid" aria-pressed="<?php echo ($default_view === 'grid') ? 'true' : 'false'; ?>" title="<?php echo esc_attr($str_grid_view); ?>">
                            <span class="rmm-view-icon">⊞</span>
                            <span class="rmm-view-text"><?php echo esc_html($str_grid_view); ?></span>
                        </button>
                        <button type="button" class="rmm-view-btn rmm-btn-list <?php echo ($default_view === 'list') ? 'is-active' : ''; ?>" data-view="list" aria-pressed="<?php echo ($default_view === 'list') ? 'true' : 'false'; ?>" title="<?php echo esc_attr($str_list_view); ?>">
                            <span class="rmm-view-icon">☰</span>
                            <span class="rmm-view-text"><?php echo esc_html($str_list_view); ?></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($show_category_headers && !empty($ordered_categories)) : ?>
                <!-- نمای دسته‌بندی‌شده همراه با عنوان و جداکننده هر دسته -->
                <div class="rmm-menu-catalog-wrap" data-default-view="<?php echo esc_attr($default_view); ?>">
                    <?php foreach ($ordered_categories as $cat) : 
                        $c_id = $cat->term_id;
                        if (empty($products_by_cat[$c_id])) continue;
                        $cat_foods = $products_by_cat[$c_id];

                        $cat_name = $cat->name;
                        if (class_exists('\RMM\I18n\MultiLangManager') && $active_lang !== 'fa') {
                            $t_name = \RMM\I18n\MultiLangManager::get_term_translation($c_id, $active_lang);
                            if (!empty($t_name)) {
                                $cat_name = $t_name;
                            }
                        }

                        $thumb_id = get_term_meta($c_id, 'thumbnail_id', true);
                        $cat_img = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : '';
                    ?>
                        <section class="rmm-category-section" id="rmm-cat-sec-<?php echo esc_attr($c_id); ?>" data-cat-id="<?php echo esc_attr($c_id); ?>">
                            <header class="rmm-category-header">
                                <div class="rmm-cat-header-main">
                                    <?php if ($settings['show_cat_thumb'] === 'yes' && $cat_img) : ?>
                                        <div class="rmm-cat-header-thumb-wrap">
                                            <img src="<?php echo esc_url($cat_img); ?>" class="rmm-cat-header-thumb" alt="<?php echo esc_attr($cat_name); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="rmm-cat-header-info">
                                        <div class="rmm-cat-title-row">
                                            <h2 class="rmm-category-title"><?php echo esc_html($cat_name); ?></h2>
                                            <?php if ($settings['show_cat_count'] === 'yes') : ?>
                                                <span class="rmm-cat-count-badge"><?php echo count($cat_foods); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($settings['show_cat_desc'] === 'yes' && !empty($cat->description)) : ?>
                                            <p class="rmm-category-desc"><?php echo esc_html($cat->description); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="rmm-cat-header-divider">
                                    <span class="rmm-cat-divider-line"></span>
                                    <span class="rmm-cat-divider-ornament"><?php echo ($style === 'oriental') ? '🏮' : '◆'; ?></span>
                                    <span class="rmm-cat-divider-line"></span>
                                </div>
                            </header>

                            <div class="<?php echo esc_attr($grid_class); ?>" data-default-view="<?php echo esc_attr($default_view); ?>">
                                <?php foreach ($cat_foods as $prod) : ?>
                                    <?php $this->render_food_card($prod, $settings, $context_data); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>

                    <?php if (!empty($uncategorized_products)) : ?>
                        <section class="rmm-category-section rmm-uncategorized-section" id="rmm-cat-sec-uncategorized" data-cat-id="uncategorized">
                            <header class="rmm-category-header">
                                <div class="rmm-cat-header-main">
                                    <div class="rmm-cat-header-info">
                                        <div class="rmm-cat-title-row">
                                            <h2 class="rmm-category-title"><?php esc_html_e('سایر غذاها و موارد متفرقه', 'fondogh-online-menu'); ?></h2>
                                            <?php if ($settings['show_cat_count'] === 'yes') : ?>
                                                <span class="rmm-cat-count-badge"><?php echo count($uncategorized_products); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="rmm-cat-header-divider">
                                    <span class="rmm-cat-divider-line"></span>
                                    <span class="rmm-cat-divider-ornament">◆</span>
                                    <span class="rmm-cat-divider-line"></span>
                                </div>
                            </header>

                            <div class="<?php echo esc_attr($grid_class); ?>" data-default-view="<?php echo esc_attr($default_view); ?>">
                                <?php foreach ($uncategorized_products as $prod) : ?>
                                    <?php $this->render_food_card($prod, $settings, $context_data); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

            <?php else : ?>

                <!-- حالت یکپارچه بدون سرفصل دسته‌بندی -->
                <div class="<?php echo esc_attr($grid_class); ?>" data-default-view="<?php echo esc_attr($default_view); ?>">
                    <?php foreach ($all_active_products as $prod) : ?>
                        <?php $this->render_food_card($prod, $settings, $context_data); ?>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

            <!-- MODIFIERS POPUP MODAL (Single Reusable Modal) -->
            <div id="rmm-modifiers-modal" class="rmm-modal-backdrop" style="display:none;">
                <div class="rmm-modal-box">
                    <button class="rmm-modal-close-btn">&times;</button>
                    <div id="rmm-modal-content">
                        <!-- Loaded via AJAX -->
                    </div>
                </div>
            </div>

            <?php if (($settings['show_theme_toggle'] ?? 'yes') === 'yes') : ?>
                <!-- Day / Night Theme Toggle Button (تم شرقی و کل افزونه) -->
                <button id="theme-toggle-btn" class="rmm-theme-toggle-btn" type="button" aria-label="<?php esc_attr_e('تغییر تم (روز / شب)', 'fondogh-online-menu'); ?>" title="<?php esc_attr_e('تغییر تم (روز / شب)', 'fondogh-online-menu'); ?>">
                    <span class="rmm-theme-icon">🌓</span>
                    <span class="rmm-theme-text"><?php echo esc_html__('تغییر تم (روز / شب)', 'fondogh-online-menu'); ?></span>
                </button>
            <?php endif; ?>

        </div>
        <?php
    }

    /**
     * Render an individual food card inside the menu
     */
    private function render_food_card($product, $settings, $context_data) {
        if (!$product) return;

        $p_id = $product->get_id();
        $in_stock = $product->is_in_stock();

        $active_branch_id = class_exists('\RMM\Branches\BranchContext') ? \RMM\Branches\BranchContext::get_active_branch_id() : 0;
        if ($active_branch_id && class_exists('\RMM\Branches\InventoryRepository')) {
            $inv_item = \RMM\Branches\InventoryRepository::get_item($p_id, $active_branch_id);
            if ($inv_item && (!$inv_item->is_available || $inv_item->stock_status === 'outofstock')) {
                $in_stock = false;
            }
        }

        $price = $product->get_price();
        $regular_price = $product->get_regular_price();
        $is_on_sale = $product->is_on_sale() && !empty($regular_price) && floatval($regular_price) > floatval($price);
        $calories = get_post_meta($p_id, '_rmm_calories', true);
        $prep_time = get_post_meta($p_id, '_rmm_prep_time', true);
        $ingredients = get_post_meta($p_id, '_rmm_ingredients', true);
        $badge = \RMM\Menu\FoodMeta::get_badge_info($p_id, $context_data['active_lang']);

        $food_title = $product->get_name();
        $food_desc  = wp_strip_all_tags($product->get_short_description() ?: $product->get_description());

        if (class_exists('\RMM\I18n\MultiLangManager') && $context_data['active_lang'] !== 'fa') {
            $trans_name = \RMM\I18n\MultiLangManager::get_product_translation($p_id, 'name', $context_data['active_lang']);
            if (!empty($trans_name)) {
                $food_title = $trans_name;
            }
            $trans_ing = \RMM\I18n\MultiLangManager::get_product_translation($p_id, 'ingredients', $context_data['active_lang']);
            if (!empty($trans_ing)) {
                $ingredients = $trans_ing;
            }
            $trans_desc = \RMM\I18n\MultiLangManager::get_product_translation($p_id, 'desc', $context_data['active_lang']);
            if (!empty($trans_desc)) {
                $food_desc = $trans_desc;
            }
        }

        $portions = get_post_meta($p_id, '_rmm_portions', true);
        $modifiers = get_post_meta($p_id, '_rmm_modifier_groups', true);
        $has_options = (!empty($portions) || !empty($modifiers));

        $cat_ids = wp_get_post_terms($p_id, 'product_cat', ['fields' => 'ids']);
        $cat_attr = implode(',', (array)$cat_ids);

        $card_action = $context_data['card_action'];
        $template_id = $context_data['template_id'];
        $popup_id    = $context_data['popup_id'];
        $is_view_only= $context_data['is_view_only'];
        ?>
        <div class="rmm-food-card <?php echo $in_stock ? 'in-stock' : 'out-of-stock'; ?> <?php echo ($card_action !== 'none') ? 'rmm-card-clickable' : ''; ?>" 
             data-id="<?php echo esc_attr($p_id); ?>" 
             data-cats="<?php echo esc_attr($cat_attr); ?>" 
             data-title="<?php echo esc_attr(mb_strtolower($food_title, 'UTF-8')); ?>"
             data-action="<?php echo esc_attr($card_action); ?>"
             data-template-id="<?php echo esc_attr($template_id); ?>"
             data-popup-id="<?php echo esc_attr($popup_id); ?>">
            
            <!-- Thumbnail -->
            <div class="rmm-card-image-wrap">
                <?php if (has_post_thumbnail($p_id)) : ?>
                    <?php echo get_the_post_thumbnail($p_id, 'medium', ['class' => 'rmm-card-img', 'alt' => esc_attr($food_title)]); ?>
                <?php else : ?>
                    <img src="<?php echo esc_url(wc_placeholder_img_src()); ?>" class="rmm-card-img" alt="<?php echo esc_attr($food_title); ?>">
                <?php endif; ?>

                <?php if ($settings['show_badges'] === 'yes' && $badge) : ?>
                    <span class="rmm-card-badge <?php echo esc_attr($badge['class']); ?>">
                        <?php if (!empty($badge['image'])) : ?>
                            <img src="<?php echo esc_url($badge['image']); ?>" class="rmm-badge-icon-img" alt="<?php echo esc_attr($badge['label']); ?>">
                        <?php elseif (!empty($badge['icon'])) : ?>
                            <span class="rmm-badge-icon-symbol"><?php echo esc_html($badge['icon']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($badge['label'])) : ?>
                            <span class="rmm-badge-text-val"><?php echo esc_html($badge['label']); ?></span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Content -->
            <div class="rmm-card-details">
                <h3 class="rmm-food-title"><?php echo esc_html($food_title); ?></h3>

                <div class="rmm-card-price-badge-row">
                    <div class="rmm-food-price">
                        <?php if ($is_view_only && \RMM\Core\Settings::get('catalog_hide_price', 'no') === 'yes') : ?>
                            <span class="rmm-price-hidden"><?php echo esc_html($context_data['str_digital']); ?></span>
                        <?php elseif ($is_on_sale) : ?>
                            <del class="rmm-card-old-price"><?php echo wc_price($regular_price); ?></del>
                            <ins class="rmm-card-sale-price"><?php echo wc_price($price); ?></ins>
                        <?php else : ?>
                            <span class="rmm-card-regular-price"><?php echo wc_price($price); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($settings['show_badges'] === 'yes' && $badge) : ?>
                        <span class="rmm-card-badge <?php echo esc_attr($badge['class']); ?>">
                            <?php if (!empty($badge['image'])) : ?>
                                <img src="<?php echo esc_url($badge['image']); ?>" class="rmm-badge-icon-img" alt="<?php echo esc_attr($badge['label']); ?>">
                            <?php elseif (!empty($badge['icon'])) : ?>
                                <span class="rmm-badge-icon-symbol"><?php echo esc_html($badge['icon']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($badge['label'])) : ?>
                                <span class="rmm-badge-text-val"><?php echo esc_html($badge['label']); ?></span>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($food_desc)) : ?>
                    <p class="rmm-food-desc"><?php echo esc_html($food_desc); ?></p>
                <?php endif; ?>
                <?php if (!empty($context_data['show_ingredients']) && $ingredients && $ingredients !== $food_desc) : ?>
                    <p class="rmm-food-ingredients"><?php echo esc_html($ingredients); ?></p>
                <?php endif; ?>

                <!-- Meta (Calories / Prep Time) -->
                <?php if ($settings['show_calories'] === 'yes' && ($calories || $prep_time)) : ?>
                    <div class="rmm-food-meta-row">
                        <?php if ($calories) : ?>
                            <span>🔥 <?php echo esc_html($calories); ?></span>
                        <?php endif; ?>
                        <?php if ($prep_time) : ?>
                            <span>⏱️ <?php echo esc_html($prep_time . ' ' . $context_data['str_minutes']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Action Button -->
                <div class="rmm-card-action-row">
                    <?php if (!$in_stock) : ?>
                        <span class="rmm-out-badge"><?php echo esc_html($context_data['str_out_of_stock']); ?></span>
                    <?php elseif ($is_view_only) : ?>
                        <?php
                        $action_btn = \RMM\Core\Settings::get('catalog_action_btn', 'whatsapp');
                        $whatsapp = \RMM\Core\Settings::get('whatsapp_number', '');
                        $phone = \RMM\Core\Settings::get('phone_number', '');
                        $cat_thumb_url = has_post_thumbnail($p_id) ? get_the_post_thumbnail_url($p_id, 'thumbnail') : wc_placeholder_img_src();
                        $hide_cat_price = (\RMM\Core\Settings::get('catalog_hide_price', 'no') === 'yes');
                        $cat_raw_price = $hide_cat_price ? 0 : floatval($price);
                        ?>
                        <button type="button"
                                class="rmm-btn-order rmm-catalog-add-btn"
                                data-id="<?php echo esc_attr($p_id); ?>"
                                data-title="<?php echo esc_attr($food_title); ?>"
                                data-price="<?php echo esc_attr($cat_raw_price); ?>"
                                data-image="<?php echo esc_url($cat_thumb_url); ?>">
                            + <?php echo esc_html(class_exists('\RMM\I18n\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_ui_string('add_to_list', $context_data['active_lang']) : __('افزودن به لیست', 'fondogh-online-menu')); ?>
                        </button>
                        <?php if ($action_btn === 'whatsapp' && $whatsapp) : ?>
                            <a href="<?php echo esc_url('https://wa.me/' . $whatsapp . '?text=' . rawurlencode(sprintf(__('سلام، مایل به استعلام و سفارش غذای %s هستم.', 'fondogh-online-menu'), $food_title))); ?>" target="_blank" class="rmm-btn-order whatsapp">
                                💬 <?php echo esc_html($context_data['str_order_wa']); ?>
                            </a>
                        <?php elseif ($action_btn === 'call' && $phone) : ?>
                            <a href="tel:<?php echo esc_attr($phone); ?>" class="rmm-btn-order call">
                                📞 <?php echo esc_html($context_data['str_call']); ?>
                            </a>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php if ($has_options) : ?>
                            <button class="rmm-btn-order rmm-open-modifiers-btn" data-id="<?php echo esc_attr($p_id); ?>">
                                + <?php echo esc_html($context_data['str_options']); ?>
                            </button>
                        <?php else : ?>
                            <button class="rmm-btn-order rmm-quick-add-btn" data-id="<?php echo esc_attr($p_id); ?>">
                                + <?php echo esc_html($context_data['str_add']); ?>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php
    }
}
