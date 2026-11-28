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

        $this->add_control(
            'pagination_type',
            [
                'label'   => __('نوع صفحه‌بندی', 'fondogh-online-menu'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none'      => __('بدون صفحه‌بندی (ثابت)', 'fondogh-online-menu'),
                    'load_more' => __('دکمه «بارگذاری بیشتر» (Load More)', 'fondogh-online-menu'),
                ],
            ]
        );

        $this->add_control(
            'load_more_text',
            [
                'label'     => __('متن دکمه بارگذاری بیشتر', 'fondogh-online-menu'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('مشاهده غذاهای بیشتر ▾', 'fondogh-online-menu'),
                'condition' => [
                    'pagination_type' => 'load_more',
                ],
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

    /**
     * Render single food card HTML (shared between initial render and AJAX Load More)
     */
    public static function render_card_html($product, $settings = [], $branch_id = 0, $branch_inventory = []) {
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
            return;
        }

        $layout_class = 'rmm-layout-' . esc_attr($settings['card_style_type'] ?? 'vertical');
        $image_id    = $product->get_image_id();
        $image_url   = $image_id ? wp_get_attachment_image_url($image_id, 'medium_large') : '';
        $card_class  = 'rmm-food-card ' . $layout_class . ($is_in_stock ? '' : ' rmm-out-of-stock');
        $cat_ids     = wp_get_post_terms($prod_id, 'product_cat', ['fields' => 'ids']);
        $cat_attr    = is_wp_error($cat_ids) ? '' : implode(',', array_map('absint', (array) $cat_ids));
        $lower_title = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);
        ?>
        <div class="<?php echo esc_attr($card_class); ?>" data-product-id="<?php echo esc_attr($prod_id); ?>" data-cats="<?php echo esc_attr($cat_attr); ?>" data-title="<?php echo esc_attr($lower_title); ?>">
            <div class="rmm-food-image-wrap rmm-open-modal rmm-loop-image-trigger" data-product-id="<?php echo esc_attr($prod_id); ?>">
                <?php if (!empty($image_url)) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" class="rmm-food-img" loading="lazy">
                <?php else : ?>
                    <div class="rmm-food-placeholder-img rmm-loop-style-01">
                        <i class="fas fa-utensils fa-2x" aria-hidden="true"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="rmm-food-info-wrap rmm-loop-style-02">
                <div>
                    <h3 class="rmm-food-title rmm-open-modal rmm-loop-title-trigger" data-product-id="<?php echo esc_attr($prod_id); ?>">
                        <?php echo esc_html($title); ?>
                    </h3>

                    <?php if (!empty($desc)) : ?>
                        <div class="rmm-food-ingredients rmm-loop-style-03">
                            <?php echo wp_kses_post(wp_trim_words($desc, 14)); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rmm-food-bottom-bar rmm-loop-style-04">
                    <div class="rmm-food-price">
                        <?php echo wp_kses_post($price_html); ?>
                    </div>

                    <div class="rmm-food-actions rmm-loop-style-05">
                        <button type="button" class="rmm-btn-details rmm-open-modal rmm-food-details-trigger" data-product-id="<?php echo esc_attr($prod_id); ?>" aria-label="<?php esc_attr_e('مشاهده جزئیات', 'fondogh-online-menu'); ?>">
                            <?php
                            if (!empty($settings['details_btn_icon']['value'])) {
                                Icons_Manager::render_icon($settings['details_btn_icon'], ['aria-hidden' => 'true']);
                            } else {
                                echo '<i class="fas fa-eye" aria-hidden="true"></i>';
                            }
                            ?>
                        </button>

                        <button type="button" class="rmm-btn-order rmm-loop-order-trigger" data-product-id="<?php echo esc_attr($prod_id); ?>" data-title="<?php echo esc_attr($title); ?>" data-price="<?php echo esc_attr(floatval($product->get_price())); ?>" data-image="<?php echo esc_url($image_url); ?>">
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
        <?php
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $per_page = !empty($settings['posts_per_page']) ? intval($settings['posts_per_page']) : 12;
        $args = [
            'status'                 => 'publish',
            'visibility'             => 'catalog',
            'limit'                  => $per_page,
            'page'                   => 1,
            'paginate'               => true,
            'orderby'                => $settings['orderby'] ?? 'menu_order',
            'order'                  => $settings['order'] ?? 'ASC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        ];

        $cat_val = $settings['category_filter'] ?? '';
        if (!empty($cat_val)) {
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

        $results = function_exists('wc_get_products') ? wc_get_products($args) : null;
        $products = ($results && is_object($results) && isset($results->products)) ? $results->products : [];
        $max_pages = ($results && is_object($results) && isset($results->max_num_pages)) ? $results->max_num_pages : 1;

        $branch_id = class_exists('\RMM\Branches\BranchContext') ? \RMM\Branches\BranchContext::get_active_branch_id() : 0;
        $branch_inventory = [];
        if ($branch_id && class_exists('\RMM\Branches\InventoryRepository') && !empty($products)) {
            $product_ids = array_map(function ($p) { return $p->get_id(); }, $products);
            $branch_inventory = \RMM\Branches\InventoryRepository::get_bulk_branch_inventory($branch_id, $product_ids);
        }

        wp_enqueue_style('rmm-frontend-menu');
        wp_enqueue_script('rmm-frontend-menu');
        ?>
        <div class="rmm-food-loop-wrapper">
            <div class="rmm-food-grid rmm-food-loop-grid rmm-loop-style-06">
                <?php if (!empty($products)) : ?>
                    <?php foreach ($products as $product) : ?>
                        <?php self::render_card_html($product, $settings, $branch_id, $branch_inventory); ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="rmm-no-foods-found rmm-loop-style-07">
                        <?php esc_html_e('غذایی در این دسته‌بندی یافت نشد.', 'fondogh-online-menu'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if (($settings['pagination_type'] ?? 'none') === 'load_more' && $max_pages > 1) : ?>
                <?php
                $serialize_icon = static function ($icon) {
                    if (!is_array($icon) || empty($icon['value'])) {
                        return [];
                    }
                    $lib = sanitize_key($icon['library'] ?? '');
                    if (is_array($icon['value'])) {
                        return [
                            'library' => $lib,
                            'value'   => [
                                'id'  => absint($icon['value']['id'] ?? 0),
                                'url' => esc_url_raw($icon['value']['url'] ?? ''),
                            ],
                        ];
                    }
                    return [
                        'library' => $lib,
                        'value'   => sanitize_text_field((string) $icon['value']),
                    ];
                };
                ?>
                <div class="rmm-food-loop-load-more-wrap rmm-loop-style-08">
                    <button type="button" class="rmm-food-loop-load-more-btn"
                            data-page="1"
                            data-max-pages="<?php echo esc_attr($max_pages); ?>"
                            data-query="<?php echo esc_attr(wp_json_encode([
                                'posts_per_page'   => $per_page,
                                'category'         => $cat_val,
                                'orderby'          => $settings['orderby'] ?? 'menu_order',
                                'order'            => $settings['order'] ?? 'ASC',
                                'only_in_stock'    => $settings['only_in_stock'] ?? 'no',
                                'card_style_type'  => $settings['card_style_type'] ?? 'vertical',
                                'details_btn_icon' => $serialize_icon($settings['details_btn_icon'] ?? []),
                                'order_btn_icon'   => $serialize_icon($settings['order_btn_icon'] ?? []),
                            ])); ?>">
                        <?php echo esc_html(!empty($settings['load_more_text']) ? $settings['load_more_text'] : __('مشاهده غذاهای بیشتر ▾', 'fondogh-online-menu')); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX endpoint for Food Loop Load More pagination
     */
    public static function ajax_load_more_products() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $rate_key = 'rmm_loop_rl_' . md5($ip);
        $hits = (int) get_transient($rate_key);
        if ($hits >= 60) {
            wp_send_json_error([
                'message' => __('تعداد درخواست‌ها بیش از حد مجاز است. کمی صبر کنید.', 'fondogh-online-menu'),
            ], 429);
        }
        set_transient($rate_key, $hits + 1, MINUTE_IN_SECONDS);

        $page = min(50, max(1, absint($_POST['page'] ?? 1)));
        $query_data = is_array($_POST['query'] ?? null) ? $_POST['query'] : [];

        $per_page      = min(48, max(1, absint($query_data['posts_per_page'] ?? 12)));
        $category      = sanitize_text_field($query_data['category'] ?? '');
        $orderby       = sanitize_key($query_data['orderby'] ?? 'menu_order');
        $order         = strtoupper(sanitize_key($query_data['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $only_in_stock = ($query_data['only_in_stock'] ?? 'no') === 'yes';
        $card_layout   = sanitize_key($query_data['card_style_type'] ?? 'vertical');

        $parse_icon = static function ($raw) {
            if (!is_array($raw) || empty($raw['value'])) {
                return [];
            }
            $lib = sanitize_key($raw['library'] ?? '');
            if (is_array($raw['value'])) {
                return [
                    'library' => $lib,
                    'value'   => [
                        'id'  => absint($raw['value']['id'] ?? 0),
                        'url' => esc_url_raw($raw['value']['url'] ?? ''),
                    ],
                ];
            }
            return [
                'library' => $lib,
                'value'   => sanitize_text_field((string) $raw['value']),
            ];
        };
        $details_btn_icon = $parse_icon($query_data['details_btn_icon'] ?? []);
        $order_btn_icon   = $parse_icon($query_data['order_btn_icon'] ?? []);

        $args = [
            'status'                 => 'publish',
            'visibility'             => 'catalog',
            'limit'                  => $per_page,
            'page'                   => $page,
            'paginate'               => true,
            'orderby'                => $orderby,
            'order'                  => $order,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        ];

        if (!empty($category)) {
            if (is_numeric($category)) {
                $term = get_term(absint($category), 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $args['category'] = [$term->slug];
                }
            } else {
                $args['category'] = [$category];
            }
        }

        if ($only_in_stock) {
            $args['stock_status'] = 'instock';
        }

        $branch_id = class_exists('\RMM\Branches\BranchContext') ? \RMM\Branches\BranchContext::get_active_branch_id() : 0;
        $render_settings = [
            'card_style_type'  => $card_layout,
            'only_in_stock'    => $only_in_stock ? 'yes' : 'no',
            'details_btn_icon' => $details_btn_icon,
            'order_btn_icon'   => $order_btn_icon,
        ];

        $html = '';
        $current_page = $page;
        $max_pages = 1;
        $attempts = 0;
        $max_attempts = 10;

        while ($attempts < $max_attempts && $current_page <= 50) {
            $attempts++;
            $args['page'] = $current_page;
            $results = function_exists('wc_get_products') ? wc_get_products($args) : null;
            if (!$results || empty($results->products)) {
                $max_pages = $current_page;
                break;
            }

            $products  = $results->products;
            $max_pages = min(50, (int) ($results->max_num_pages ?? 1));

            $branch_inventory = [];
            if ($branch_id && class_exists('\RMM\Branches\InventoryRepository')) {
                $product_ids = array_map(function ($p) { return $p->get_id(); }, $products);
                $branch_inventory = \RMM\Branches\InventoryRepository::get_bulk_branch_inventory($branch_id, $product_ids);
            }

            ob_start();
            foreach ($products as $product) {
                self::render_card_html($product, $render_settings, $branch_id, $branch_inventory);
            }
            $html .= ob_get_clean();

            if (trim($html) !== '' || $current_page >= $max_pages) {
                break;
            }
            $current_page++;
        }

        wp_send_json_success([
            'html'      => $html,
            'has_more'  => (trim($html) !== '' && $current_page < $max_pages),
            'page'      => $current_page,
            'max_pages' => $max_pages,
        ]);
    }
}
