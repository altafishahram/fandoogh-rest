<?php
namespace FandooghRest\Reports;

final class Reports
{
    public function register(): void {}
    public static function summary(int $days = 7): array
    {
        $days = min(366, max(1, $days));
        $start = (new \DateTimeImmutable('today', wp_timezone()))->modify('-' . ($days - 1) . ' days');
        $daily = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->modify('+' . $i . ' days')->format('Y-m-d');
            $daily[$date] = ['date' => $date, 'revenue' => 0.0, 'order_count' => 0];
        }
        $result = ['revenue' => 0.0, 'order_count' => 0, 'pending_count' => 0, 'top_products' => [], 'daily' => [], 'currency_symbol' => html_entity_decode(get_woocommerce_currency_symbol())];
        $products = [];
        $page = 1;
        do {
            $batch = wc_get_orders(['limit' => 100, 'page' => $page++, 'paginate' => true, 'date_created' => '>=' . $start->getTimestamp(), 'type' => 'shop_order', 'orderby' => 'date', 'order' => 'ASC']);
            foreach ($batch->orders as $order) {
                // Filter through CRUD rather than storage-specific post/meta queries (HPOS).
                if (!$order->get_meta('_admincafe_channel') || $order->get_status() === 'checkout-draft') { continue; }
                $result['order_count']++;
                if (in_array($order->get_meta('_admincafe_stage'), ['awaiting_approval', 'accepted', 'preparing', 'ready'], true)) { $result['pending_count']++; }
                $date = $order->get_date_created()->setTimezone(wp_timezone())->format('Y-m-d');
                if (isset($daily[$date])) { $daily[$date]['order_count']++; }
                if (!$order->is_paid() && !$order->get_date_paid() && $order->get_status() !== 'refunded') { continue; }
                // Never combine historical foreign currencies into the store currency total.
                if ($order->get_currency() !== get_woocommerce_currency()) { continue; }
                $net = max(0, (float) $order->get_total() - (float) $order->get_total_refunded());
                $result['revenue'] += $net;
                if (isset($daily[$date])) { $daily[$date]['revenue'] += $net; }
                foreach ($order->get_items() as $item_id => $item) {
                    $id = $item->get_product_id();
                    $products[$id] ??= ['id' => $id, 'name' => $item->get_name(), 'quantity' => 0, 'revenue' => 0.0];
                    $products[$id]['quantity'] += max(0, $item->get_quantity() + $order->get_qty_refunded_for_item($item_id));
                    $products[$id]['revenue'] += max(0, (float) $item->get_total() - abs((float) $order->get_total_refunded_for_item($item_id)));
                }
            }
        } while ($page <= $batch->max_num_pages);
        usort($products, fn($a, $b) => $b['quantity'] <=> $a['quantity']);
        $result['revenue'] = round($result['revenue'], wc_get_price_decimals());
        $result['top_products'] = array_slice(array_values($products), 0, 10);
        $result['daily'] = array_values($daily);
        return $result;
    }
}
