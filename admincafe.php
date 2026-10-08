<?php
/** Legacy entry point for existing installations. No plugin header: use fandoogh-rest.php for new installations. */
defined('ABSPATH') || exit;
if (!defined('FANDOOGH_REST_VERSION') && !defined('FANDOOGH_REST_LEGACY_ENTRY')) { define('FANDOOGH_REST_LEGACY_ENTRY', __FILE__); }
require_once __DIR__ . '/fandoogh-rest.php';
if (defined('FANDOOGH_REST_VERSION')) {
    register_activation_hook(__FILE__, [FandooghRest\Core\Installation::class, 'activate']);
    register_deactivation_hook(__FILE__, [FandooghRest\Core\Installation::class, 'deactivate']);
}
