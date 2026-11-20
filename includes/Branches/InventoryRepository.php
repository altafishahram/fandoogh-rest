<?php
namespace RMM\Branches;

if (!defined('ABSPATH')) {
    exit;
}

class InventoryRepository {

    private static $cache = [];

    /**
     * Get table name
     */
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'rmm_branch_inventory';
    }

    /**
     * Get inventory record for a specific product and branch
     *
     * @param int $product_id
     * @param int $branch_id
     * @return object|null
     */
    public static function get_item($product_id, $branch_id) {
        $product_id = absint($product_id);
        $branch_id  = absint($branch_id);

        if (!$product_id || !$branch_id) {
            return null;
        }

        $cache_key = "{$product_id}_{$branch_id}";
        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        global $wpdb;
        $table = self::table();
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE product_id = %d AND branch_id = %d LIMIT 1",
            $product_id,
            $branch_id
        ));

        self::$cache[$cache_key] = $row;
        return $row;
    }

    /**
     * Get all inventory items for a branch keyed by product_id
     *
     * @param int $branch_id
     * @return array
     */
    public static function get_branch_inventory($branch_id) {
        $branch_id = absint($branch_id);
        if (!$branch_id) {
            return [];
        }

        global $wpdb;
        $table = self::table();
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE branch_id = %d",
            $branch_id
        ), OBJECT_K); // Keyed by product_id or primary key

        $result = [];
        if ($rows) {
            foreach ($rows as $row) {
                $result[$row->product_id] = $row;
                self::$cache["{$row->product_id}_{$branch_id}"] = $row;
            }
        }

        return $result;
    }

    /**
     * Upsert single item with ON DUPLICATE KEY UPDATE
     *
     * @param array $data
     * @return bool|int
     */
    public static function upsert($data) {
        global $wpdb;
        $table = self::table();

        $product_id    = absint($data['product_id'] ?? 0);
        $branch_id     = absint($data['branch_id'] ?? 0);
        $regular_price = isset($data['regular_price']) && $data['regular_price'] !== '' ? floatval($data['regular_price']) : null;
        $sale_price    = isset($data['sale_price']) && $data['sale_price'] !== '' ? floatval($data['sale_price']) : null;
        $stock_status  = in_array($data['stock_status'] ?? '', ['instock', 'outofstock']) ? $data['stock_status'] : 'instock';
        $is_available  = isset($data['is_available']) ? (int) (bool) $data['is_available'] : 1;

        if (!$product_id || !$branch_id) {
            return false;
        }

        // Invalidate internal cache
        unset(self::$cache["{$product_id}_{$branch_id}"]);

        $sql = "INSERT INTO {$table} (product_id, branch_id, regular_price, sale_price, stock_status, is_available, updated_at)
                VALUES (%d, %d, %s, %s, %s, %d, NOW())
                ON DUPLICATE KEY UPDATE
                    regular_price = VALUES(regular_price),
                    sale_price    = VALUES(sale_price),
                    stock_status  = VALUES(stock_status),
                    is_available  = VALUES(is_available),
                    updated_at    = NOW()";

        return $wpdb->query($wpdb->prepare(
            $sql,
            $product_id,
            $branch_id,
            $regular_price,
            $sale_price,
            $stock_status,
            $is_available
        ));
    }

    /**
     * Fast toggle stock status
     */
    public static function toggle_stock($product_id, $branch_id, $stock_status) {
        $product_id   = absint($product_id);
        $branch_id    = absint($branch_id);
        $stock_status = ($stock_status === 'outofstock') ? 'outofstock' : 'instock';
        $is_available = ($stock_status === 'instock') ? 1 : 0;

        return self::upsert([
            'product_id'   => $product_id,
            'branch_id'    => $branch_id,
            'stock_status' => $stock_status,
            'is_available' => $is_available
        ]);
    }

    /**
     * Update branch-specific price
     */
    public static function update_price($product_id, $branch_id, $regular_price, $sale_price = null) {
        return self::upsert([
            'product_id'    => $product_id,
            'branch_id'     => $branch_id,
            'regular_price' => $regular_price,
            'sale_price'    => $sale_price
        ]);
    }

    /**
     * Delete inventory records when a product is deleted
     */
    public static function delete_for_product($product_id) {
        global $wpdb;
        $table = self::table();
        return $wpdb->delete($table, ['product_id' => absint($product_id)], ['%d']);
    }

    /**
     * Delete inventory records when a branch term is deleted
     */
    public static function delete_for_branch($branch_id) {
        global $wpdb;
        $table = self::table();
        return $wpdb->delete($table, ['branch_id' => absint($branch_id)], ['%d']);
    }
}
