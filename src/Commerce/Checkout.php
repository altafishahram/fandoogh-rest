<?php

namespace FandooghRest\Commerce;

use FandooghRest\Core\Settings;
use FandooghRest\Menu\Catalog;
use FandooghRest\Localization\Language;
use FandooghRest\Branches\Branches;
use FandooghRest\Tables\Tables;

final class Checkout
{
    private static ?int $storeBranch = null;
    private static array $storeContexts = [];
    public function register(): void
    {
        add_filter('woocommerce_add_to_cart_validation', [$this, 'addValidation'], 20, 6);
        add_filter('woocommerce_update_cart_validation', [$this, 'quantityValidation'], 20, 4);
        add_filter('woocommerce_add_cart_item_data', [$this, 'bindItem'], 20, 4);
        add_filter('woocommerce_add_cart_item', [$this, 'bindAddedItem'], 20, 2);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'restoreItem'], 20, 3);
        add_action('woocommerce_store_api_validate_add_to_cart', [$this, 'storeAddValidation'], 20, 2);
        add_action('woocommerce_store_api_validate_cart_item', [$this, 'storeItemValidation'], 20, 2);
        add_filter('rest_request_before_callbacks', [$this, 'storeRequestValidation'], 15, 3);
        add_filter('rest_request_after_callbacks', [$this, 'restoreStoreContext'], 99, 3);
        add_filter('woocommerce_cart_needs_shipping', [$this, 'needsShipping'], 100);
        add_filter('woocommerce_cart_needs_shipping_address', [$this, 'needsShipping'], 100);
        add_action('woocommerce_after_checkout_validation', [$this, 'classicValidation'], 10, 2);
        add_action('woocommerce_checkout_create_order', [$this, 'decorate'], 10, 2);
        add_action('woocommerce_resume_order', [$this, 'resumeValidation'], 5, 1);
        add_action('woocommerce_checkout_order_processed', [$this, 'placed'], 20, 1);
        add_action('woocommerce_store_api_checkout_update_order_from_request', [$this, 'storeValidation'], 10, 2);
        add_action('woocommerce_store_api_checkout_order_created', [$this, 'storeDraftCreated'], 10, 1);
        add_action('woocommerce_store_api_checkout_update_order_meta', [$this, 'storeDraftCreated'], 10, 1);
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
            return new \WP_Error('language', __('Unsupported customer language.', 'fandoogh-rest'), ['status' => 400]);
        }
        return Language::resolve($value);
    }

    /** Resolve public ownership from a real branch or a stable server-side QR identity. */
    public static function resolveBranch(array $input, bool $required = true): int|\WP_Error
    {
        $id = $input['branch_id'] ?? null;
        if ($id !== null && ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]*$/D', (string) $id))) {
            return new \WP_Error('branch', __('Choose a valid branch.', 'fandoogh-rest'), ['status' => 400]);
        }
        $token = $input['table_token'] ?? $input['table'] ?? null;
        if ($token !== null && $token !== '') {
            if (!is_string($token)) { return self::branchError(); }
            $table = Tables::context($token);
            if (is_wp_error($table)) { return $table; }
            if ($id !== null && (int) $id !== (int) $table['branch_id']) { return self::branchError(); }
            $id = $table['branch_id'];
        }
        if ($id === null && $required) {
            return new \WP_Error('branch_required', __('Choose a branch before ordering.', 'fandoogh-rest'), ['status' => 400]);
        }
        $id = $id === null ? Branches::current() : (int) $id;
        $branch = Branches::get($id);
        return $branch && $branch['enabled'] ? $id : new \WP_Error('branch_disabled', __('This branch is unavailable.', 'fandoogh-rest'), ['status' => 403]);
    }

    public static function cartBranch(): int
    {
        if (function_exists('WC') && WC()->session) {
            $id = (int) WC()->session->get('admincafe_branch_id', 0);
            if ($id) { return $id; }
        }
        // Unattributed legacy sessions belong to the migrated default branch.
        return Branches::defaultId();
    }

    private static function requestedBranch(): ?int
    {
        if (self::$storeBranch !== null) { return self::$storeBranch; }
        if (!isset($_REQUEST['branch_id'])) { return null; }
        $id = wp_unslash($_REQUEST['branch_id']);
        return is_scalar($id) && preg_match('/^[1-9][0-9]*$/D', (string) $id) ? (int) $id : 0;
    }

    private static function branchError(): \WP_Error
    {
        return new \WP_Error('cart_branch_conflict', __('All cart items and checkout must belong to the selected branch. Return to its menu or confirm replacing your cart.', 'fandoogh-rest'), ['status' => 409]);
    }

    private static function addError(\WC_Product $product, ?int $requested = null): bool|\WP_Error
    {
        $id = $requested ?? self::requestedBranch() ?? self::cartBranch();
        if (Branches::productBranch($product) !== $id) { return self::branchError(); }
        if (WC()->cart && !WC()->cart->is_empty() && self::cartBranch() !== $id) { return self::branchError(); }
        foreach (WC()->cart ? WC()->cart->get_cart() : [] as $item) {
            $existing = wc_get_product((int) (($item['variation_id'] ?? 0) ?: $item['product_id']));
            if (!$existing || Branches::productBranch($existing) !== $id || (int) ($item['_fandoogh_branch_id'] ?? Branches::defaultId()) !== $id) { return self::branchError(); }
        }
        return Branches::runFor($id, static function () {
            $allowed = self::allowed(self::cartBranch() === Branches::current() ? self::channel() : '');
            if (is_wp_error($allowed)) { return $allowed; }
            if (Settings::get('ordering_paused')) { return new \WP_Error('closed', __('Ordering is unavailable.', 'fandoogh-rest'), ['status' => 403]); }
            return true;
        });
    }

    public function addValidation(bool $valid, int $productId, $quantity, int $variationId = 0, array $variations = [], array $data = []): bool
    {
        $product = wc_get_product($variationId ?: $productId);
        $error = $product ? self::addError($product) : self::branchError();
        if (is_wp_error($error)) { wc_add_notice($error->get_error_message(), 'error'); return false; }
        return $valid;
    }

    public function quantityValidation(bool $valid, string $key, array $item, $quantity): bool
    {
        if ((float) $quantity <= 0) { return $valid; }
        $error = self::cartError();
        if (is_wp_error($error)) { wc_add_notice($error->get_error_message(), 'error'); return false; }
        return $valid;
    }

    public function bindItem(array $data, int $productId, int $variationId = 0, $quantity = 1): array
    {
        $product = wc_get_product($variationId ?: $productId);
        if ($product) {
            $error = self::addError($product);
            if (is_wp_error($error)) { throw new \Exception($error->get_error_message()); }
            $data['_fandoogh_branch_id'] = Branches::productBranch($product);
            if (WC()->session && (!WC()->cart || WC()->cart->is_empty())) {
                if (self::cartBranch() !== $data['_fandoogh_branch_id']) { WC()->session->set('admincafe_channel', null); }
                WC()->session->set('admincafe_branch_id', $data['_fandoogh_branch_id']);
            }
        }
        return $data;
    }

    public function restoreItem(array $item, array $values, string $key): array
    {
        $item['_fandoogh_branch_id'] = (int) ($values['_fandoogh_branch_id'] ?? Branches::defaultId());
        return $item;
    }

    private static function storeThrow(bool|\WP_Error $error): void
    {
        if (is_wp_error($error)) { throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException($error->get_error_code(), $error->get_error_message(), (int) ($error->get_error_data()['status'] ?? 409)); }
    }

    /** Store API adds directly to cart contents, bypassing add_cart_item_data. */
    public function bindAddedItem(array $item, string $key): array
    {
        $product = wc_get_product((int) (($item['variation_id'] ?? 0) ?: $item['product_id']));
        if ($product) {
            $item['_fandoogh_branch_id'] = Branches::productBranch($product);
            if (WC()->session && WC()->cart && count(WC()->cart->get_cart()) <= 1) {
                WC()->session->set('admincafe_branch_id', $item['_fandoogh_branch_id']);
            }
        }
        return $item;
    }

    public function storeAddValidation(\WC_Product $product, array $request): void
    {
        self::storeThrow(self::addError($product));
        if (WC()->session && WC()->cart && WC()->cart->is_empty()) {
            if (self::cartBranch() !== Branches::productBranch($product)) { WC()->session->set('admincafe_channel', null); }
            WC()->session->set('admincafe_branch_id', Branches::productBranch($product));
        }
    }

    public function storeItemValidation(\WC_Product $product, array $item): void
    {
        if (Branches::productBranch($product) !== self::cartBranch() || (int) ($item['_fandoogh_branch_id'] ?? Branches::defaultId()) !== self::cartBranch()) {
            self::storeThrow(self::branchError());
        }
        self::storeThrow(self::cartError());
    }

    public function storeRequestValidation($response, $handler, \WP_REST_Request $request)
    {
        if (!preg_match('#^/wc/store/v[0-9]+/(?:cart|checkout)(?:/|$)#', $request->get_route())) { return $response; }
        self::$storeContexts[] = [self::$storeBranch, Branches::current()];
        self::$storeBranch = null;
        if ($response !== null) { return $response; }
        if (!WC()->session || !WC()->cart) { wc_load_cart(); }
        $param = $request->get_param('branch_id');
        if ($param !== null) {
            $id = self::resolveBranch(['branch_id' => $param]);
            if (is_wp_error($id)) { return $id; }
            // Bind validated request identity for add-to-cart and checkout hooks.
            self::$storeBranch = $id;
            if (WC()->cart && !WC()->cart->is_empty() && $id !== self::cartBranch()) { return self::branchError(); }
        }
        if (preg_match('#/(?:checkout|cart/update-item)$#', $request->get_route())) {
            $error = self::cartError();
            if (is_wp_error($error)) { return $error; }
            if (str_ends_with($request->get_route(), '/checkout') && WC()->session) {
                $draft = wc_get_order((int) WC()->session->get('store_api_draft_order', 0));
                if ($draft && Branches::orderBranch($draft) !== self::cartBranch()) { return self::branchError(); }
            }
        }
        $context = self::$storeBranch ?? self::cartBranch();
        $branch = Branches::get($context);
        if (!$branch || !$branch['enabled']) { return self::branchError(); }
        Branches::setCurrent($context);
        return $response;
    }

    public function restoreStoreContext($response, $handler, \WP_REST_Request $request)
    {
        if (preg_match('#^/wc/store/v[0-9]+/(?:cart|checkout)(?:/|$)#', $request->get_route()) && self::$storeContexts) {
            [$previous, $branch] = array_pop(self::$storeContexts);
            self::$storeBranch = $previous;
            Branches::setCurrent($branch);
        }
        return $response;
    }

    public static function choose(string $channel, mixed $language = null, ?int $branchId = null, bool $replace = false): array|\WP_Error
    {
        $branchId = $branchId ?? Branches::current();
        return Branches::runFor($branchId, static fn() => self::chooseInBranch($channel, $language, $replace));
    }

    private static function chooseInBranch(string $channel, mixed $language, bool $replace): array|\WP_Error
    {
        if (!in_array($channel, ['pickup', 'delivery'], true)) {
            return new \WP_Error('channel', __('Invalid checkout channel.', 'fandoogh-rest'), ['status' => 400]);
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
        $previous = self::cartBranch();
        if (WC()->cart && !WC()->cart->is_empty() && $previous !== Branches::current()) {
            if (!$replace) {
                return new \WP_Error('cart_branch_conflict', __('Your cart belongs to another branch. Confirm replacing it to continue.', 'fandoogh-rest'), ['status' => 409, 'branch_id' => $previous]);
            }
            WC()->cart->empty_cart();
        }
        WC()->session->set('admincafe_branch_id', Branches::current());
        // Detach the customer's prior branch checkout references. Preserve the orders
        // themselves and their native payment/stock lifecycle.
        foreach (['store_api_draft_order', 'order_awaiting_payment'] as $key) {
            $previousOrder = wc_get_order((int) WC()->session->get($key, 0));
            if ($previousOrder && Branches::orderBranch($previousOrder) !== Branches::current()) {
                WC()->session->set($key, null);
            }
        }
        Language::remember($language);
        WC()->session->set('admincafe_channel', $channel);
        WC()->session->set_customer_session_cookie(true);
        if (WC()->cart) {
            WC()->cart->calculate_shipping();
            WC()->cart->calculate_totals();
        }
        return ['branch_id' => Branches::current(), 'url' => add_query_arg(['lang' => $language, 'branch_id' => Branches::current()], wc_get_checkout_url())];
    }

    public static function allowed(string $channel): bool|\WP_Error
    {
        $branch = Branches::get(Branches::current());
        if (!$branch || !$branch['enabled']) {
            return new \WP_Error('branch_disabled', __('This branch is unavailable.', 'fandoogh-rest'), ['status' => 403]);
        }
        if ($channel === '') {
            return true;
        }
        if (!in_array($channel, ['pickup', 'delivery'], true) || Settings::get('ordering_paused') || !Settings::get($channel . '_enabled')) {
            return new \WP_Error('channel_disabled', __('This ordering channel is unavailable.', 'fandoogh-rest'), ['status' => 403]);
        }
        return true;
    }

    public function needsShipping(bool $needs): bool
    {
        return self::channel() === 'pickup' ? false : $needs;
    }

    public static function cartError(): bool|\WP_Error
    {
        $branchId = self::cartBranch();
        $requested = self::requestedBranch();
        if ($requested !== null && $requested !== $branchId) { return self::branchError(); }
        return Branches::runFor($branchId, static fn() => self::cartErrorInBranch());
    }

    private static function cartErrorInBranch(): bool|\WP_Error
    {
        $channel = self::channel();
        $allowed = self::allowed($channel);
        if (is_wp_error($allowed)) {
            return $allowed;
        }
        if (!WC()->cart) {
            return true;
        }
        foreach (WC()->cart->get_cart() as $item) {
            $product = wc_get_product((int) (($item['variation_id'] ?? 0) ?: $item['product_id']));
            if (!$product || Branches::productBranch($product) !== Branches::current()
                || (int) ($item['_fandoogh_branch_id'] ?? Branches::defaultId()) !== Branches::current()) {
                return self::branchError();
            }
        }
        if (!$channel) {
            $visible = array_column(Catalog::menu()['products'], 'id');
            foreach (WC()->cart->get_cart() as $item) {
                if (in_array((int) $item['product_id'], $visible, true)) {
                    return new \WP_Error(
                        'channel_required',
                        __('Choose pickup or delivery from the restaurant menu before checkout.', 'fandoogh-rest'),
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

    public function resumeValidation(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if ($order && Branches::orderBranch($order) !== self::cartBranch()) { throw new \Exception(self::branchError()->get_error_message()); }
    }

    public function storeDraftCreated(\WC_Order $order): void
    {
        self::storeThrow(self::cartError());
        if ($order->get_meta('_fandoogh_branch_id') && Branches::orderBranch($order) !== self::cartBranch()) {
            self::storeThrow(self::branchError());
        }
        Branches::runFor(self::cartBranch(), static fn() => Orders::snapshotBranch($order));
        $order->save();
    }

    public function decorate(\WC_Order $order, array $data = []): void
    {
        Branches::runFor(self::cartBranch(), fn() => $this->decorateInBranch($order, $data));
    }

    private function decorateInBranch(\WC_Order $order, array $data = []): void
    {
        $channel = self::channel();
        if (!$channel) {
            return;
        }
        $branchId = self::cartBranch();
        $error = self::cartError();
        if (is_wp_error($error) || ($order->get_meta('_fandoogh_branch_id') && Branches::orderBranch($order) !== $branchId)) {
            throw new \Exception(is_wp_error($error) ? $error->get_error_message() : self::branchError()->get_error_message());
        }
        Branches::runFor($branchId, static fn() => Orders::snapshotBranch($order));
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
            wc_get_logger()->error(__('Fandoogh Rest checkout notification failed.', 'fandoogh-rest'), ['source' => 'admincafe']);
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
                wc_get_logger()->error(__('Fandoogh Rest payment notification failed.', 'fandoogh-rest'), ['source' => 'admincafe']);
            }
        }
    }
}
