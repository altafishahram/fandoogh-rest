<?php

namespace AdminCafe\Commerce;

use AdminCafe\Core\Settings;
use AdminCafe\Core\Security;
use AdminCafe\Menu\Catalog;
use AdminCafe\Tables\Tables;
use AdminCafe\Localization\Language;

final class Orders
{
    public function register(): void
    {
    }

    public static function transitions(): array
    {
        return [
            'awaiting_approval' => ['accepted', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['delivered', 'cancelled'],
            'delivered' => [],
            'cancelled' => []
        ];
    }

    public static function validateItems(array $items): array|\WP_Error
    {
        if (!$items || count($items) > 100) {
            return new \WP_Error('items', __('Choose between 1 and 100 items.', 'admincafe'), ['status' => 400]);
        }
        $menu = Catalog::menu();
        $visible = array_column($menu['products'], 'id');
        $result = [];
        $counts = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                return new \WP_Error('items', __('Invalid item.', 'admincafe'), ['status' => 400]);
            }
            $id = absint($item['product_id'] ?? 0);
            $variation = absint($item['variation_id'] ?? 0);
            $quantity = $item['quantity'] ?? 0;
            if (!is_numeric($quantity) || (int) $quantity != $quantity || $quantity < 1 || $quantity > 99 || !in_array($id, $visible, true)) {
                return new \WP_Error('items', __('Invalid product or quantity.', 'admincafe'), ['status' => 400]);
            }
            $product = wc_get_product($variation ?: $id);
            if (
                !$product
                || ($variation && (!$product->is_type('variation') || $product->get_parent_id() !== $id))
                || (!$variation && !$product->is_type('simple'))
                || !$product->is_purchasable()
                || !$product->is_in_stock()
                || $product->get_price() === ''
            ) {
                return new \WP_Error('unavailable', __('An item is unavailable.', 'admincafe'), ['status' => 409]);
            }
            $key = $product->get_id();
            $counts[$key] = ($counts[$key] ?? 0) + (int) $quantity;
            if ($counts[$key] > 99 || ($product->is_sold_individually() && $counts[$key] > 1) || !$product->has_enough_stock($counts[$key])) {
                return new \WP_Error('stock', __('Requested quantity is unavailable.', 'admincafe'), ['status' => 409]);
            }
            $result[] = ['product' => $product, 'quantity' => (int) $quantity];
        }
        return $result;
    }

    /** Persist a Woo order once per session/table request, recovering only confirmed writes. */
    public static function create(array $input, bool $manual = false): array|\WP_Error
    {
        $language = Checkout::language($input['language'] ?? null);
        if (is_wp_error($language)) {
            return $language;
        }
        if (!$manual) {
            Language::remember($language);
        }
        $table = null;
        if (!$manual) {
            if (Settings::get('ordering_paused') || !Settings::get('dine_in_enabled')) {
                return new \WP_Error('closed', __('Table ordering is unavailable.', 'admincafe'), ['status' => 403]);
            }
            $table = Tables::context(sanitize_text_field($input['table_token'] ?? ''));
            if (is_wp_error($table)) {
                return $table;
            }
            if (!$table || empty($table['can_order'])) {
                return new \WP_Error('table', __('This table supports menu viewing only.', 'admincafe'), ['status' => 403]);
            }
        }
        if (!isset($input['items']) || !is_array($input['items'])) {
            return new \WP_Error('items', __('An item list is required.', 'admincafe'), ['status' => 400]);
        }
        $request = sanitize_text_field($input['request_id'] ?? '');
        if (!$manual && !preg_match('/^[A-Za-z0-9_-]{16,100}$/D', $request)) {
            return new \WP_Error('request_id', __('A unique request ID is required.', 'admincafe'), ['status' => 400]);
        }
        $scope = hash('sha256', Security::sessionKey() . '|' . ($table['token'] ?? 'counter') . '|' . $request);
        $fingerprint = hash('sha256', wp_json_encode([
            $input['items'] ?? [],
            $input['name'] ?? '',
            $input['note'] ?? ''
        ]));
        $option = 'admincafe_request_' . $scope;
        if (!$manual) {
            $existing = get_option($option);
            if ($existing) {
                if ($existing['fingerprint'] !== $fingerprint) {
                    return new \WP_Error('request_conflict', __('Request ID already used for different items.', 'admincafe'), ['status' => 409]);
                }
                if (!empty($existing['order_id'])) {
                    $order = wc_get_order($existing['order_id']);
                    if ($order) {
                        return self::publicOrder($order, $existing['token']);
                    }
                }
                if ((int) ($existing['created'] ?? 0) < time() - 60) {
                    $recovered = wc_get_orders([
                        'limit' => 1,
                        'type' => 'shop_order',
                        'meta_query' => [['key' => '_admincafe_request_scope', 'value' => $scope]]
                    ]);
                    if (
                        $recovered
                        && $recovered[0]->get_meta('_admincafe_request_complete')
                        && $recovered[0]->get_meta('_admincafe_request_fingerprint') === $fingerprint
                        && !empty($existing['token'])
                        && hash_equals(
                            (string) $recovered[0]->get_meta('_admincafe_tracking_hash'),
                            hash('sha256', $existing['token'])
                        )
                    ) {
                        $order = $recovered[0];
                        update_option($option, [
                            'fingerprint' => $fingerprint,
                            'order_id' => $order->get_id(),
                            'token' => $existing['token'],
                            'created' => $existing['created']
                        ], false);
                        return self::publicOrder($order, $existing['token']);
                    }
                    // Do not recreate an uncertain request: a worker may have died after a gateway/storage write.
                    return new \WP_Error(
                        'request_recovery',
                        __('This request needs staff review before retrying. Please contact the restaurant.', 'admincafe'),
                        ['status' => 409]
                    );
                }
                return new \WP_Error('request_pending', __('This request is being processed.', 'admincafe'), ['status' => 409]);
            }
        }
        $items = self::validateItems($input['items']);
        if (is_wp_error($items)) {
            return $items;
        }
        $token = bin2hex(random_bytes(32));
        if (!$manual && !add_option($option, [
            'fingerprint' => $fingerprint,
            'token' => $token,
            'created' => time()
        ], '', false)) {
            return new \WP_Error('request_pending', __('This request is being processed.', 'admincafe'), ['status' => 409]);
        }
        $order = null;
        try {
            $order = new \WC_Order();
            $order->set_status('pending');
            $order->set_created_via('admincafe');
            $order->set_currency(get_woocommerce_currency());
            $order->set_prices_include_tax(wc_prices_include_tax());
            if (!$manual) {
                $order->update_meta_data('_admincafe_request_scope', $scope);
                $order->update_meta_data('_admincafe_request_fingerprint', $fingerprint);
            }
            foreach ($items as $item) {
                $itemId = $order->add_product($item['product'], $item['quantity']);
                $line = $order->get_item($itemId, false);
                if ($line) {
                    self::snapshotItem($line, $item['product'], $language);
                    $line->save();
                }
            }
            $order->update_meta_data('_admincafe_language', $language);
            $order->set_billing_first_name(sanitize_text_field($input['name'] ?? ''));
            if ($manual) {
                $order->set_billing_phone(sanitize_text_field($input['phone'] ?? ''));
            }
            $order->set_customer_note(sanitize_textarea_field($input['note'] ?? ''));
            $order->update_meta_data('_admincafe_channel', $manual ? 'counter' : 'table');
            $order->update_meta_data('_admincafe_stage', $manual ? 'accepted' : 'awaiting_approval');
            $order->update_meta_data('_admincafe_tracking_hash', hash('sha256', $token));
            if ($table) {
                $order->update_meta_data('_admincafe_table_token', $table['token']);
                $order->update_meta_data('_admincafe_table_label', $table['label']);
            }
            $order->calculate_totals();
            $order->save();
            // Woo's reservation table serializes concurrent stock claims.
            wc_reserve_stock_for_order($order);
            $order->set_status('on-hold');
            $order->save();
            wc_reduce_stock_levels($order->get_id());
            wc_release_stock_for_order($order);
            $order->update_meta_data('_admincafe_request_complete', true);
            $order->save();
            if (!$manual) {
                update_option($option, [
                    'fingerprint' => $fingerprint,
                    'order_id' => $order->get_id(),
                    'token' => $token,
                    'created' => time()
                ], false);
            }
        } catch (\Throwable $error) {
            if ($order instanceof \WC_Order) {
                wc_release_stock_for_order($order);
                wc_increase_stock_levels($order->get_id());
                $order->delete(true);
            }
            if (!$manual) {
                delete_option($option);
            }
            return new \WP_Error('order_failed', __('Unable to create the order. Please try again.', 'admincafe'), ['status' => 500]);
        }
        // Notification failures must never undo an already persisted customer order.
        try {
            do_action('admincafe_order_created', $order->get_id());
        } catch (\Throwable $error) {
            wc_get_logger()->error(__('AdminCafe order notification failed.', 'admincafe'), ['source' => 'admincafe']);
        }
        return self::publicOrder($order, $token);
    }

    public static function publicOrder(\WC_Order $order, string $token = ''): array
    {
        return [
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'stage' => $order->get_meta('_admincafe_stage'),
            'total' => $order->get_total(),
            'currency_symbol' => html_entity_decode(get_woocommerce_currency_symbol($order->get_currency())),
            'tracking_token' => $token
        ];
    }

    public static function snapshotItem(\WC_Order_Item_Product $item, \WC_Product $product, string $language): void
    {
        $item->update_meta_data('_admincafe_language', $language);
        $item->update_meta_data('_admincafe_canonical_name', $product->get_name());
        $item->update_meta_data('_admincafe_localized_name', Catalog::localizedName($product, $language));
        foreach (['description', 'short_description'] as $field) {
            $item->update_meta_data('_admincafe_localized_' . $field, Catalog::localizedText($product, $field, $language));
        }
        if (Checkout::translatedVariant($product, $language)) {
            $item->update_meta_data('_admincafe_variant_keys', array_keys($product->get_attributes()));
        } else {
            $item->delete_meta_data('_admincafe_variant_keys');
        }
    }

    public static function detail(\WC_Order $order): array
    {
        $data = self::publicOrder($order);
        unset($data['tracking_token']);
        $address = $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address();
        $address = wp_strip_all_tags(preg_replace('/<br\s*\/?\s*>/i', ', ', $address));
        return $data + [
            'channel' => $order->get_meta('_admincafe_channel'),
            'payment_status' => $order->get_status(),
            'paid' => $order->is_paid(),
            'refunded' => $order->get_total_refunded(),
            'table' => $order->get_meta('_admincafe_table_label'),
            'name' => $order->get_formatted_billing_full_name(),
            'phone' => $order->get_billing_phone(),
            'address' => $address,
            'note' => $order->get_customer_note(),
            'created_at' => $order->get_date_created()?->date(DATE_ATOM),
            'items' => array_values(array_map(static fn ($i) => [
                'id' => $i->get_id(),
                'name' => $i->get_meta('_admincafe_canonical_name') ?: $i->get_name(),
                'quantity' => $i->get_quantity(),
                'total' => $i->get_total()
            ], $order->get_items()))
        ];
    }

    /** Keep operational stages independent from financial settlement and gateway refunds. */
    public static function update(\WC_Order $order, array $input): array|\WP_Error
    {
        $current = $order->get_meta('_admincafe_stage');
        $next = $input['stage'] ?? $current;
        if ($next !== $current && !in_array($next, self::transitions()[$current] ?? [], true)) {
            return new \WP_Error('stage', __('Invalid stage transition.', 'admincafe'), ['status' => 409]);
        }
        if (in_array($order->get_meta('_admincafe_channel'), ['pickup', 'delivery'], true) && in_array($next, [
            'accepted',
            'preparing',
            'ready',
            'delivered'
        ], true)) {
            $offline = (array) apply_filters('admincafe_offline_payment_gateways', [
                'cod',
                'bacs',
                'cheque'
            ]);
            if (
                !$order->is_paid()
                && !(in_array($order->get_payment_method(), $offline, true)
                    && in_array($order->get_status(), ['pending', 'on-hold'], true))
            ) {
                return new \WP_Error('payment_required', __('Payment must be confirmed before accepting this online order.', 'admincafe'), ['status' => 409]);
            }
        }
        $action = $input['payment_action'] ?? '';
        $user = wp_get_current_user();
        $manager = current_user_can('admincafe_manage_settings');
        if ($action && (!$manager && !in_array('admincafe_cashier', $user->roles, true))) {
            return new \WP_Error('payment_permission', __('Cashier permission is required.', 'admincafe'), ['status' => 403]);
        }
        if ($action === 'refund' && !$manager) {
            return new \WP_Error('refund_permission', __('Manager permission is required.', 'admincafe'), ['status' => 403]);
        }
        if ($action && !in_array($action, ['settle', 'refund'], true)) {
            return new \WP_Error('payment_action', __('Invalid payment action.', 'admincafe'), ['status' => 400]);
        }
        if ($action === 'settle') {
            if ($current === 'cancelled' || $next === 'cancelled' || !in_array($order->get_status(), ['pending', 'on-hold'], true)) {
                return new \WP_Error('settlement', __('This order cannot be settled.', 'admincafe'), ['status' => 409]);
            }
            $order->payment_complete();
            $order->add_order_note(sprintf(__('Offline settlement recorded by user %d.', 'admincafe'), get_current_user_id()));
        }
        if ($action === 'refund') {
            $remaining = (float) $order->get_total() - (float) $order->get_total_refunded();
            if (!$order->get_date_paid() || $remaining <= 0) {
                return new \WP_Error('refund', __('No paid balance to refund.', 'admincafe'), ['status' => 409]);
            }
            $gateway = wc_get_payment_gateway_by_order($order);
            $online = (bool) $order->get_transaction_id();
            if ($online && (!$gateway || !$gateway->supports('refunds'))) {
                return new \WP_Error(
                    'refund_gateway',
                    __('Refund through the gateway dashboard first; automatic refunds are unavailable.', 'admincafe'),
                    ['status' => 409]
                );
            }
            $refund = wc_create_refund([
                'order_id' => $order->get_id(),
                'amount' => $remaining,
                'reason' => __('AdminCafe manager refund', 'admincafe'),
                'refund_payment' => $online,
                'restock_items' => false
            ]);
            if (is_wp_error($refund)) {
                return $refund;
            }
            $order = wc_get_order($order->get_id());
        }
        if ($next !== $current) {
            $order->update_meta_data('_admincafe_stage', $next);
            if ($next === 'cancelled' && !$order->get_date_paid()) {
                $order->set_status('cancelled');
            }
            $order->save();
            try {
                do_action('admincafe_order_stage_changed', $order->get_id(), $next);
            } catch (\Throwable $error) {
                wc_get_logger()->error(__('AdminCafe stage notification failed.', 'admincafe'), ['source' => 'admincafe']);
            }
        }
        return self::detail($order);
    }
}
