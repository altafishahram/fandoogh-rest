<?php
/** Standalone bootstrap compatibility regression. Run normally and with the "conflict" argument. */
define('ABSPATH', __DIR__);
define('ADMINCAFE_GOOGLE_TRANSLATE_API_KEY', 'test-legacy-credential');
$hooks = [];
$shortcodes = [];
function get_option($name, $default = false) { return $name === 'active_plugins' && ($GLOBALS['argv'][1] ?? '') === 'conflict' ? ['old-admincafe/admincafe.php'] : $default; }
function get_site_option($name, $default = false) { return $default; }
function plugin_basename($file) { return 'fandoogh-rest/' . basename($file); }
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://example.test/plugins/fandoogh-rest/'; }
function add_action($name, $callback, ...$args) { $GLOBALS['hooks'][$name][] = $callback; }
function add_filter($name, $callback, ...$args) { $GLOBALS['hooks'][$name][] = $callback; }
function add_shortcode($name, $callback) { $GLOBALS['shortcodes'][$name] = $callback; }
function register_activation_hook($file, $callback) { $GLOBALS['hooks']['activate'][] = $callback; }
function register_deactivation_hook($file, $callback) { $GLOBALS['hooks']['deactivate'][] = $callback; }
function check(bool $condition, string $message): void { if (!$condition) { throw new RuntimeException($message); } }
require __DIR__ . (($argv[1] ?? '') === 'legacy' ? '/../admincafe.php' : '/../fandoogh-rest.php');
if (($argv[1] ?? '') === 'conflict') {
    check(!defined('FANDOOGH_REST_VERSION'), 'Separate active legacy plugin blocks new bootstrap');
    check(!isset($hooks['plugins_loaded']) && isset($hooks['admin_notices']), 'Conflict adds notice without registering business handlers');
    echo "Branding conflict checks passed.\n";
    exit;
}
check(FANDOOGH_REST_VERSION === '1.3.1', 'New version');
check(basename(FANDOOGH_REST_FILE) === (($argv[1] ?? '') === 'legacy' ? 'admincafe.php' : 'fandoogh-rest.php'), 'HPOS integration uses the entry point actually loaded');
check(ADMINCAFE_PATH === FANDOOGH_REST_PATH, 'Existing path integrations work');
check(FANDOOGH_REST_GOOGLE_TRANSLATE_API_KEY === 'test-legacy-credential', 'Legacy wp-config credentials work');
check(class_exists('AdminCafe\\Core\\Settings'), 'Old class names resolve');
check(is_a('AdminCafe\\Core\\Settings', 'FandooghRest\\Core\\Settings', true), 'Old class aliases reference the same settings implementation');
check(FandooghRest\Core\Access::CAPS[0] === 'admincafe_manage_menu', 'Existing permissions preserved');
(new FandooghRest\Integrations\Builders())->register();
foreach (['menu', 'categories', 'products', 'cart'] as $component) {
    check(isset($shortcodes['admincafe_' . $component], $shortcodes['fandoogh_rest_' . $component]), 'Saved shortcodes and new aliases registered');
}
$count = count($hooks['plugins_loaded']);
require __DIR__ . '/../admincafe.php';
check(count($hooks['plugins_loaded']) === $count, 'Legacy wrapper cannot double-bootstrap');
check(!str_contains(file_get_contents(__DIR__ . '/../admincafe.php'), 'Plugin Name:'), 'Legacy wrapper does not advertise a second plugin');
echo "Branding compatibility checks passed.\n";
