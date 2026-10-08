<?php
/** Disposable WordPress/Woo appearance regression. No orders/products are changed. */
define('WP_HTTP_BLOCK_EXTERNAL', true);
$_SERVER['HTTP_HOST'] = '127.0.0.1:8093';
$_SERVER['REQUEST_URI'] = '/wp-json/admincafe/v1/manage/appearance/preview';
$loader = $argv[1] ?? '';
if (!$loader || !is_file($loader)) { fwrite(STDERR, "Pass a disposable WordPress wp-load.php path.\n"); exit(1); }
require $loader;
if (get_option('admincafe_test_environment') !== 'local-disposable') { fwrite(STDERR, "Disposable database marker required.\n"); exit(1); }

use AdminCafe\Core\Settings;
use AdminCafe\Core\Assets;
use AdminCafe\Integrations\Builders;

$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) { throw new RuntimeException('FAIL after ' . $checks . ' checks: ' . $label); }
    $checks++;
};
set_exception_handler(static function (Throwable $error): void { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); });
$options = [];
foreach (['admincafe_settings', 'woocommerce_currency'] as $name) { $options[$name] = get_option($name, null); }
$previousUser = get_current_user_id();
$users = [];
$request = static function (string $path, array $body, ?string $nonce = null): WP_REST_Request {
    $r = new WP_REST_Request('POST', '/admincafe/v1/manage/' . $path);
    $r->set_header('Content-Type', 'application/json');
    $r->set_body(wp_json_encode($body));
    if ($nonce !== null) { $r->set_header('X-WP-Nonce', $nonce); }
    return $r;
};
$scope = '.admincafe-root[data-admincafe-app="menu"] .ac-menu';
$validMap = [
    'general' => 'h2, .ac-product-card { color: #123456; } @media (min-width: 700px) { @supports (display: grid) { .ac-products { gap: 12px; } } }',
    'card' => 'border-radius: 19px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);',
    'detail' => 'padding: 21px;', 'language' => 'letter-spacing: 0.2px;',
    'categories' => 'gap: 9px;', 'buttons' => 'font-weight: 600;', 'cart' => 'border-radius: 17px;',
];
try {
    $manager = wp_insert_user(['user_login' => 'ac-appearance-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'subscriber']);
    if (is_wp_error($manager)) { throw new RuntimeException('Could not create dedicated test manager.'); }
    $users[] = $manager;
    (new WP_User($manager))->add_cap('admincafe_manage_settings');
    wp_set_current_user($manager);
    $nonce = wp_create_nonce('wp_rest');
    $before = get_option('admincafe_settings', []);
    $response = rest_do_request($request('settings', ['menu_theme' => 'garden', 'custom_css_enabled' => true, 'custom_css' => $validMap], $nonce));
    $check($response->get_status() === 200, 'Authorized appearance save through REST');
    $saved = Settings::all();
    $check($saved['menu_theme'] === 'garden' && $saved['custom_css_enabled'] === true && $saved['custom_css'] === $validMap, 'Raw drafts persist without truncation or declaration loss');
    foreach (['restaurant_name', 'tagline', 'messages', 'content_translations', 'currency_code'] as $field) {
        $check($saved[$field] === ($before[$field] ?? Settings::defaults()[$field]), 'Appearance leaves existing field intact: ' . $field);
    }
    foreach (['fa', 'en', 'zh', 'tr'] as $language) {
        $public = Settings::publicSettings($language);
        $css = $public['menu_custom_css'] ?? '';
        $check(!array_key_exists('custom_css', $public) && !array_key_exists('content_translations', $public), 'Public settings redact raw maps: ' . $language);
        $check(($public['menu_theme'] ?? '') === 'garden' && str_contains($css, $scope) && str_contains($css, '@media') && str_contains($css, '@supports'), 'Public CSS compiled and scoped: ' . $language);
        $check(str_contains($css, $scope . ' h2') && str_contains($css, $scope . ' .ac-product-card'), 'Every comma-list selector scoped: ' . $language);
        foreach (['.ac-product', '.ac-food-dialog', '.ac-language-dialog', '.ac-categories button', '.ac-primary', '.ac-plus', '.ac-cart-float', '.ac-cart-dialog'] as $target) {
            $check(str_contains($css, $scope . ' ' . $target . '{') || str_contains($css, $scope . ' ' . $target . ','), 'Element declarations remain scoped: ' . $target . '/' . $language);
        }
    }
    wp_set_current_user(0);
    $publicRequest = new WP_REST_Request('GET', '/admincafe/v1/bootstrap');
    $publicRequest->set_param('lang', 'fa');
    $bootstrap = rest_do_request($publicRequest);
    $check($bootstrap->get_status() === 200 && !array_key_exists('custom_css', $bootstrap->get_data()['settings']) && str_contains($bootstrap->get_data()['settings']['menu_custom_css'], $scope), 'Guest bootstrap provides compiled CSS only');
    $initial = Assets::config();
    $check(isset($initial['appearance']) && ($initial['appearance']['menu_theme'] ?? '') === 'garden' && str_contains($initial['appearance']['menu_custom_css'] ?? '', $scope), 'Initial language gate receives saved appearance');
    $check(!array_key_exists('custom_css', $initial['appearance']) && !array_key_exists('restaurant_name', $initial['appearance']) && !array_key_exists('messages', $initial['appearance']), 'Initial appearance uses minimal whitelist');
    foreach (['menu', 'categories', 'products', 'cart'] as $component) {
        $mount = (new Builders())->shortcode(['mode' => 'menu'], $component);
        preg_match('/data-admincafe-config="([^"]*)"/', $mount, $match);
        $config = json_decode(html_entity_decode($match[1] ?? '', ENT_QUOTES, 'UTF-8'), true);
        $check(($config['component'] ?? '') === $component && ($config['appearance'] ?? null) === $initial['appearance'], 'Builder initial appearance: ' . $component);
    }
    $check(empty(Assets::config([], 'panel')['appearance']), 'Customer appearance omitted from panel initial config');
    wp_set_current_user($manager);
    $draft = ['menu_theme' => 'midnight', 'custom_css_enabled' => true, 'custom_css' => ['general' => '.ac-product-card { color: #abcdef; }', 'card' => 'padding: 23px;']];
    $snapshot = get_option('admincafe_settings');
    $preview = rest_do_request($request('appearance/preview', $draft, wp_create_nonce('wp_rest')));
    $check($preview->get_status() === 200, 'Valid preview accepted');
    $previewData = $preview->get_data();
    $previewCss = $previewData['menu_custom_css'] ?? '';
    $check(str_contains($previewCss, '.ac-appearance-preview') && !str_contains($previewCss, $scope), 'Preview uses isolated preview scope');
    $check(get_option('admincafe_settings') === $snapshot, 'Preview does not mutate persisted settings');
    foreach (['cafe', 'minimal', 'midnight', 'garden', 'bistro'] as $theme) { $check(!is_wp_error(Settings::update(['menu_theme' => $theme])), 'Allowlisted theme: ' . $theme); }

    $badShapes = [
        ['menu_theme' => 'unknown'], ['menu_theme' => []], ['menu_theme' => 1],
        ['custom_css_enabled' => 'true'], ['custom_css_enabled' => 1], ['custom_css_enabled' => null],
        ['custom_css' => 'color:red'], ['custom_css' => ['unknown' => 'color:red;']],
        ['custom_css' => ['card' => ['color:red;']]], ['custom_css' => ['card' => null]],
        ['custom_css' => ['card' => 1]], ['custom_css' => ['card' => str_repeat(' ', 100001)]],
        ['custom_css' => ['general' => '.ac-product { color:red; }' . str_repeat(' ', 12000)]],
        ['custom_css' => ['card' => 'color:red;' . str_repeat(' ', 4000)]],
        ['custom_css' => array_merge(['general' => '.ac-product { color:red; }' . str_repeat(' ', 11500)], array_fill_keys(['card', 'detail', 'language', 'categories', 'buttons', 'cart'], 'color:red;' . str_repeat(' ', 3900)))],
    ];
    $attacks = [
        'background: url(https://example.invalid/leak);', 'background: URL("https://example.invalid/leak");',
        'background: u/**/rl(https://example.invalid/leak);', 'background: image-set("https://example.invalid/leak" 1x);',
        'background: -webkit-image-set("https://example.invalid/leak" 1x);', 'cursor: url(data:image/svg+xml,bad),auto;',
        'behavior: url(x);', '-moz-binding: url(x);', 'width: expression(alert(1));',
        'color: red; } body { display: none;', 'color: red; </style><script>alert(1)</script>',
        'color: \\72 ed;', "color: red;\x00background:blue;", "color: red;\x01background:blue;",
        'background: attr(data-secret url);',
        'background: var(--remote);', '-webkit-border-image: var(--remote);', '-webkit-box-reflect: var(--remote);', 'fill: var(--remote);', 'stroke: var(--remote);',
        'color: red /* unclosed', 'padding: calc(1px; color:red);',
    ];
    foreach ($attacks as $css) { $badShapes[] = ['custom_css' => ['card' => $css]]; }
    foreach ([
        '@import "https://example.invalid/leak";', '@font-face { font-family: x; src: url(x); }',
        '@keyframes x { from { color:red; } to { color:blue; } }', '@page { color:red; }',
        '.ac-product-card { color:red; } body { background:url(x); }',
        '.ac-product-card { color:red; } </style><img src=x>', '.ac-product-card { color:red; ',
        '@media (min-width:1px) { @import "https://example.invalid/leak"; }',
        ':root { color:red; }', 'body { color:red; }', 'html { color:red; }',
        '.ac-menu + .outside { color:red; }', '.ac-menu:hover ~ .outside { color:red; }',
    ] as $css) { $badShapes[] = ['custom_css' => ['general' => $css]]; }
    foreach ($badShapes as $index => $bad) {
        $snapshot = get_option('admincafe_settings');
        $currency = get_option('woocommerce_currency');
        $bad['restaurant_name'] = 'Must not partially save';
        $bad['currency_code'] = $currency === 'USD' ? 'EUR' : 'USD';
        $failed = rest_do_request($request('settings', $bad, wp_create_nonce('wp_rest')));
        $check($failed->get_status() === 400 && get_option('admincafe_settings') === $snapshot && get_option('woocommerce_currency') === $currency, 'Invalid CSS/type rejects atomically: ' . $index);
        unset($bad['restaurant_name'], $bad['currency_code']);
        $failedPreview = rest_do_request($request('appearance/preview', $bad, wp_create_nonce('wp_rest')));
        $check($failedPreview->get_status() === 400 && get_option('admincafe_settings') === $snapshot, 'Preview applies same CSS/type rejection: ' . $index);
    }
    foreach ([['user' => 0, 'nonce' => null], ['user' => $manager, 'nonce' => null], ['user' => $manager, 'nonce' => 'invalid']] as $case) {
        wp_set_current_user($case['user']);
        foreach (['settings', 'appearance/preview'] as $path) {
            $snapshot = get_option('admincafe_settings');
            $denied = rest_do_request($request($path, $draft, $case['nonce']));
            $check($denied->get_status() >= 400 && get_option('admincafe_settings') === $snapshot, 'Guest/missing/invalid nonce denied without mutation: ' . $path);
        }
    }
    (new WP_User($manager))->remove_cap('admincafe_manage_settings');
    wp_set_current_user(0); wp_set_current_user($manager);
    foreach (['settings', 'appearance/preview'] as $path) { $check(rest_do_request($request($path, $draft, wp_create_nonce('wp_rest')))->get_status() === 403, 'Settings capability required: ' . $path); }
    (new WP_User($manager))->add_cap('admincafe_manage_menu');
    wp_set_current_user(0); wp_set_current_user($manager);
    $staffBootstrap = new WP_REST_Request('GET', '/admincafe/v1/manage/bootstrap');
    $staffBootstrap->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    $staffResponse = rest_do_request($staffBootstrap);
    $check($staffResponse->get_status() === 200 && !array_key_exists('custom_css', $staffResponse->get_data()['settings']), 'Menu staff cannot retrieve raw appearance drafts via management bootstrap');
    $disabled = Settings::update(['custom_css_enabled' => false]);
    $check(!is_wp_error($disabled) && Settings::publicSettings()['menu_custom_css'] === '' && Assets::config()['appearance']['menu_custom_css'] === '', 'Disabled drafts never render publicly');
    $check(!empty(Settings::all()['custom_css']['general']), 'Disabling retains editable drafts');
    $tampered = Settings::all();
    $tampered['custom_css_enabled'] = true;
    $tampered['custom_css']['general'] = '.ac-product { background:url(https://example.invalid/leak); }';
    update_option('admincafe_settings', $tampered, false);
    $check(!str_contains(Settings::publicSettings()['menu_custom_css'], 'example.invalid') && !str_contains(Assets::config()['appearance']['menu_custom_css'], 'example.invalid'), 'Directly tampered stored CSS fails closed on both public paths');
    echo "Appearance security: {$checks} checks passed; original settings restored.\n";
} finally {
    foreach ($options as $name => $value) { if ($value === null) { delete_option($name); } else { update_option($name, $value, false); } }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($users as $id) { wp_delete_user($id); }
    wp_set_current_user($previousUser);
}
