<?php
namespace RMM;

if (!defined('ABSPATH')) {
    exit;
}

class Autoloader {

    public static function run() {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    public static function autoload($class) {
        $prefix = 'RMM\\';
        $base_dir = RMM_PLUGIN_PATH . 'includes/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
