<?php
namespace RMM\Elementor\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

class LanguageSwitcherWidget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'rmm_language_switcher';
    }

    public function get_title() {
        return __('انتخاب زبان منو (Language Switcher)', 'restaurant-menu-manager');
    }

    public function get_icon() {
        return 'eicon-globe';
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

    protected function register_controls() {
        // ==================== CONTENT TAB ==================== //
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('تنظیمات انتخاب زبان', 'restaurant-menu-manager'),
            ]
        );

        $this->add_control(
            'display_mode',
            [
                'label'   => __('حالت نمایش', 'restaurant-menu-manager'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'dropdown',
                'options' => [
                    'dropdown' => __('دراپ‌دان شناور (Dropdown Modal)', 'restaurant-menu-manager'),
                    'pills'    => __('دکمه‌های کنار هم (Pills)', 'restaurant-menu-manager'),
                ],
            ]
        );

        $this->add_control(
            'show_globe_icon',
            [
                'label'        => __('نمایش آیکون کره زمین (Globe)', 'restaurant-menu-manager'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_flag',
            [
                'label'        => __('نمایش پرچم کشورها', 'restaurant-menu-manager'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'no',
                'return_value' => 'yes',
            ]
        );

        $this->add_control(
            'show_arrow',
            [
                'label'        => __('نمایش فلش دراپ‌دان (Chevron)', 'restaurant-menu-manager'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'default'      => 'yes',
                'return_value' => 'yes',
                'condition'    => [
                    'display_mode' => 'dropdown',
                ],
            ]
        );

        $this->add_control(
            'lang_name_format',
            [
                'label'   => __('فرمت نام زبان‌ها', 'restaurant-menu-manager'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'native',
                'options' => [
                    'native' => __('نام بومی (فارسی، English، Türkçe)', 'restaurant-menu-manager'),
                    'name'   => __('نام انگلیسی (Persian, English, Turkish)', 'restaurant-menu-manager'),
                    'code'   => __('کد کوتاه ۲ حرفی (FA, EN, TR)', 'restaurant-menu-manager'),
                ],
            ]
        );

        $this->add_responsive_control(
            'align',
            [
                'label'     => __('تراز ویجت', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [
                        'title' => __('چپ', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('وسط', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right'  => [
                        'title' => __('راست', 'restaurant-menu-manager'),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'default'   => 'right',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-widget-wrap' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE TAB: TRIGGER BUTTON ==================== //
        $this->start_controls_section(
            'section_style_button',
            [
                'label' => __('استایل دکمه بازکننده (Trigger Button)', 'restaurant-menu-manager'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'button_typography',
                'label'    => __('تایپوگرافی دکمه', 'restaurant-menu-manager'),
                'selector' => '{{WRAPPER}} .rmm-lang-trigger-btn',
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label'     => __('رنگ متن دکمه', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-trigger-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه دکمه', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#8b1e28',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-trigger-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'button_border',
                'selector' => '{{WRAPPER}} .rmm-lang-trigger-btn',
            ]
        );

        $this->add_responsive_control(
            'button_border_radius',
            [
                'label'      => __('گردی لبه (Border Radius)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
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
                    '{{WRAPPER}} .rmm-lang-trigger-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label'      => __('فاصله داخلی (Padding)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'      => 8,
                    'right'    => 18,
                    'bottom'   => 8,
                    'left'     => 18,
                    'unit'     => 'px',
                    'isLinked' => false,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-lang-trigger-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'button_shadow',
                'selector' => '{{WRAPPER}} .rmm-lang-trigger-btn',
            ]
        );

        $this->add_control(
            'icon_size',
            [
                'label'      => __('اندازه آیکون کره زمین / پرچم', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
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
                    '{{WRAPPER}} .rmm-lang-icon' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label'     => __('رنگ آیکون کره زمین', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#eab308',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-icon-globe' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // ==================== STYLE TAB: DROPDOWN MENU ==================== //
        $this->start_controls_section(
            'section_style_dropdown',
            [
                'label'     => __('استایل باکس منوی دراپ‌دان (Dropdown Box)', 'restaurant-menu-manager'),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'display_mode' => 'dropdown',
                ],
            ]
        );

        $this->add_control(
            'dropdown_bg_color',
            [
                'label'     => __('رنگ پس‌زمینه منو', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-dropdown-panel' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'dropdown_width',
            [
                'label'      => __('حداقل عرض باکس منو (Min-Width)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 120,
                        'max' => 300,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 160,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .rmm-lang-dropdown-panel' => 'min-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'dropdown_border',
                'selector' => '{{WRAPPER}} .rmm-lang-dropdown-panel',
            ]
        );

        $this->add_responsive_control(
            'dropdown_border_radius',
            [
                'label'      => __('گردی لبه منو (Border Radius)', 'restaurant-menu-manager'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
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
                    '{{WRAPPER}} .rmm-lang-dropdown-panel' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'dropdown_shadow',
                'selector' => '{{WRAPPER}} .rmm-lang-dropdown-panel',
            ]
        );

        // Header / Active Language Item in Dropdown
        $this->add_control(
            'active_item_heading',
            [
                'label'     => __('استایل زبان فعال در بالای منو', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'active_item_bg',
            [
                'label'     => __('رنگ پس‌زمینه زبان فعال', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#fef3c7',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-dropdown-item.is-active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'active_item_color',
            [
                'label'     => __('رنگ متن زبان فعال', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#0f172a',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-dropdown-item.is-active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'active_checkmark_color',
            [
                'label'     => __('رنگ تیک انتخاب (✓)', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#991b1b',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-check-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        // Inactive Language Items
        $this->add_control(
            'inactive_item_heading',
            [
                'label'     => __('استایل سایر زبان‌ها در منو', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'item_text_color',
            [
                'label'     => __('رنگ متن زبان‌ها', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#334155',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-dropdown-item:not(.is-active)' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_hover_bg',
            [
                'label'     => __('رنگ پس‌زمینه هاور', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#f8fafc',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-dropdown-item:not(.is-active):hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'item_hover_color',
            [
                'label'     => __('رنگ متن هاور', 'restaurant-menu-manager'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#0284c7',
                'selectors' => [
                    '{{WRAPPER}} .rmm-lang-dropdown-item:not(.is-active):hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        if (!class_exists('\RMM\I18n\MultiLangManager')) {
            return;
        }

        $enabled_langs = \RMM\I18n\MultiLangManager::get_enabled_languages();
        if (count($enabled_langs) < 2) {
            return;
        }

        $current_lang = \RMM\I18n\MultiLangManager::get_current_language();
        $curr_data = $enabled_langs[$current_lang] ?? $enabled_langs['fa'] ?? reset($enabled_langs);

        $format = $settings['lang_name_format'] ?? 'native';
        $curr_label = ($format === 'code') ? strtoupper($current_lang) : (($format === 'name') ? $curr_data['name'] : $curr_data['native']);

        $show_globe = ($settings['show_globe_icon'] === 'yes');
        $show_flag  = ($settings['show_flag'] === 'yes');
        $show_arrow = ($settings['show_arrow'] === 'yes');
        $mode       = $settings['display_mode'] ?? 'dropdown';

        // Enqueue frontend scripts & styles
        wp_enqueue_style('rmm-frontend-menu');
        wp_enqueue_script('rmm-frontend-menu');
        ?>
        <div class="rmm-lang-widget-wrap">
            <?php if ($mode === 'dropdown') : ?>
                <div class="rmm-lang-dropdown-component" data-current="<?php echo esc_attr($current_lang); ?>">
                    <button type="button" class="rmm-lang-trigger-btn" aria-haspopup="true" aria-expanded="false" title="<?php esc_attr_e('انتخاب زبان منو', 'restaurant-menu-manager'); ?>">
                        <span class="rmm-lang-btn-text"><?php echo esc_html($curr_label); ?></span>

                        <?php if ($show_flag) : ?>
                            <span class="rmm-lang-icon rmm-lang-flag-icon"><?php echo esc_html($curr_data['flag']); ?></span>
                        <?php endif; ?>

                        <?php if ($show_globe) : ?>
                            <span class="rmm-lang-icon rmm-lang-icon-globe">🌐</span>
                        <?php endif; ?>

                        <?php if ($show_arrow) : ?>
                            <span class="rmm-lang-chevron">▾</span>
                        <?php endif; ?>
                    </button>

                    <div class="rmm-lang-dropdown-panel" role="menu">
                        <!-- Active Language Item (Pinned at top like screenshot) -->
                        <div class="rmm-lang-dropdown-item is-active" role="menuitem" data-lang="<?php echo esc_attr($current_lang); ?>">
                            <span class="rmm-lang-check-icon">✓</span>
                            <?php if ($show_flag) : ?>
                                <span class="rmm-lang-flag-mini"><?php echo esc_html($curr_data['flag']); ?></span>
                            <?php endif; ?>
                            <span class="rmm-lang-item-label"><?php echo esc_html($curr_label); ?></span>
                        </div>

                        <!-- Other Available Languages -->
                        <?php foreach ($enabled_langs as $code => $data) : ?>
                            <?php
                            if ($code === $current_lang) continue;
                            $label = ($format === 'code') ? strtoupper($code) : (($format === 'name') ? $data['name'] : $data['native']);
                            $target_url = add_query_arg('lang', $code);
                            ?>
                            <a href="<?php echo esc_url($target_url); ?>" class="rmm-lang-dropdown-item" role="menuitem" data-lang="<?php echo esc_attr($code); ?>">
                                <?php if ($show_flag) : ?>
                                    <span class="rmm-lang-flag-mini"><?php echo esc_html($data['flag']); ?></span>
                                <?php endif; ?>
                                <span class="rmm-lang-item-label"><?php echo esc_html($label); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <script>
                (function() {
                    function initRmmLangSwitcher() {
                        var buttons = document.querySelectorAll('.rmm-lang-trigger-btn');
                        buttons.forEach(function(btn) {
                            if (btn.dataset.rmmBound) return;
                            btn.dataset.rmmBound = 'true';
                            btn.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                var comp = btn.closest('.rmm-lang-dropdown-component');
                                if (!comp) return;
                                var isOpen = comp.classList.toggle('is-open');
                                btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                                document.querySelectorAll('.rmm-lang-dropdown-component.is-open').forEach(function(other) {
                                    if (other !== comp) {
                                        other.classList.remove('is-open');
                                        var otherBtn = other.querySelector('.rmm-lang-trigger-btn');
                                        if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                                    }
                                });
                            });
                        });
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initRmmLangSwitcher);
                    } else {
                        initRmmLangSwitcher();
                    }

                    if (!window.__rmmLangGlobalBound) {
                        window.__rmmLangGlobalBound = true;
                        document.addEventListener('click', function(e) {
                            if (!e.target.closest('.rmm-lang-dropdown-component')) {
                                document.querySelectorAll('.rmm-lang-dropdown-component.is-open').forEach(function(c) {
                                    c.classList.remove('is-open');
                                    var b = c.querySelector('.rmm-lang-trigger-btn');
                                    if (b) b.setAttribute('aria-expanded', 'false');
                                });
                            }
                        });
                        document.addEventListener('keydown', function(e) {
                            if (e.key === 'Escape' || e.keyCode === 27) {
                                document.querySelectorAll('.rmm-lang-dropdown-component.is-open').forEach(function(c) {
                                    c.classList.remove('is-open');
                                    var b = c.querySelector('.rmm-lang-trigger-btn');
                                    if (b) b.setAttribute('aria-expanded', 'false');
                                });
                            }
                        });
                    }
                })();
                </script>
            <?php else : ?>
                <!-- Horizontal Pills Mode -->
                <div class="rmm-lang-pills-component">
                    <?php foreach ($enabled_langs as $code => $data) : ?>
                        <?php
                        $isActive = ($code === $current_lang) ? 'is-active' : '';
                        $label = ($format === 'code') ? strtoupper($code) : (($format === 'name') ? $data['name'] : $data['native']);
                        $target_url = add_query_arg('lang', $code);
                        ?>
                        <a href="<?php echo esc_url($target_url); ?>" class="rmm-lang-pill-item <?php echo esc_attr($isActive); ?>" data-lang="<?php echo esc_attr($code); ?>">
                            <?php if ($show_flag) : ?>
                                <span class="rmm-lang-flag-mini"><?php echo esc_html($data['flag']); ?></span>
                            <?php endif; ?>
                            <span><?php echo esc_html($label); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
