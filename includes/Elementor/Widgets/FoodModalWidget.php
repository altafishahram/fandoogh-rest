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

class FoodModalWidget extends Widget_Base {

    public function get_name() {
        return 'rmm_food_modal';
    }

    public function get_title() {
        return __('پاپ‌آپ جزئیات غذا (Food Details Modal)', 'fondogh-online-menu');
    }

    public function get_icon() {
        return 'eicon-lightbox-expand';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    public function get_keywords() {
        return ['modal', 'popup', 'food', 'details', 'پاپ‌آپ', 'مودال', 'جزئیات غذا'];
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
            'section_modal_settings',
            [
                'label' => __('تنظیمات عمومی پاپ‌آپ', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'close_icon',
            [
                'label'       => __('آیکون بستن پاپ‌آپ (کتابخانه یا SVG)', 'fondogh-online-menu'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-times',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'cart_btn_icon',
            [
                'label'       => __('آیکون دکمه سفارش داخل پاپ‌آپ', 'fondogh-online-menu'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-shopping-bag',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'cart_btn_text',
            [
                'label'   => __('متن دکمه سفارش', 'fondogh-online-menu'),
                'type'    => Controls_Manager::TEXT,
                'default' => __('افزودن به سبد سفارش', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'show_calories_badge',
            [
                'label'        => __('نمایش برچسب کالری', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_ingredients_section',
            [
                'label'        => __('نمایش بخش ترکیبات و توضیحات کامل', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_responsive_control(
            'modal_max_width',
            [
                'label'      => __('حداکثر عرض پنجره پاپ‌آپ', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min' => 300,
                        'max' => 900,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 100,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 520,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-modal-content, .rmm-food-details-modal .rmm-modal-content' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE: BACKDROP & OVERLAY ==================== //
        $this->start_controls_section(
            'section_style_backdrop',
            [
                'label' => __('پرده پس‌زمینه (Backdrop Overlay)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'backdrop_light_bg',
            [
                'label'     => __('رنگ پس‌زمینه تیره/تار (حالت روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(15, 23, 42, 0.65)',
                'selectors' => [
                    'body.light-theme .rmm-modal-backdrop, {{WRAPPER}} .rmm-modal-backdrop' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'backdrop_dark_bg',
            [
                'label'     => __('رنگ پس‌زمینه تیره/تار (حالت شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => 'rgba(0, 0, 0, 0.85)',
                'selectors' => [
                    'body:not(.light-theme) .rmm-modal-backdrop, body.dark-theme .rmm-modal-backdrop' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'backdrop_blur',
            [
                'label'      => __('میزان تاری پس‌زمینه (Blur)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-modal-backdrop, .rmm-modal-backdrop' => 'backdrop-filter: blur({{SIZE}}{{UNIT}}); -webkit-backdrop-filter: blur({{SIZE}}{{UNIT}});',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE: MODAL BOX ==================== //
        $this->start_controls_section(
            'section_style_modal_box',
            [
                'label' => __('کارت و پنجره پاپ‌آپ', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'box_border_radius',
            [
                'label'      => __('گردی لبه‌های پنجره (Border Radius)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'      => 24,
                    'right'    => 24,
                    'bottom'   => 24,
                    'left'     => 24,
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-modal-content, .rmm-food-details-modal .rmm-modal-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'box_padding',
            [
                'label'      => __('فاصله داخلی محتوا (Padding)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'      => 20,
                    'right'    => 24,
                    'bottom'   => 24,
                    'left'     => 24,
                    'unit'     => 'px',
                    'isLinked' => false,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-modal-body, .rmm-food-details-modal .rmm-modal-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== LIGHT MODE STYLING ==================== //
        $this->start_controls_section(
            'section_style_modal_light',
            [
                'label' => __('استایل حالت روشن (Light Mode)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'light_modal_bg',
            [
                'label'     => __('رنگ پس‌زمینه پنجره (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    'body.light-theme .rmm-modal-content, {{WRAPPER}} .rmm-modal-content' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_modal_title',
            [
                'label'     => __('رنگ عنوان غذا (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => [
                    'body.light-theme .rmm-modal-title, {{WRAPPER}} .rmm-modal-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_modal_desc',
            [
                'label'     => __('رنگ توضیحات غذا (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#475569',
                'selectors' => [
                    'body.light-theme .rmm-modal-desc, {{WRAPPER}} .rmm-modal-desc' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_modal_price',
            [
                'label'     => __('رنگ قیمت (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#c82333',
                'selectors' => [
                    'body.light-theme .rmm-modal-price, {{WRAPPER}} .rmm-modal-price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_btn_bg',
            [
                'label'     => __('رنگ دکمه ثبت سفارش (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#c82333',
                'selectors' => [
                    'body.light-theme .rmm-modal-add-cart, {{WRAPPER}} .rmm-modal-add-cart' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_btn_text_color',
            [
                'label'     => __('رنگ متن دکمه ثبت سفارش (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    'body.light-theme .rmm-modal-add-cart, {{WRAPPER}} .rmm-modal-add-cart' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'light_box_border',
                'selector' => 'body.light-theme .rmm-modal-content, {{WRAPPER}} .rmm-modal-content',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'light_box_shadow',
                'selector' => 'body.light-theme .rmm-modal-content, {{WRAPPER}} .rmm-modal-content',
            ]
        );

        $this->end_controls_section();

        // ==================== DARK MODE STYLING ==================== //
        $this->start_controls_section(
            'section_style_modal_dark',
            [
                'label' => __('استایل حالت تیره (Dark Mode)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'dark_modal_bg',
            [
                'label'     => __('رنگ پس‌زمینه پنجره (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#1f0d13',
                'selectors' => [
                    'body:not(.light-theme) .rmm-modal-content, body.dark-theme .rmm-modal-content' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_modal_title',
            [
                'label'     => __('رنگ عنوان غذا (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    'body:not(.light-theme) .rmm-modal-title, body.dark-theme .rmm-modal-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_modal_desc',
            [
                'label'     => __('رنگ توضیحات غذا (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#c7b2ab',
                'selectors' => [
                    'body:not(.light-theme) .rmm-modal-desc, body.dark-theme .rmm-modal-desc' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_modal_price',
            [
                'label'     => __('رنگ قیمت (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f5e08b',
                'selectors' => [
                    'body:not(.light-theme) .rmm-modal-price, body.dark-theme .rmm-modal-price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_btn_bg',
            [
                'label'     => __('رنگ دکمه ثبت سفارش (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#d4af37',
                'selectors' => [
                    'body:not(.light-theme) .rmm-modal-add-cart, body.dark-theme .rmm-modal-add-cart' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_btn_text_color',
            [
                'label'     => __('رنگ متن دکمه ثبت سفارش (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#120305',
                'selectors' => [
                    'body:not(.light-theme) .rmm-modal-add-cart, body.dark-theme .rmm-modal-add-cart' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'dark_box_border',
                'selector' => 'body:not(.light-theme) .rmm-modal-content, body.dark-theme .rmm-modal-content',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'dark_box_shadow',
                'selector' => 'body:not(.light-theme) .rmm-modal-content, body.dark-theme .rmm-modal-content',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        wp_enqueue_style('rmm-frontend-menu');
        wp_enqueue_script('rmm-frontend-menu');
        ?>
        <div class="rmm-modal-widget-container">
            <!-- Modal Structure for Interactive Quick View -->
            <div id="rmm-food-modal" class="rmm-modal-backdrop rmm-food-details-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="rmm-modal-title">
                <div class="rmm-modal-content" style="position:relative; margin:auto; overflow:hidden;">
                    <button type="button" class="rmm-modal-close" aria-label="<?php esc_attr_e('بستن پنجره', 'fondogh-online-menu'); ?>" style="position:absolute; top:14px; left:14px; z-index:10; background:rgba(0,0,0,0.3); border:none; border-radius:50%; width:36px; height:36px; display:flex; align-items:center; justify-content:center; color:#fff; cursor:pointer;">
                        <?php
                        if (!empty($settings['close_icon']['value'])) {
                            Icons_Manager::render_icon($settings['close_icon'], ['aria-hidden' => 'true']);
                        } else {
                            echo '&times;';
                        }
                        ?>
                    </button>

                    <div class="rmm-modal-banner" style="position:relative; height:240px; background:#f1f5f9; overflow:hidden;">
                        <img id="rmm-modal-image" src="" alt="" style="width:100%; height:100%; object-fit:cover; display:none;">
                        <div class="rmm-modal-image-placeholder" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#94a3b8;">
                            <i class="fas fa-utensils fa-3x" aria-hidden="true"></i>
                        </div>
                    </div>

                    <div class="rmm-modal-body">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
                            <h2 id="rmm-modal-title" class="rmm-modal-title" style="margin:0; font-size:20px; font-weight:700;"></h2>
                            <div id="rmm-modal-price" class="rmm-modal-price" style="font-size:18px; font-weight:700;"></div>
                        </div>

                        <?php if ($settings['show_calories_badge'] === 'yes') : ?>
                            <div id="rmm-modal-meta" class="rmm-modal-meta" style="margin-bottom:12px; display:flex; gap:8px;">
                                <span id="rmm-modal-calories" class="rmm-badge rmm-badge-cal" style="display:none; font-size:12px; padding:4px 8px; border-radius:8px;"></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($settings['show_ingredients_section'] === 'yes') : ?>
                            <div id="rmm-modal-desc" class="rmm-modal-desc" style="font-size:14px; line-height:1.7; margin-bottom:16px;"></div>
                        <?php endif; ?>

                        <!-- Addons / Variations Section -->
                        <div id="rmm-modal-options" class="rmm-modal-options" style="margin-bottom:16px;"></div>

                        <div class="rmm-modal-footer" style="display:flex; align-items:center; justify-content:space-between; margin-top:16px; pt-4;">
                            <div class="rmm-qty-stepper" style="display:inline-flex; align-items:center; gap:8px;">
                                <button type="button" class="rmm-qty-btn rmm-qty-minus" style="width:32px; height:32px; border-radius:8px; border:none; cursor:pointer;">-</button>
                                <span class="rmm-qty-val" style="font-weight:700; min-width:20px; text-align:center;">1</span>
                                <button type="button" class="rmm-qty-btn rmm-qty-plus" style="width:32px; height:32px; border-radius:8px; border:none; cursor:pointer;">+</button>
                            </div>

                            <button type="button" class="rmm-modal-add-cart" style="display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:12px; border:none; font-weight:600; cursor:pointer;">
                                <?php if (!empty($settings['cart_btn_icon']['value'])) : ?>
                                    <span class="rmm-btn-icon" aria-hidden="true">
                                        <?php Icons_Manager::render_icon($settings['cart_btn_icon'], ['aria-hidden' => 'true']); ?>
                                    </span>
                                <?php endif; ?>
                                <span><?php echo esc_html($settings['cart_btn_text']); ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
