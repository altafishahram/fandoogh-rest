<?php
namespace FandooghRest\Import;

use FandooghRest\Menu\Catalog;
use FandooghRest\Rest\Management;

defined('ABSPATH') || exit;

final class Importer
{
    public function register(): void
    {
        add_action('rest_api_init', function (): void {
            foreach (['preview', 'apply'] as $action) {
                register_rest_route('admincafe/v1', '/manage/import/' . $action, [
                    'methods' => 'POST', 'callback' => [$this, $action],
                    'permission_callback' => static fn($request) => Management::permission($request, 'admincafe_manage_menu'),
                ]);
            }
        });
    }

    public function preview(\WP_REST_Request $request): array|\WP_Error
    {
        $file = $request->get_file_params()['file'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            return new \WP_Error('admincafe_import_upload', __('Upload a CSV or XLSX file.', 'fandoogh-rest'), ['status' => 400]);
        }
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            return new \WP_Error('admincafe_import_upload', __('Only CSV and XLSX files are supported.', 'fandoogh-rest'), ['status' => 400]);
        }
        $data = Reader::read($file['tmp_name'], $extension);
        if (is_wp_error($data)) {
            return $data;
        }
        $token = bin2hex(random_bytes(24));
        $data['user'] = get_current_user_id();
        $data['cursor'] = 0;
        $data['result'] = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        set_transient('ac_import_' . hash('sha256', $token), $data, 30 * MINUTE_IN_SECONDS);
        return ['token' => $token, 'columns' => $data['columns'], 'rows' => array_slice($data['rows'], 0, 20), 'total' => count($data['rows'])];
    }

    public function apply(\WP_REST_Request $request): array|\WP_Error
    {
        $token = $request->get_param('token');
        $mapping = $request->get_param('mapping');
        $mode = $request->get_param('mode') ?: 'upsert';
        if (!is_string($token) || !preg_match('/^[a-f0-9]{48}$/D', $token) || !is_array($mapping) || !in_array($mode, ['upsert', 'create'], true)) {
            return new \WP_Error('admincafe_import_request', __('Invalid import request.', 'fandoogh-rest'), ['status' => 400]);
        }
        $key = 'ac_import_' . hash('sha256', $token);
        $data = get_transient($key);
        if (!is_array($data) || $data['user'] !== get_current_user_id()) {
            return new \WP_Error('admincafe_import_expired', __('The preview has expired. Upload the file again.', 'fandoogh-rest'), ['status' => 410]);
        }
        $indexes = [];
        foreach (['name', 'description', 'price', 'sku', 'category', 'status'] as $field) {
            $column = $mapping[$field] ?? '';
            if (!is_string($column) || ($column !== '' && !in_array($column, $data['columns'], true))) {
                return new \WP_Error('admincafe_import_mapping', __('Choose columns from the uploaded file.', 'fandoogh-rest'), ['status' => 400]);
            }
            $indexes[$field] = $column === '' ? null : array_search($column, $data['columns'], true);
        }
        if ($indexes['name'] === null) {
            return new \WP_Error('admincafe_import_mapping', __('Map the product name column.', 'fandoogh-rest'), ['status' => 400]);
        }
        $fingerprint = hash('sha256', wp_json_encode([$indexes, $mode]));
        if (isset($data['fingerprint']) && $data['fingerprint'] !== $fingerprint) {
            return new \WP_Error('admincafe_import_mapping', __('The mapping cannot change after importing starts.', 'fandoogh-rest'), ['status' => 409]);
        }
        $lock = $key . '_lock';
        $locked = get_option($lock);
        if (is_array($locked) && (int) ($locked['created'] ?? 0) < time() - 300) {
            self::release($lock, $locked);
        }
        $lease = ['owner' => bin2hex(random_bytes(16)), 'created' => microtime(true)];
        if (!add_option($lock, $lease, '', false)) {
            return new \WP_Error('admincafe_import_busy', __('This import is already processing.', 'fandoogh-rest'), ['status' => 409]);
        }
        try {
            // A concurrent batch may have advanced the checkpoint while we waited.
            $data = get_transient($key);
            if (!is_array($data) || $data['user'] !== get_current_user_id() || (isset($data['fingerprint']) && $data['fingerprint'] !== $fingerprint)) {
                return new \WP_Error('admincafe_import_expired', __('The preview has expired. Upload the file again.', 'fandoogh-rest'), ['status' => 410]);
            }
            $data['fingerprint'] = $fingerprint;
            $end = min(count($data['rows']), $data['cursor'] + 75);
            for (; $data['cursor'] < $end; $data['cursor']++) {
                $nextLease = $lease;
                $nextLease['created'] = microtime(true);
                global $wpdb;
                $renewed = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s", maybe_serialize($nextLease), $lock, maybe_serialize($lease)));
                if ($renewed !== 1) {
                    return new \WP_Error('admincafe_import_busy', __('This import is already processing.', 'fandoogh-rest'), ['status' => 409]);
                }
                $lease = $nextLease;
                wp_cache_delete($lock, 'options');
                $row = $data['rows'][$data['cursor']];
                $values = [];
                foreach ($indexes as $field => $index) {
                    $values[$field] = $index === null ? null : trim((string) ($row[$index] ?? ''));
                }
                $saved = $this->row($values, $mode, hash('sha256', $token . ':' . $data['cursor']));
                if (is_wp_error($saved)) {
                    $data['result']['skipped']++;
                    $data['result']['errors'][] = ['row' => $data['cursor'] + 2, 'message' => $saved->get_error_message()];
                } else {
                    $data['result'][$saved]++;
                }
                // Checkpoints keep subsequent batches resumable and bounded.
                $checkpoint = $data;
                $checkpoint['cursor']++;
                set_transient($key, $checkpoint, 30 * MINUTE_IN_SECONDS);
            }
            $done = $data['cursor'] >= count($data['rows']);
            set_transient($key, $data, 30 * MINUTE_IN_SECONDS);
            return $data['result'] + ['done' => $done, 'remaining' => count($data['rows']) - $data['cursor']];
        } finally {
            self::release($lock, $lease);
        }
    }

    private static function release(string $key, array $lease): void
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s", $key, maybe_serialize($lease)));
        wp_cache_delete($key, 'options');
        wp_cache_delete('notoptions', 'options');
    }

    private function row(array $values, string $mode, string $rowKey): string|\WP_Error
    {
        if (trim((string) $values['name']) === '') {
            return new \WP_Error('admincafe_import_name', __('Product name is required.', 'fandoogh-rest'));
        }
        // A successful row can be retried without creating another product.
        $previous = get_posts(['post_type' => 'product', 'post_status' => ['publish', 'draft', 'private'], 'fields' => 'ids', 'posts_per_page' => 1, 'meta_key' => '_admincafe_import_row', 'meta_value' => $rowKey]);
        if ($previous) {
            return 'skipped';
        }
        $sku = sanitize_text_field((string) ($values['sku'] ?? ''));
        $id = $sku ? wc_get_product_id_by_sku($sku) : 0;
        if ($id && $mode === 'create') {
            return new \WP_Error('admincafe_import_duplicate', __('This SKU already exists.', 'fandoogh-rest'));
        }
        $input = ['name' => $values['name'], 'visible' => true];
        if ($sku !== '') {
            $input['sku'] = $sku;
        }
        if ($values['description'] !== null) {
            $input['description'] = $values['description'];
        }
        if ($values['price'] !== null) {
            $price = Reader::price($values['price']);
            if (is_wp_error($price)) {
                return $price;
            }
            if ($price === null) {
                $input['available'] = false;
            } else {
                $input['price'] = $price;
                $input['available'] = true;
            }
        }
        if ($values['status'] !== null && $values['status'] !== '') {
            $status = strtolower($values['status']);
            if (in_array($status, ['ناموجود', 'unavailable', 'outofstock', '0', 'false'], true)) {
                $input['available'] = false;
            } elseif (in_array($status, ['موجود', 'available', 'instock', '1', 'true'], true) && !isset($input['available'])) {
                $input['available'] = true;
            }
        }
        if ($values['category'] !== null && $values['category'] !== '') {
            $ids = [];
            foreach (explode('|', $values['category']) as $name) {
                $name = sanitize_text_field(trim($name));
                if (!$name) {
                    continue;
                }
                $term = term_exists($name, 'product_cat');
                if (!$term) {
                    $term = wp_insert_term($name, 'product_cat');
                }
                if (is_wp_error($term)) {
                    return $term;
                }
                $ids[] = (int) (is_array($term) ? $term['term_id'] : $term);
            }
            $input['category_ids'] = $ids;
        }
        global $wpdb;
        if ($wpdb->query('START TRANSACTION') === false) {
            return new \WP_Error('admincafe_import_database', __('The database could not start a safe import transaction.', 'fandoogh-rest'));
        }
        try {
            $result = Catalog::saveProduct($input, $id, $rowKey);
            if (is_wp_error($result)) {
                $wpdb->query('ROLLBACK');
                return $result;
            }
            $wpdb->query('COMMIT');
        } catch (\Throwable $error) {
            $wpdb->query('ROLLBACK');
            return new \WP_Error('admincafe_import_database', __('The product could not be imported safely.', 'fandoogh-rest'));
        }
        return $id ? 'updated' : 'created';
    }
}
