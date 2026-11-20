<?php
namespace RMM\Menu;

if (!defined('ABSPATH')) {
    exit;
}

class HappyHour {

    public function __construct() {
        add_filter('woocommerce_product_get_price', [$this, 'apply_happy_hour_price'], 15, 2);
        add_filter('woocommerce_product_get_sale_price', [$this, 'apply_happy_hour_price'], 15, 2);
    }

    /**
     * Check if Happy Hour is currently active
     */
    public static function is_active() {
        $settings = get_option('rmm_settings', []);
        if (empty($settings['enable_happy_hour']) || $settings['enable_happy_hour'] !== 'yes') {
            return false;
        }

        $hh = get_option('rmm_happy_hour_rules', [
            'enabled'    => false,
            'discount'   => 15, // percent
            'start_time' => '17:00',
            'end_time'   => '20:00',
            'days'       => ['1', '2', '3', '4', '5', '6', '7'],
        ]);

        if (empty($hh['enabled'])) {
            return false;
        }

        // Check day of week
        $current_day = date('N'); // 1 (Monday) through 7 (Sunday)
        if (!empty($hh['days']) && !in_array($current_day, (array)$hh['days'])) {
            return false;
        }

        // Check time of day
        $current_time = current_time('H:i');
        $start = $hh['start_time'] ?? '17:00';
        $end   = $hh['end_time'] ?? '20:00';

        if ($current_time >= $start && $current_time <= $end) {
            return true;
        }

        return false;
    }

    /**
     * Get happy hour discount percentage
     */
    public static function get_discount_percentage() {
        $hh = get_option('rmm_happy_hour_rules', ['discount' => 15]);
        return floatval($hh['discount'] ?? 15);
    }

    /**
     * Filter price if happy hour is running
     */
    public function apply_happy_hour_price($price, $product) {
        if (!self::is_active()) {
            return $price;
        }

        if (is_admin() && !wp_doing_ajax()) {
            return $price;
        }

        $discount_pct = self::get_discount_percentage();
        if ($discount_pct > 0 && floatval($price) > 0) {
            $discount_amount = ($price * $discount_pct) / 100;
            return round($price - $discount_amount, 2);
        }

        return $price;
    }
}
