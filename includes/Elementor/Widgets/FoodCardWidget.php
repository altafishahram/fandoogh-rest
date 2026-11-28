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

class FoodCardWidget extends Widget_Base {

    public function get_name() {
        return 'rmm_food_card';
    }

    public function get_title() {
        return __('کارت غذای اختصاصی (Food Card Item)', 'fondogh-online-menu');
    }

    public function get_icon() {
        return 'eicon-product-price';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    public function get_keywords() {
        return ['food', 'card', 'item', 'menu', 'غذا', 'کارت', 'منو'];
    }

    public function get_script_depends() {
        return ['rmm-frontend-menu'];
    }

    public function get_style_depends() {
        return ['rmm-frontend-menu'];
    }

    private function get_products_options() {
        $options = ['' => __('— انتخاب خودکار / پیش‌نمایش نمونه —', 'fondogh-online-menu')];
        if (function_exists('wc_get_products')) {
            $products = wc_get_products([
                'status' => 'publish',
                'limit'  => 50,
            ]);
            foreach ($products as $prod) {
                $options[$prod->get_id()] = $prod->get_name() . ' (#' . $prod->get_id() . ')';
            }
        }
        return $options;
    }

    protected function register_controls() {
        // ==================== CONTENT TAB ==================== //
        $this->start_controls_section(
            'section_content_item',
            [
                'label' => __('تنظیمات آیتم غذا', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'product_id',
            [
                'label'       => __('انتخاب محصول غذا', 'fondogh-online-menu'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $this->get_products_options(),
                'default'     => '',
                'description' => __('محصول مورد نظر را برای نمایش اطلاعات واقعی انتخاب کنید.', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'card_layout',
            [
                'label'   => __('چیدمان کارت', 'fondogh-online-menu'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'vertical',
                'options' => [
                    'vertical'   => __('عمودی (کارت مدرن)', 'fondogh-online-menu'),
                    'horizontal' => __('افقی (سطری)', 'fondogh-online-menu'),
                    'compact'    => __('جمع‌وجور (Compact)', 'fondogh-online-menu'),
                ],
            ]
        );

        $this->add_control(
            'show_image',
            [
                'label'        => __('نمایش تصویر غذا', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_description',
            [
                'label'        => __('نمایش توضیحات / ترکیبات', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_badges',
            [
                'label'        => __('نمایش نشان‌های ویژه (تند، گیاهی و...)', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_calories',
            [
                'label'        => __('نمایش کالری و مشخصات', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_order_btn',
            [
                'label'        => __('نمایش دکمه افزودن / سفارش', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'order_btn_text',
            [
                'label'       => __('متن دکمه سفارش', 'fondogh-online-menu'),
                'type'        => Controls_Manager::TEXT,
                'default'     => __('افزودن', 'fondogh-online-menu'),
                'condition'   => [
                    'show_order_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'order_btn_icon',
            [
                'label'       => __('آیکون دکمه سفارش', 'fondogh-online-menu'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-plus',
                    'library' => 'fa-solid',
                ],
                'condition'   => [
                    'show_order_btn' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_details_btn',
            [
                'label'        => __('نمایش دکمه پاپ‌آپ توضیحات (Quick View)', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'details_btn_icon',
            [
                'label'       => __('آیکون دکمه جزئیات', 'fondogh-online-menu'),
                'type'        => Controls_Manager::ICONS,
                'default'     => [
                    'value'   => 'fas fa-eye',
                    'library' => 'fa-solid',
                ],
                'condition'   => [
                    'show_details_btn' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE: CARD CONTAINER ==================== //
        $this->start_controls_section(
            'section_style_card',
            [
                'label' => __('ابعاد و حاشیه‌های کارت', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label'      => __('فاصله داخلی کارت (Padding)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'      => 16,
                    'right'    => 16,
                    'bottom'   => 16,
                    'left'     => 16,
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-food-card-standalone' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_border_radius',
            [
                'label'      => __('گردی لبه‌های کارت (Border Radius)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'      => 16,
                    'right'    => 16,
                    'bottom'   => 16,
                    'left'     => 16,
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-food-card-standalone' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'image_height',
            [
                'label'      => __('ارتفاع تصویر غذا', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'vh'],
                'range'      => [
                    'px' => [
                        'min' => 100,
                        'max' => 500,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 190,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-food-card-standalone .rmm-food-image-wrap' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'show_image' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE: TYPOGRAPHY ==================== //
        $this->start_controls_section(
            'section_style_typography',
            [
                'label' => __('تایپوگرافی متن‌ها', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'label'    => __('تایپوگرافی عنوان غذا', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-food-title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'desc_typography',
                'label'     => __('تایپوگرافی توضیحات', 'fondogh-online-menu'),
                'selector'  => '{{WRAPPER}} .rmm-food-ingredients',
                'condition' => [
                    'show_description' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'price_typography',
                'label'    => __('تایپوگرافی قیمت', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-food-price',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'      => 'btn_typography',
                'label'     => __('تایپوگرافی دکمه سفارش', 'fondogh-online-menu'),
                'selector'  => '{{WRAPPER}} .rmm-btn-order',
                'condition' => [
                    'show_order_btn' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== LIGHT MODE STYLING ==================== //
        $this->start_controls_section(
            'section_style_card_light',
            [
                'label' => __('استایل حالت روشن (Light Mode)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'light_card_bg',
            [
                'label'     => __('رنگ پس‌زمینه کارت (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-food-card-standalone, {{WRAPPER}} .rmm-food-card-standalone' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_title_color',
            [
                'label'     => __('رنگ عنوان (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-food-title, {{WRAPPER}} .rmm-food-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_desc_color',
            [
                'label'     => __('رنگ توضیحات (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#64748b',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-food-ingredients, {{WRAPPER}} .rmm-food-ingredients' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_price_color',
            [
                'label'     => __('رنگ قیمت (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#c82333',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-food-price, {{WRAPPER}} .rmm-food-price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_btn_bg',
            [
                'label'     => __('رنگ پس‌زمینه دکمه (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#c82333',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-btn-order, {{WRAPPER}} .rmm-btn-order' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_btn_text',
            [
                'label'     => __('رنگ متن دکمه (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-btn-order, {{WRAPPER}} .rmm-btn-order' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'light_card_border',
                'selector' => 'body.light-theme {{WRAPPER}} .rmm-food-card-standalone, {{WRAPPER}} .rmm-food-card-standalone',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'light_card_shadow',
                'selector' => 'body.light-theme {{WRAPPER}} .rmm-food-card-standalone, {{WRAPPER}} .rmm-food-card-standalone',
            ]
        );

        $this->end_controls_section();

        // ==================== DARK MODE STYLING ==================== //
        $this->start_controls_section(
            'section_style_card_dark',
            [
                'label' => __('استایل حالت تیره (Dark Mode)', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'dark_card_bg',
            [
                'label'     => __('رنگ پس‌زمینه کارت (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#1f0d13',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-food-card-standalone, body.dark-theme {{WRAPPER}} .rmm-food-card-standalone' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_title_color',
            [
                'label'     => __('رنگ عنوان (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-food-title, body.dark-theme {{WRAPPER}} .rmm-food-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_desc_color',
            [
                'label'     => __('رنگ توضیحات (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#c7b2ab',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-food-ingredients, body.dark-theme {{WRAPPER}} .rmm-food-ingredients' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_price_color',
            [
                'label'     => __('رنگ قیمت (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f5e08b',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-food-price, body.dark-theme {{WRAPPER}} .rmm-food-price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_btn_bg',
            [
                'label'     => __('رنگ پس‌زمینه دکمه (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#d4af37',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-btn-order, body.dark-theme {{WRAPPER}} .rmm-btn-order' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_btn_text',
            [
                'label'     => __('رنگ متن دکمه (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#120305',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-btn-order, body.dark-theme {{WRAPPER}} .rmm-btn-order' => 'color: {{VALUE}}; fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'dark_card_border',
                'selector' => 'body:not(.light-theme) {{WRAPPER}} .rmm-food-card-standalone, body.dark-theme {{WRAPPER}} .rmm-food-card-standalone',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'dark_card_shadow',
                'selector' => 'body:not(.light-theme) {{WRAPPER}} .rmm-food-card-standalone, body.dark-theme {{WRAPPER}} .rmm-food-card-standalone',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $prod_id  = !empty($settings['product_id']) ? intval($settings['product_id']) : 0;

        $title       = __('پیتزا چیکن پستو مخصوص', 'fondogh-online-menu');
        $desc        = __('فیله مرغ گریل، سس پستو دست‌ساز، پنیر موزارلا تازه، گوجه گیلاسی و ریحان ایتالیایی', 'fondogh-online-menu');
        $price_html  = '<span class="woocommerce-Price-amount amount">۳۸۰,۰۰۰&nbsp;<span class="woocommerce-Price-currencySymbol">تومان</span></span>';
        $image_url   = '';
        $is_in_stock = true;
        $calories    = '680 kcal';

        if ($prod_id > 0 && function_exists('wc_get_product')) {
            $product = wc_get_product($prod_id);
            if ($product) {
                $title       = $product->get_name();
                $desc        = $product->get_short_description() ?: $product->get_description();
                $price_html  = $product->get_price_html();
                $is_in_stock = $product->is_in_stock();
                $image_id    = $product->get_image_id();
                if ($image_id) {
                    $image_url = wp_get_attachment_image_url($image_id, 'medium_large');
                }
                $cal_meta = get_post_meta($prod_id, '_rmm_calories', true);
                if (!empty($cal_meta)) {
                    $calories = $cal_meta . ' kcal';
                }
            }
        }

        $layout_class = 'rmm-layout-' . esc_attr($settings['card_layout'] ?? 'vertical');
        $card_classes = 'rmm-food-card rmm-food-card-standalone ' . $layout_class . ($is_in_stock ? '' : ' rmm-out-of-stock');
        $cat_ids      = $prod_id ? wp_get_post_terms($prod_id, 'product_cat', ['fields' => 'ids']) : [];
        $cat_attr     = is_wp_error($cat_ids) ? '' : implode(',', array_map('absint', (array) $cat_ids));
        $lower_title  = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);

        wp_enqueue_style('rmm-frontend-menu');
        wp_enqueue_script('rmm-frontend-menu');
        ?>
        <div class="<?php echo esc_attr($card_classes); ?>" data-product-id="<?php echo esc_attr($prod_id); ?>" data-cats="<?php echo esc_attr($cat_attr); ?>" data-title="<?php echo esc_attr($lower_title); ?>">
            <?php if ($settings['show_image'] === 'yes') : ?>
                <div class="rmm-food-image-wrap rmm-open-modal rmm-card-image-trigger" data-product-id="<?php echo esc_attr($prod_id); ?>">
                    <?php if (!empty($image_url)) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" class="rmm-food-img" loading="lazy">
                    <?php else : ?>
                        <div class="rmm-food-placeholder-img rmm-card-style-01">
                            <i class="fas fa-utensils fa-2x" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>

                    <?php if ($settings['show_badges'] === 'yes') : ?>
                        <div class="rmm-badges-container rmm-card-style-02">
                            <span class="rmm-badge rmm-badge-special rmm-card-style-03">
                                <i class="fas fa-star" aria-hidden="true"></i> <?php esc_html_e('پیشنهاد سرآشپز', 'fondogh-online-menu'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="rmm-food-info-wrap rmm-card-style-04">
                <div>
                    <h3 class="rmm-food-title rmm-open-modal rmm-card-title-trigger" data-product-id="<?php echo esc_attr($prod_id); ?>">
                        <?php echo esc_html($title); ?>
                    </h3>

                    <?php if ($settings['show_description'] === 'yes' && !empty($desc)) : ?>
                        <div class="rmm-food-ingredients rmm-card-style-05">
                            <?php echo wp_kses_post(wp_trim_words($desc, 18)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($settings['show_calories'] === 'yes' && !empty($calories)) : ?>
                        <div class="rmm-food-meta-row rmm-card-style-06">
                            <span class="rmm-meta-calorie"><i class="fas fa-fire" aria-hidden="true"></i> <?php echo esc_html($calories); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rmm-food-bottom-bar rmm-card-style-07">
                    <div class="rmm-food-price">
                        <?php echo wp_kses_post($price_html); ?>
                    </div>

                    <div class="rmm-food-actions rmm-card-style-08">
                        <?php if ($settings['show_details_btn'] === 'yes') : ?>
                            <button type="button" class="rmm-btn-details rmm-open-modal rmm-food-details-trigger" data-product-id="<?php echo esc_attr($prod_id); ?>" aria-label="<?php esc_attr_e('مشاهده جزئیات', 'fondogh-online-menu'); ?>">
                                <?php
                                if (!empty($settings['details_btn_icon']['value'])) {
                                    Icons_Manager::render_icon($settings['details_btn_icon'], ['aria-hidden' => 'true']);
                                } else {
                                    echo '<i class="fas fa-eye" aria-hidden="true"></i>';
                                }
                                ?>
                            </button>
                        <?php endif; ?>

                        <?php if ($settings['show_order_btn'] === 'yes') :
                            $card_raw_price = (isset($product) && $product) ? floatval($product->get_price()) : 0;
                        ?>
                            <button type="button" class="rmm-btn-order rmm-card-order-trigger" data-product-id="<?php echo esc_attr($prod_id); ?>" data-title="<?php echo esc_attr($title); ?>" data-price="<?php echo esc_attr($card_raw_price); ?>" data-image="<?php echo esc_url($image_url); ?>">
                                <?php if (!empty($settings['order_btn_icon']['value'])) : ?>
                                    <span class="rmm-btn-icon" aria-hidden="true">
                                        <?php Icons_Manager::render_icon($settings['order_btn_icon'], ['aria-hidden' => 'true']); ?>
                                    </span>
                                <?php endif; ?>
                                <span><?php echo esc_html($settings['order_btn_text'] ?? __('افزودن', 'fondogh-online-menu')); ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
