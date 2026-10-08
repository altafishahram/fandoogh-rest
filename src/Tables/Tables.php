<?php
namespace FandooghRest\Tables;

use FandooghRest\Core\Settings;
use FandooghRest\Branches\Branches;

final class Tables
{
    public function register(): void {}
    public static function all(): array
    {
        return array_values(array_filter(self::raw(), static fn($table) => $table['branch_id'] === Branches::current()));
    }
    private static function raw(): array
    {
        return array_values(array_map(static function ($table) {
            $table['branch_id'] = (int) ($table['branch_id'] ?? Branches::defaultId());
            $table['url'] = home_url('/cafe-qr/' . rawurlencode($table['token']));
            return $table;
        }, (array) get_option('admincafe_tables', [])));
    }
    public static function findByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{48}$/D', $token)) { return null; }
        foreach (self::raw() as $table) {
            if (hash_equals($table['token'], $token)) { return $table; }
        }
        return null;
    }
    public static function context(string $token): array|\WP_Error
    {
        $table = self::findByToken($token);
        if (!$table || !$table['enabled']) { return new \WP_Error('table_not_found', __('Table unavailable.', 'fandoogh-rest'), ['status' => 404]); }
        $branch = Branches::get($table['branch_id']);
        if (!$branch || !$branch['enabled']) { return new \WP_Error('table_not_found', __('Table unavailable.', 'fandoogh-rest'), ['status' => 404]); }
        return Branches::runFor($table['branch_id'], static function () use ($table, $token) {
        $mode = $table['mode'] === 'inherit' ? Settings::get('table_default_mode', 'menu') : $table['mode'];
        return ['id' => $table['id'], 'branch_id' => $table['branch_id'], 'label' => $table['label'], 'token' => $token, 'mode' => $mode,
            'can_order' => $mode === 'order' && (bool) Settings::get('dine_in_enabled', false) && !Settings::get('ordering_paused', false)];
        });
    }
    public static function save(array $input, int $id = 0): array|\WP_Error
    {
        foreach (['label', 'mode', 'enabled'] as $key) {
            if (isset($input[$key]) && !is_scalar($input[$key])) { return new \WP_Error('invalid_table', __('Invalid table field.', 'fandoogh-rest'), ['status' => 400]); }
        }
        $tables = self::raw();
        $index = null;
        foreach ($tables as $key => $table) { if ($table['id'] === $id) { $index = $key; break; } }
        if ($id && ($index === null || $tables[$index]['branch_id'] !== Branches::current())) { return new \WP_Error('table_not_found', __('Table not found.', 'fandoogh-rest'), ['status' => 404]); }
        if (isset($input['mode']) && !in_array($input['mode'], ['inherit', 'menu', 'order'], true)) {
            return new \WP_Error('invalid_mode', __('Invalid table mode.', 'fandoogh-rest'), ['status' => 400]);
        }
        $label = sanitize_text_field($input['label'] ?? ($index !== null ? $tables[$index]['label'] : ''));
        if ($label === '' || mb_strlen($label) > 100) { return new \WP_Error('invalid_label', __('Table label must contain 1–100 characters.', 'fandoogh-rest'), ['status' => 400]); }
        $table = $index !== null ? $tables[$index] : ['id' => max(array_merge([0], array_column($tables, 'id'))) + 1, 'token' => bin2hex(random_bytes(24)), 'mode' => 'inherit', 'enabled' => true];
        $table['label'] = $label;
        $table['branch_id'] = Branches::current();
        if (isset($input['mode'])) { $table['mode'] = $input['mode']; }
        if (array_key_exists('enabled', $input)) { $table['enabled'] = rest_sanitize_boolean($input['enabled']); }
        $table['url'] = home_url('/cafe-qr/' . $table['token']);
        if ($index === null) { $tables[] = $table; } else { $tables[$index] = $table; }
        update_option('admincafe_tables', $tables, false);
        return $table;
    }
    public static function delete(int $id): array|\WP_Error
    {
        $tables = self::raw();
        $filtered = array_values(array_filter($tables, fn($table) => $table['id'] !== $id || $table['branch_id'] !== Branches::current()));
        if (count($filtered) === count($tables)) { return new \WP_Error('table_not_found', __('Table not found.', 'fandoogh-rest'), ['status' => 404]); }
        update_option('admincafe_tables', $filtered, false);
        return ['deleted' => true];
    }
}
