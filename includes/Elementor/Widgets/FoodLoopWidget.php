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

class FoodLoopWidget extends Widget_Base {

    public function get_name() {
        return 'rmm_food_loop';
    }

    public function get_title() {
        return __('لوپ و شبکه کارت غذاها (Food Loop Grid)', 'fondogh-online-menu');
    }

    public function get_icon() {
        return 'eicon-loop-builder';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    public function get_keywords() {
        return ['loop', 'grid', 'food', 'menu', 'لوپ', 'شبکه', 'کارت غذا'];
    }

    public function get_script_depends() {
        return ['rmm-frontend-menu'];
    }

    public function get_style_depends() {
        return ['rmm-frontend-menu'];
    }

    private function get_category_options() {
        $options = ['' => __('همه دسته‌بندی‌ها (All)', 'fondogh-online-menu')];
        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ]);
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $options[$term->slug] = $term->name;
            }
        }
        return $options;
    }

    protected function register_controls() {
        // ==================== QUERY SECTION ==================== //
        $this->start_controls_section(
            'section_query',
            [
                'label' => __('تنظیمات کوئری و دریافت غذاها', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'category_filter',
            [
                'label'   => __('فیلتر بر اساس دسته‌بندی', 'fondogh-online-menu'),
                'type'    => Controls_Manager::SELECT,
                'options' => $this->get_category_options(),
                'default' => '',
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label'   => __('تعداد غذاها برای نمایش', 'fondogh-online-menu'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 12,
                'min'     => 1,
                'max'     => 100,
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label'   => __('مرتب‌سازی بر اساس', 'fondogh-online-menu'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'menu_order',
                'options' => [
                    'menu_order' => __('ترتیب دستی منو (Menu Order)', 'fondogh-online-menu'),
                    'date'       => __('جدیدترین غذاها', 'fondogh-online-menu'),
                    'title'      => __('عنوان غذا (الفبا)', 'fondogh-online-menu'),
                    'price'      => __('قیمت غذا', 'fondogh-online-menu'),
                    'popularity' => __('محبوبیت / پرفروش‌ترین', 'fondogh-online-menu'),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label'   => __('جهت مرتب‌سازی', 'fondogh-online-menu'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'ASC',
                'options' => [
                    'ASC'  => __('صعودی (ASC)', 'fondogh-online-menu'),
                    'DESC' => __('نزولی (DESC)', 'fondogh-online-menu'),
                ],
            ]
        );

        $this->add_control(
            'only_in_stock',
            [
                'label'        => __('فقط غذاهای موجود', 'fondogh-online-menu'),
                'type'         => Controls_Manager::SWITCHER,
                'default'      => 'no',
                'return_value' => 'yes',
            ]
        );

        $this->end_controls_section();

        // ==================== LAYOUT SECTION ==================== //
        $this->start_controls_section(
            'section_layout',
            [
                'label' => __('ساختار شبکه و ستون‌ها', 'fondogh-online-menu'),
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label'           => __('تعداد ستون‌ها', 'fondogh-online-menu'),
                'type'            => Controls_Manager::SELECT,
                'desktop_default' => '3',
                'tablet_default'  => '2',
                'mobile_default'  => '1',
                'options'         => [
                    '1' => '۱ ستون',
                    '2' => '۲ ستون',
                    '3' => '۳ ستون',
                    '4' => '۴ ستون',
                    '5' => '۵ ستون',
                    '6' => '۶ ستون',
                ],
                'selectors'       => [
                    '{{WRAPPER}} .rmm-food-loop-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ],
            ]
        );

        $this->add_responsive_control(
            'grid_gap',
            [
                'label'      => __('فاصله بین کارت‌ها (Gap)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => [
                    'px' => [
                        'min' => 4,
                        'max' => 50,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-food-loop-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'card_style_type',
            [
                'label'   => __('سبک نمایش کارت‌ها', 'fondogh-online-menu'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'vertical',
                'options' => [
                    'vertical'   => __('عمودی مدرن (Modern Vertical)', 'fondogh-online-menu'),
                    'horizontal' => __('افقی سطری (Horizontal Row)', 'fondogh-online-menu'),
                ],
            ]
        );

        $this->add_control(
            'order_btn_icon',
            [
                'label'   => __('آیکون دکمه سفارش', 'fondogh-online-menu'),
                'type'    => Controls_Manager::ICONS,
                'default' => [
                    'value'   => 'fas fa-plus',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'details_btn_icon',
            [
                'label'   => __('آیکون دکمه مشاهده جزئیات', 'fondogh-online-menu'),
                'type'    => Controls_Manager::ICONS,
                'default' => [
                    'value'   => 'fas fa-eye',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE: CARD BASE ==================== //
        $this->start_controls_section(
            'section_style_loop_card',
            [
                'label' => __('ابعاد و حاشیه‌های کارت', 'fondogh-online-menu'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'loop_card_padding',
            [
                'label'      => __('فاصله داخلی کارت (Padding)', 'fondogh-online-menu'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'      => 14,
                    'right'    => 14,
                    'bottom'   => 14,
                    'left'     => 14,
                    'unit'     => 'px',
                    'isLinked' => true,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-food-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'loop_card_border_radius',
            [
                'label'      => __('گردی لبه‌های کارت', 'fondogh-online-menu'),
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
                    '{{WRAPPER}} .rmm-food-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'loop_title_typography',
                'label'    => __('تایپوگرافی عنوان غذا', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-food-title',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'loop_price_typography',
                'label'    => __('تایپوگرافی قیمت', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-food-price',
            ]
        );

        $this->end_controls_section();

        // ==================== LIGHT MODE STYLING ==================== //
        $this->start_controls_section(
            'section_style_loop_light',
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
                    'body.light-theme {{WRAPPER}} .rmm-food-card, {{WRAPPER}} .rmm-food-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_title_color',
            [
                'label'     => __('رنگ عنوان غذا (روز)', 'fondogh-online-menu'),
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
                'label'     => __('رنگ توضیحات غذا (روز)', 'fondogh-online-menu'),
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
            'light_order_btn_bg',
            [
                'label'     => __('رنگ دکمه سفارش (روز)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#c82333',
                'selectors' => [
                    'body.light-theme {{WRAPPER}} .rmm-btn-order, {{WRAPPER}} .rmm-btn-order' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'light_order_btn_text',
            [
                'label'     => __('رنگ متن دکمه سفارش (روز)', 'fondogh-online-menu'),
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
                'name'     => 'light_loop_border',
                'selector' => 'body.light-theme {{WRAPPER}} .rmm-food-card, {{WRAPPER}} .rmm-food-card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'light_loop_shadow',
                'selector' => 'body.light-theme {{WRAPPER}} .rmm-food-card, {{WRAPPER}} .rmm-food-card',
            ]
        );

        $this->end_controls_section();

        // ==================== DARK MODE STYLING ==================== //
        $this->start_controls_section(
            'section_style_loop_dark',
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
                    'body:not(.light-theme) {{WRAPPER}} .rmm-food-card, body.dark-theme {{WRAPPER}} .rmm-food-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_title_color',
            [
                'label'     => __('رنگ عنوان غذا (شب)', 'fondogh-online-menu'),
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
                'label'     => __('رنگ توضیحات غذا (شب)', 'fondogh-online-menu'),
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
            'dark_order_btn_bg',
            [
                'label'     => __('رنگ دکمه سفارش (شب)', 'fondogh-online-menu'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#d4af37',
                'selectors' => [
                    'body:not(.light-theme) {{WRAPPER}} .rmm-btn-order, body.dark-theme {{WRAPPER}} .rmm-btn-order' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'dark_order_btn_text',
            [
                'label'     => __('رنگ متن دکمه سفارش (شب)', 'fondogh-online-menu'),
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
                'name'     => 'dark_loop_border',
                'selector' => 'body:not(.light-theme) {{WRAPPER}} .rmm-food-card, body.dark-theme {{WRAPPER}} .rmm-food-card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'dark_loop_shadow',
                'selector' => 'body:not(.light-theme) {{WRAPPER}} .rmm-food-card, body.dark-theme {{WRAPPER}} .rmm-food-card',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $args = [
            'status'  => 'publish',
            'limit'   => !empty($settings['posts_per_page']) ? intval($settings['posts_per_page']) : 12,
            'orderby' => $settings['orderby'] ?? 'menu_order',
            'order'   => $settings['order'] ?? 'ASC',
        ];

        if (!empty($settings['category_filter'])) {
            $cat_val = $settings['category_filter'];
            if (is_numeric($cat_val)) {
                $term = get_term(absint($cat_val), 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $args['category'] = [$term->slug];
                }
            } else {
                $args['category'] = [$cat_val];
            }
        }

        if (($settings['only_in_stock'] ?? 'no') === 'yes') {
            $args['stock_status'] = 'instock';
        }

        $products = function_exists('wc_get_products') ? wc_get_products($args) : [];

        $branch_id = class_exists('\RMM\Branches\BranchContext') ? \RMM\Branches\BranchContext::get_active_branch_id() : 0;
        $branch_inventory = [];
        if ($branch_id && class_exists('\RMM\Branches\InventoryRepository') && !empty($products)) {
            $product_ids = array_map(function ($p) { return $p->get_id(); }, $products);
            $branch_inventory = \RMM\Branches\InventoryRepository::get_bulk_branch_inventory($branch_id, $product_ids);
        }

        $layout_class = 'rmm-layout-' . esc_attr($settings['card_style_type'] ?? 'vertical');

        wp_enqueue_style('rmm-frontend-menu');
        wp_enqueue_script('rmm-frontend-menu');
        ?>
        <div class="rmm-food-loop-wrapper">
            <div class="rmm-food-grid rmm-food-loop-grid" style="display:grid;">
                <?php if (!empty($products)) : ?>
                    <?php foreach ($products as $product) :
                        $prod_id     = $product->get_id();
                        $title       = $product->get_name();
                        $desc        = $product->get_short_description() ?: $product->get_description();
                        $price_html  = $product->get_price_html();
                        $is_in_stock = $product->is_in_stock();

                        // Check branch-specific inventory overrides
                        if ($branch_id && isset($branch_inventory[$prod_id])) {
                            $inv = $branch_inventory[$prod_id];
                            if ($inv->stock_status === 'outofstock' || empty($inv->is_available)) {
                                $is_in_stock = false;
                            }
                        }

                        if (($settings['only_in_stock'] ?? 'no') === 'yes' && !$is_in_stock) {
                            continue;
                        }
                        $image_id    = $product->get_image_id();
                        $image_url   = $image_id ? wp_get_attachment_image_url($image_id, 'medium_large') : '';
                        $card_class  = 'rmm-food-card ' . $layout_class . ($is_in_stock ? '' : ' rmm-out-of-stock');
                        ?>
                        <div class="<?php echo esc_attr($card_class); ?>" data-product-id="<?php echo esc_attr($prod_id); ?>">
                            <div class="rmm-food-image-wrap rmm-open-modal" data-product-id="<?php echo esc_attr($prod_id); ?>" style="cursor:pointer; position:relative;">
                                <?php if (!empty($image_url)) : ?>
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" class="rmm-food-img" loading="lazy">
                                <?php else : ?>
                                    <div class="rmm-food-placeholder-img" style="width:100%; height:180px; display:flex; align-items:center; justify-content:center; background:#f1f5f9; color:#94a3b8;">
                                        <i class="fas fa-utensils fa-2x" aria-hidden="true"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="rmm-food-info-wrap" style="flex:1; display:flex; flex-direction:column; justify-content:space-between; margin-top:10px;">
                                <div>
                                    <h3 class="rmm-food-title rmm-open-modal" data-product-id="<?php echo esc_attr($prod_id); ?>" style="cursor:pointer; margin:0 0 6px 0;">
                                        <?php echo esc_html($title); ?>
                                    </h3>

                                    <?php if (!empty($desc)) : ?>
                                        <div class="rmm-food-ingredients" style="font-size:13px; line-height:1.6; margin-bottom:8px;">
                                            <?php echo wp_kses_post(wp_trim_words($desc, 14)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="rmm-food-bottom-bar" style="display:flex; align-items:center; justify-content:space-between; margin-top:8px;">
                                    <div class="rmm-food-price">
                                        <?php echo wp_kses_post($price_html); ?>
                                    </div>

                                    <div class="rmm-food-actions" style="display:flex; gap:6px;">
                                        <button type="button" class="rmm-btn-details rmm-open-modal" data-product-id="<?php echo esc_attr($prod_id); ?>" aria-label="<?php esc_attr_e('مشاهده جزئیات', 'fondogh-online-menu'); ?>" style="border:none; background:transparent; padding:6px; cursor:pointer;">
                                            <?php
                                            if (!empty($settings['details_btn_icon']['value'])) {
                                                Icons_Manager::render_icon($settings['details_btn_icon'], ['aria-hidden' => 'true']);
                                            } else {
                                                echo '<i class="fas fa-eye" aria-hidden="true"></i>';
                                            }
                                            ?>
                                        </button>

                                        <button type="button" class="rmm-btn-order" data-product-id="<?php echo esc_attr($prod_id); ?>" style="display:inline-flex; align-items:center; gap:4px; border:none; cursor:pointer; padding:6px 12px; border-radius:8px;">
                                            <?php if (!empty($settings['order_btn_icon']['value'])) : ?>
                                                <span class="rmm-btn-icon" aria-hidden="true">
                                                    <?php Icons_Manager::render_icon($settings['order_btn_icon'], ['aria-hidden' => 'true']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <span><?php esc_html_e('افزودن', 'fondogh-online-menu'); ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="rmm-no-foods-found" style="text-align:center; padding:30px; opacity:0.7;">
                        <?php esc_html_e('غذایی در این دسته‌بندی یافت نشد.', 'fondogh-online-menu'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
