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
}
