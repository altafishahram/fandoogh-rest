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
     * Get bulk inventory for specific products in a branch (eliminates N+1 queries)
     *
     * @param int $branch_id
     * @param array $product_ids
     * @return array [product_id => object]
     */
    public static function get_bulk_branch_inventory($branch_id, array $product_ids) {
        $branch_id = absint($branch_id);
        $product_ids = array_filter(array_map('absint', $product_ids));

        if (!$branch_id || empty($product_ids)) {
            return [];
        }

        $result = [];
        $missing_ids = [];

        foreach ($product_ids as $pid) {
            $cache_key = "{$pid}_{$branch_id}";
            if (isset(self::$cache[$cache_key])) {
                $result[$pid] = self::$cache[$cache_key];
            } else {
                $missing_ids[] = $pid;
            }
        }

        if (empty($missing_ids)) {
            return $result;
        }

        global $wpdb;
        $table = self::table();
        $placeholders = implode(',', array_fill(0, count($missing_ids), '%d'));

        $query = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE branch_id = %d AND product_id IN ({$placeholders})",
            array_merge([$branch_id], $missing_ids)
        );

        $rows = $wpdb->get_results($query);
        $found_pids = [];

        if ($rows) {
            foreach ($rows as $row) {
                $pid = (int) $row->product_id;
                $result[$pid] = $row;
                $found_pids[$pid] = true;
                self::$cache["{$pid}_{$branch_id}"] = $row;
            }
        }

        // Cache negative lookup for products without branch overrides to eliminate remaining queries
        foreach ($missing_ids as $mid) {
            if (!isset($found_pids[$mid])) {
                self::$cache["{$mid}_{$branch_id}"] = null;
            }
        }

        return $result;
    }

        /**
     * Write row portably across MySQL and SQLite without ON DUPLICATE KEY UPDATE / NOW()
     *
     * @param int $product_id
     * @param int $branch_id
     * @param array $fields
     * @return bool
     */
    private static function write_row($product_id, $branch_id, array $fields) {
        global $wpdb;
        $table = self::table();
        $product_id = absint($product_id);
        $branch_id  = absint($branch_id);

        if (!$product_id || !$branch_id) {
            return false;
        }

        unset(self::$cache["{$product_id}_{$branch_id}"]);

        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE product_id = %d AND branch_id = %d LIMIT 1",
            $product_id,
            $branch_id
        ));

        $fields['updated_at'] = gmdate('Y-m-d H:i:s');

        $success = false;
        if ($existing_id) {
            $updated = $wpdb->update(
                $table,
                $fields,
                ['id' => $existing_id]
            );
            $success = ($updated !== false);
        } else {
            $fields['product_id'] = $product_id;
            $fields['branch_id']  = $branch_id;
            $inserted = $wpdb->insert(
                $table,
                $fields
            );
            $success = ($inserted !== false);
        }

        if ($success) {
            do_action('rmm_branch_inventory_updated', $product_id, $branch_id);
        }

        return $success;
    }

    /**
     * Upsert single item with partial merging to avoid nulling out omitted columns
     *
     * @param array $data
     * @return bool
     */
    public static function upsert($data) {
        $product_id = absint($data['product_id'] ?? 0);
        $branch_id  = absint($data['branch_id'] ?? 0);

        if (!$product_id || !$branch_id) {
            return false;
        }

        // Fetch existing record to prevent nulling omitted columns
        $existing = self::get_item($product_id, $branch_id);

        $regular_price = array_key_exists('regular_price', $data)
            ? ($data['regular_price'] !== '' && $data['regular_price'] !== null ? floatval($data['regular_price']) : null)
            : ($existing ? $existing->regular_price : null);

        $sale_price = array_key_exists('sale_price', $data)
            ? ($data['sale_price'] !== '' && $data['sale_price'] !== null ? floatval($data['sale_price']) : null)
            : ($existing ? $existing->sale_price : null);

        $stock_status = array_key_exists('stock_status', $data)
            ? (in_array($data['stock_status'], ['instock', 'outofstock']) ? $data['stock_status'] : 'instock')
            : ($existing ? $existing->stock_status : 'instock');

        $is_available = array_key_exists('is_available', $data)
            ? (int) (bool) $data['is_available']
            : ($existing ? (int) $existing->is_available : ($stock_status === 'instock' ? 1 : 0));

        return self::write_row($product_id, $branch_id, [
            'regular_price' => $regular_price,
            'sale_price'    => $sale_price,
            'stock_status'  => $stock_status,
            'is_available'  => $is_available,
        ]);
    }

    /**
     * Fast toggle stock status (partial update - preserves existing prices)
     */
    public static function toggle_stock($product_id, $branch_id, $stock_status) {
        $product_id   = absint($product_id);
        $branch_id    = absint($branch_id);
        $stock_status = ($stock_status === 'outofstock') ? 'outofstock' : 'instock';
        $is_available = ($stock_status === 'instock') ? 1 : 0;

        if (!$product_id || !$branch_id) {
            return false;
        }

        return self::write_row($product_id, $branch_id, [
            'stock_status' => $stock_status,
            'is_available' => $is_available,
        ]);
    }

    /**
     * Update branch-specific price (partial update - preserves existing stock status)
     */
    public static function update_price($product_id, $branch_id, $regular_price, $sale_price = null) {
        $product_id    = absint($product_id);
        $branch_id     = absint($branch_id);
        $regular_price = ($regular_price !== '' && $regular_price !== null) ? floatval($regular_price) : null;
        $sale_price    = ($sale_price !== '' && $sale_price !== null) ? floatval($sale_price) : null;

        if (!$product_id || !$branch_id) {
            return false;
        }

        return self::write_row($product_id, $branch_id, [
            'regular_price' => $regular_price,
            'sale_price'    => $sale_price,
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
