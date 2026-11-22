<?php
namespace RMM\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class BranchSelectorWidget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'rmm_branch_selector';
    }

    public function get_title() {
        return __('انتخابگر شعبه و هدر رستوران', 'fondogh-online-menu');
    }

    public function get_icon() {
        return 'eicon-map-pin';
    }

    public function get_categories() {
        return ['tastymenu-category'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('تنظیمات هدر و شعبه', 'fondogh-online-menu'),
            ]
        );

        $this->add_control(
            'show_status',
            [
                'label'        => __('نمایش وضعیت باز/بسته بودن شعبه', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_table',
            [
                'label'        => __('نمایش شماره میز (در حالت سفارش حضوری)', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_lang',
            [
                'label'        => __('نمایش سوئیچر زبان (فارسی / English)', 'fondogh-online-menu'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE TAB ==================== //

        // 1. Container Style
        $this->start_controls_section(
            'section_style_header_container',
            [
                'label' => __('استایل کادر هدر', 'fondogh-online-menu'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'header_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه هدر', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-branch-header-bar' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'header_border',
                'selector' => '{{WRAPPER}} .rmm-branch-header-bar',
            ]
        );

        $this->add_control(
            'header_border_radius',
            [
                'label'      => __('گردی کادر هدر', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-branch-header-bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'header_box_shadow',
                'selector' => '{{WRAPPER}} .rmm-branch-header-bar',
            ]
        );

        $this->add_control(
            'header_padding',
            [
                'label'      => __('فاصله داخلی (Padding)', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-branch-header-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 2. Branch Dropdown & Picker Style
        $this->start_controls_section(
            'section_style_branch_picker',
            [
                'label' => __('استایل انتخابگر شعبه', 'fondogh-online-menu'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'picker_bg_color',
            [
                'label'     => __('پس‌زمینه باکس انتخاب شعبه', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-branch-picker' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'picker_text_color',
            [
                'label'     => __('رنگ متن نام شعبه', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-branch-dropdown, {{WRAPPER}} .rmm-single-branch-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'picker_typography',
                'label'    => __('تایپوگرافی نام شعبه', 'fondogh-online-menu'),
                'selector' => '{{WRAPPER}} .rmm-branch-dropdown, {{WRAPPER}} .rmm-single-branch-name',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'picker_border',
                'selector' => '{{WRAPPER}} .rmm-branch-picker',
            ]
        );

        $this->add_control(
            'picker_border_radius',
            [
                'label'      => __('گردی باکس شعبه', 'fondogh-online-menu'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-branch-picker' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // 3. Status Pill & Table Indicator
        $this->start_controls_section(
            'section_style_status_table',
            [
                'label' => __('استایل وضعیت شعبه و شماره میز', 'fondogh-online-menu'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'table_badge_heading',
            [
                'label'     => __('نشان شماره میز', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'table_bg_color',
            [
                'label'     => __('پس‌زمینه شماره میز', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-table-indicator' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'table_text_color',
            [
                'label'     => __('رنگ متن شماره میز', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-table-indicator' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'status_open_heading',
            [
                'label'     => __('نشان وضعیت شعبه (شعبه باز)', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'status_open_bg',
            [
                'label'     => __('پس‌زمینه شعبه باز', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-status-pill.open' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'status_open_color',
            [
                'label'     => __('رنگ متن شعبه باز', 'fondogh-online-menu'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .rmm-status-pill.open' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        $branches = get_terms([
            'taxonomy'   => \RMM\Branches\BranchTaxonomy::TAXONOMY,
            'hide_empty' => false,
        ]);

        $active_branch_id = \RMM\Branches\BranchContext::get_active_branch_id();
        $is_open = true;
        $branch_phone = '';
        $branch_hours = '';

        if ($active_branch_id) {
            $is_open = get_term_meta($active_branch_id, '_rmm_is_open', true) !== '0';
            $branch_phone = get_term_meta($active_branch_id, '_rmm_phone', true);
            $branch_hours = get_term_meta($active_branch_id, '_rmm_hours', true);
        }

        $table_no = \RMM\OperationModes\ModeManager::get_current_table();
        ?>
        <div class="rmm-branch-header-bar">
            <div class="rmm-branch-bar-inner">
                <!-- Branch Selector Dropdown -->
                <?php if (!empty($branches) && count($branches) > 1) : ?>
                    <div class="rmm-branch-picker">
                        <span class="rmm-branch-icon">📍</span>
                        <select class="rmm-branch-dropdown" onchange="if(this.value){ window.location.href = this.value; }">
                            <?php foreach ($branches as $branch) : ?>
                                <?php
                                $url = add_query_arg('branch', $branch->term_id, remove_query_arg('branch'));
                                ?>
                                <option value="<?php echo esc_url($url); ?>" <?php selected($branch->term_id, $active_branch_id); ?>>
                                    <?php echo esc_html($branch->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif (!empty($branches)) : ?>
                    <div class="rmm-single-branch-name">
                        📍 <strong><?php echo esc_html($branches[0]->name); ?></strong>
                    </div>
                <?php endif; ?>

                <!-- Table Dine-in Badge -->
                <?php if ($settings['show_table'] === 'yes' && $table_no > 0) : ?>
                    <div class="rmm-table-indicator">
                        🍽️ <?php printf(esc_html__('میز شماره %d', 'fondogh-online-menu'), $table_no); ?>
                    </div>
                <?php endif; ?>

                <!-- Open/Closed Status -->
                <?php if ($settings['show_status'] === 'yes') : ?>
                    <div class="rmm-status-pill <?php echo $is_open ? 'open' : 'closed'; ?>">
                        <span class="rmm-dot"></span>
                        <?php echo $is_open ? esc_html__('سفارش‌گیری باز است', 'fondogh-online-menu') : esc_html__('در حال حاضر بسته است', 'fondogh-online-menu'); ?>
                        <?php if ($branch_hours) : ?>
                            <span class="rmm-hours-text">(<?php echo esc_html($branch_hours); ?>)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Language Switcher -->
                <?php if ($settings['show_lang'] === 'yes') : ?>
                    <div class="rmm-lang-box">
                        <?php echo \RMM\I18n\MultiLangManager::render_language_switcher(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
