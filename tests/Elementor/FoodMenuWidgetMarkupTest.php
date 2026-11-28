<?php

use PHPUnit\Framework\TestCase;

class FoodMenuWidgetMarkupTest extends TestCase {

    public function test_widget_does_not_emit_a_duplicate_global_modifiers_modal() {
        $source = file_get_contents(dirname(__DIR__, 2) . '/includes/Elementor/Widgets/FoodMenuWidget.php');

        $this->assertNotFalse($source);
        $this->assertStringNotContainsString('id="rmm-modifiers-modal"', $source);
        $this->assertStringNotContainsString('id="rmm-modal-content"', $source);
    }

    public function test_theme_toggle_uses_a_reusable_class_not_a_duplicate_id() {
        $source = file_get_contents(dirname(__DIR__, 2) . '/includes/Elementor/Widgets/FoodMenuWidget.php');

        $this->assertNotFalse($source);
        $this->assertStringNotContainsString('id="theme-toggle-btn"', $source);
        $this->assertStringContainsString('class="rmm-theme-toggle-btn"', $source);
    }

    public function test_live_view_switcher_updates_every_category_container() {
        $source = file_get_contents(dirname(__DIR__, 2) . '/assets/js/frontend-menu.js');

        $this->assertNotFalse($source);
        $this->assertStringContainsString('$containers.each(function ()', $source);
        $this->assertStringNotContainsString(".find('.rmm-menu-container').first()", $source);
    }

    public function test_modal_quantity_controls_reuse_catalog_list_stepper_classes() {
        $source = file_get_contents(dirname(__DIR__, 2) . '/assets/js/frontend-menu.js');

        $this->assertNotFalse($source);
        $this->assertStringContainsString('rmm-qty-controls rmm-catalog-item-qty', $source);
        $this->assertStringContainsString('rmm-cat-qty-btn rmm-cat-qty-plus', $source);
        $this->assertStringContainsString('rmm-qty-val rmm-cat-qty-num', $source);
    }

    public function test_admin_settings_have_mobile_overflow_guards() {
        $source = file_get_contents(dirname(__DIR__, 2) . '/assets/css/admin-ui.css');

        $this->assertNotFalse($source);
        $this->assertStringContainsString('@media (max-width: 782px)', $source);
        $this->assertStringContainsString('min-width: 0;', $source);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $source);
    }
}
