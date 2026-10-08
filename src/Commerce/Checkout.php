<?php

namespace AdminCafe\Commerce;

use AdminCafe\Core\Settings;
use AdminCafe\Menu\Catalog;
use AdminCafe\Localization\Language;

final class Checkout
{
    public function register(): void
    {
        add_filter('woocommerce_cart_needs_shipping', [$this, 'needsShipping'], 100);
        add_filter('woocommerce_cart_needs_shipping_address', [$this, 'needsShipping'], 100);
        add_action('woocommerce_after_checkout_validation', [$this, 'classicValidation'], 10, 2);
        add_action('woocommerce_checkout_create_order', [$this, 'decorate'], 10, 2);
        add_action('woocommerce_checkout_order_processed', [$this, 'placed'], 20, 1);
        add_action('woocommerce_store_api_checkout_update_order_from_request', [$this, 'storeValidation'], 10, 2);
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'storePlaced'], 20, 1);
        add_action('woocommerce_payment_complete', [$this, 'paid'], 20, 1);
        add_filter('woocommerce_cart_item_name', [$this, 'cartItemName'], 20, 3);
        add_filter('woocommerce_order_item_name', [$this, 'orderItemName'], 20, 3);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'lineItem'], 20, 4);
        add_filter('rest_post_dispatch', [$this, 'storeNames'], 20, 3);
        // Blocks hydrate their initial cart through internal REST dispatch, bypassing post_dispatch.
        add_filter('rest_request_after_callbacks', [$this, 'storeNames'], 20, 3);
        // Woo 8.9+ invokes controllers directly for Blocks preload and exposes this matching hook.
        add_filter('woocommerce_hydration_request_after_callbacks', [$this, 'storeNames'], 20, 3);
        add_filter('woocommerce_get_item_data', [$this, 'cartItemData'], 20, 2);
        add_filter('woocommerce_order_item_get_formatted_meta_data', [$this, 'orderItemMeta'], 20, 2);
    }

    public static function channel(): string
    {
        return function_exists('WC') && WC()->session ? (string) WC()->session->get('admincafe_channel', '') : '';
    }

    public static function language(mixed $value = null): string|\WP_Error
    {
        if ($value !== null && (!is_string($value) || !in_array($value, Language::enabled(), true))) {
            return new \WP_Error('language', __('Unsupported customer language.', 'admincafe'), ['status' => 400]);
        }
        return Language::resolve($value);
    }

    public static function choose(string $channel, mixed $language = null): array|\WP_Error
    {
        if (!in_array($channel, ['pickup', 'delivery'], true)) {
            return new \WP_Error('channel', __('Invalid checkout channel.', 'admincafe'), ['status' => 400]);
        }
        $error = self::allowed($channel);
        if (is_wp_error($error)) {
            return $error;
        }
        if (!WC()->session) {
            wc_load_cart();
        }
        $language = self::language($language);
        if (is_wp_error($language)) {
            return $language;
        }
        Language::remember($language);
        WC()->session->set('admincafe_channel', $channel);
        WC()->session->set_customer_session_cookie(true);
        if (WC()->cart) {
            WC()->cart->calculate_shipping();
            WC()->cart->calculate_totals();
        }
        return ['url' => add_query_arg('lang', $language, wc_get_checkout_url())];
    }

    public static function allowed(string $channel): bool|\WP_Error
    {
        if ($channel === '') {
            return true;
        }
        if (!in_array($channel, ['pickup', 'delivery'], true) || Settings::get('ordering_paused') || !Settings::get($channel . '_enabled')) {
            return new \WP_Error('channel_disabled', __('This ordering channel is unavailable.', 'admincafe'), ['status' => 403]);
        }
        return true;
    }

    public function needsShipping(bool $needs): bool
    {
        return self::channel() === 'pickup' ? false : $needs;
    }

    public static function cartError(): bool|\WP_Error
    {
        $channel = self::channel();
        $allowed = self::allowed($channel);
        if (is_wp_error($allowed)) {
            return $allowed;
        }
        if (!WC()->cart) {
            return true;
        }
        if (!$channel) {
            $visible = array_column(Catalog::menu()['products'], 'id');
            foreach (WC()->cart->get_cart() as $item) {
                if (in_array((int) $item['product_id'], $visible, true)) {
                    return new \WP_Error(
                        'channel_required',
                        __('Choose pickup or delivery from the restaurant menu before checkout.', 'admincafe'),
                        ['status' => 403]
                    );
                }
            }
            return true;
        }
        $items = [];
        foreach (WC()->cart->get_cart() as $item) {
            $items[] = [
                'product_id' => $item['product_id'],
                'variation_id' => $item['variation_id'],
                'quantity' => $item['quantity']
            ];
        }
        $validated = Orders::validateItems($items);
        return is_wp_error($validated) ? $validated : true;
    }

    public function classicValidation(array $data, \WP_Error $errors): void
    {
        $error = self::cartError();
        if (is_wp_error($error)) {
            $errors->add($error->get_error_code(), $error->get_error_message());
        }
    }

    public function decorate(\WC_Order $order, array $data = []): void
    {
        $channel = self::channel();
        if (!$channel) {
            return;
        }
        $order->update_meta_data('_admincafe_channel', $channel);
        $order->update_meta_data('_admincafe_stage', 'awaiting_approval');
        $language = Language::resolve();
        $order->update_meta_data('_admincafe_language', $language);
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                Orders::snapshotItem($item, $product, $language);
            }
        }
    }

    public function lineItem(\WC_Order_Item_Product $item, string $key, array $values, \WC_Order $order): void
    {
        if (self::channel() && isset($values['data'])) {
            Orders::snapshotItem($item, $values['data'], Language::resolve());
        }
    }

    public function cartItemName(string $name, array $item, string $key): string
    {
        if (!Language::isCustomerRequest() || empty($item['data'])) {
            return $name;
        }
        return esc_html(Catalog::localizedName($item['data'], Language::resolve()));
    }

    public function orderItemName(string $name, $item, bool $visible = false): string
    {
        if (!Language::isCustomerRequest() || !$item instanceof \WC_Order_Item_Product) {
            return $name;
        }
        $snapshot = $item->get_meta('_admincafe_localized_name');
        return $snapshot !== '' ? esc_html($snapshot) : $name;
    }

    /** Full translated variant names already communicate the choice; retain all custom metadata. */
    public static function translatedVariant(\WC_Product $product, string $language): bool
    {
        if ($language === 'fa' || !$product->is_type('variation')) {
            return false;
        }
        $translations = $product->get_meta('_admincafe_translations');
        if (
            !is_array($translations)
            || !is_string($translations[$language]['name'] ?? null)
            || trim($translations[$language]['name']) === ''
            || Catalog::localizedName($product, $language) === $product->get_name()
        ) {
            return false;
        }
        return in_array($product->get_parent_id(), array_column(Catalog::menu('fa')['products'], 'id'), true);
    }

    public function cartItemData(array $data, array $cartItem): array
    {
        $product = $cartItem['data'] ?? null;
        if (!Language::isCustomerRequest() || !$product instanceof \WC_Product || !self::translatedVariant($product, Language::resolve())) {
            return $data;
        }
        foreach ($product->get_attributes() as $attribute => $value) {
            $label = wc_attribute_label($attribute, $product);
            if (taxonomy_exists($attribute)) {
                $term = get_term_by('slug', $value, $attribute);
                if ($term && !is_wp_error($term)) {
                    $value = $term->name;
                }
            }
            foreach ($data as $key => $row) {
                if (($row['key'] ?? $row['name'] ?? '') === $label && (string) ($row['value'] ?? '') === (string) $value) {
                    unset($data[$key]);
                }
            }
        }
        return array_values($data);
    }

    public function orderItemMeta(array $metadata, $item): array
    {
        if (!Language::isCustomerRequest() || !$item instanceof \WC_Order_Item_Product) {
            return $metadata;
        }
        $keys = $item->get_meta('_admincafe_variant_keys');
        if (!is_array($keys)) {
            return $metadata;
        }
        foreach ($metadata as $id => $meta) {
            if (in_array($meta->key, $keys, true) || in_array(preg_replace('/^attribute_/', '', $meta->key), $keys, true)) {
                unset($metadata[$id]);
            }
        }
        return $metadata;
    }

    /** Translate customer Store API display fields without mutating products, IDs or money. */
    public function storeNames($response, $server, \WP_REST_Request $request)
    {
        if (
            !Language::isCustomerRequest()
            || !preg_match('#^/wc/store/v[0-9]+/(?:cart(?:/|$)|order(?:/|$))#', $request->get_route())
            || !$response instanceof \WP_REST_Response
        ) {
            return $response;
        }
        $data = $response->get_data();
        if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
            return $response;
        }
        $visible = array_column(Catalog::menu('fa')['products'], 'id');
        foreach ($data['items'] as &$item) {
            if (preg_match('#^/wc/store/v[0-9]+/order(?:/|$)#', $request->get_route())) {
                $line = new \WC_Order_Item_Product(absint($item['id'] ?? 0));
                $snapshot = $line->get_meta('_admincafe_localized_name');
                if ($snapshot !== '' && isset($item['name'])) {
                    $item['name'] = $snapshot;
                    foreach (['description', 'short_description'] as $field) {
                        $snapshotField = '_admincafe_localized_' . $field;
                        if ($line->meta_exists($snapshotField) && isset($item[$field])) {
                            $item[$field] = wp_kses_post(wc_format_content($line->get_meta($snapshotField)));
                        }
                    }
                    if (is_array($line->get_meta('_admincafe_variant_keys')) && isset($item['variation'])) {
                        $item['variation'] = [];
                    }
                }
                continue;
            }
            $product = wc_get_product(absint($item['id'] ?? 0));
            if (
                $product
                && in_array($product->is_type('variation') ? $product->get_parent_id() : $product->get_id(), $visible, true)
                && isset($item['name'])
            ) {
                $item['name'] = Catalog::localizedName($product, Language::resolve());
                foreach (['description', 'short_description'] as $field) {
                    if (isset($item[$field])) {
                        $item[$field] = wp_kses_post(wc_format_content(Catalog::localizedText($product, $field, Language::resolve())));
                    }
                }
                if (self::translatedVariant($product, Language::resolve()) && isset($item['variation'])) {
                    $item['variation'] = [];
                }
            }
        }
        unset($item);
        $response->set_data($data);
        return $response;
    }

    public function storeValidation(\WC_Order $order, \WP_REST_Request $request): void
    {
        $error = self::cartError();
        if (is_wp_error($error)) {
            throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException($error->get_error_code(), $error->get_error_message(), 403);
        }
        $this->decorate($order);
    }

    public function placed(int $id): void
    {
        $order = wc_get_order($id);
        if (!$order || !$order->get_meta('_admincafe_channel') || $order->get_status() === 'checkout-draft' || $order->get_meta('_admincafe_created_event')) {
            return;
        }
        $order->update_meta_data('_admincafe_created_event', true);
        $order->save();
        if (WC()->session) {
            WC()->session->set('admincafe_channel', null);
        }
        try {
            do_action('admincafe_order_created', $id);
        } catch (\Throwable $error) {
            wc_get_logger()->error(__('AdminCafe checkout notification failed.', 'admincafe'), ['source' => 'admincafe']);
        }
    }

    public function storePlaced(\WC_Order $order): void
    {
        $this->placed($order->get_id());
    }
    public function paid(int $id): void
    {
        $order = wc_get_order($id);
        if ($order && $order->get_meta('_admincafe_channel')) {
            try {
                do_action('admincafe_order_paid', $id);
            } catch (\Throwable $error) {
                wc_get_logger()->error(__('AdminCafe payment notification failed.', 'admincafe'), ['source' => 'admincafe']);
            }
        }
    }
}
