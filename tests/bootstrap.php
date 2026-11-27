<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('OBJECT_K')) {
    define('OBJECT_K', 'OBJECT_K');
}

$GLOBALS['rmm_test_users'] = [];
$GLOBALS['rmm_test_current_user_id'] = 0;
$GLOBALS['rmm_test_terms'] = [];
$GLOBALS['rmm_test_actions'] = [];

function absint($value) {
    return abs((int) $value);
}

function add_action() {
    return true;
}

function do_action($hook) {
    $args = func_get_args();
    array_shift($args);
    $GLOBALS['rmm_test_actions'][] = [$hook, $args];
}

function is_wp_error($value) {
    return false;
}

function get_current_user_id() {
    return (int) $GLOBALS['rmm_test_current_user_id'];
}

function get_userdata($user_id) {
    return $GLOBALS['rmm_test_users'][(int) $user_id] ?? false;
}

function wp_get_current_user() {
    return get_userdata(get_current_user_id()) ?: new RMM_Test_User(0, [], []);
}

function user_can($user, $capability) {
    if (is_numeric($user)) {
        $user = get_userdata((int) $user);
    }

    return $user && isset($user->caps[$capability]) && $user->caps[$capability] === true;
}

function get_terms($args = []) {
    return $GLOBALS['rmm_test_terms'];
}

class RMM_Test_User {
    public $ID;
    public $roles;
    public $caps;

    public function __construct($id, array $roles = [], array $caps = []) {
        $this->ID = (int) $id;
        $this->roles = $roles;
        $this->caps = array_fill_keys($caps, true);
    }

    public function exists() {
        return $this->ID > 0;
    }
}

class RMM_Test_Wpdb {
    public $prefix = 'wp_';
    public $rows = [];
    public $insert_id = 0;

    public function prepare($query) {
        $args = func_get_args();
        array_shift($args);
        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }
        if (!$args) {
            return $query;
        }
        return vsprintf(str_replace('%d', '%s', $query), array_map('intval', $args));
    }

    public function get_row($query) {
        if (preg_match('/product_id\s*=\s*[\'"]?(\d+)[\'"]?.*branch_id\s*=\s*[\'"]?(\d+)/i', $query, $matches)) {
            $key = $matches[1] . ':' . $matches[2];
            return isset($this->rows[$key]) ? clone $this->rows[$key] : null;
        }
        return null;
    }

    public function get_var($query) {
        $row = $this->get_row($query);
        return $row ? $row->id : null;
    }

    public function insert($table, $data) {
        $this->insert_id++;
        $row = (object) array_merge($data, ['id' => $this->insert_id]);
        $this->rows[$row->product_id . ':' . $row->branch_id] = $row;
        return 1;
    }

    public function update($table, $data, $where) {
        foreach ($this->rows as $key => $row) {
            if ((int) $row->id === (int) $where['id']) {
                $this->rows[$key] = (object) array_merge((array) $row, $data);
                return 1;
            }
        }
        return 0;
    }

    public function get_results($query, $output = null) {
        $branch_id = 0;
        if (preg_match('/branch_id\s*=\s*[\'"]?(\d+)/i', $query, $matches)) {
            $branch_id = (int) $matches[1];
        }

        $ids = [];
        if (preg_match('/product_id\s+IN\s*\(([^)]+)\)/i', $query, $matches)) {
            $ids = array_map('intval', explode(',', $matches[1]));
        }

        $result = [];
        foreach ($this->rows as $row) {
            if ($branch_id && (int) $row->branch_id !== $branch_id) {
                continue;
            }
            if ($ids && !in_array((int) $row->product_id, $ids, true)) {
                continue;
            }
            $result[] = clone $row;
        }
        return $result;
    }

    public function delete($table, $where, $format = []) {
        $deleted = 0;
        foreach ($this->rows as $key => $row) {
            $matches = true;
            foreach ($where as $field => $value) {
                if ((int) $row->{$field} !== (int) $value) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                unset($this->rows[$key]);
                $deleted++;
            }
        }
        return $deleted;
    }
}

$GLOBALS['wpdb'] = new RMM_Test_Wpdb();

require_once dirname(__DIR__) . '/includes/Branches/InventoryRepository.php';
require_once dirname(__DIR__) . '/includes/FrontendDashboard/AuthController.php';
