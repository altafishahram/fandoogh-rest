<?php
namespace AdminCafe\Core;

defined('ABSPATH') || exit;

/** Monetary amounts always belong to WooCommerce; this adds the Toman currency. */
final class Currency
{
    public function register(): void
    {
        add_filter('woocommerce_currencies', static function (array $currencies): array {
            $currencies['IRT'] = __('Toman', 'admincafe');
            return $currencies;
        });
        add_filter('woocommerce_currency_symbol', static function (string $symbol, string $code): string {
            return $code === 'IRT' ? __('Toman', 'admincafe') : $symbol;
        }, 10, 2);
    }
}
