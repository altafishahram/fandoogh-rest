<?php
/** Isolated local WP regression. No provider credentials or outbound HTTP required. */
define('WP_HTTP_BLOCK_EXTERNAL', true);
$_SERVER['HTTP_HOST'] = '127.0.0.1:8093';
$_SERVER['REQUEST_URI'] = '/wp-json/admincafe/v1/manage/translation/status';
$loader = $argv[1] ?? '';
if (!$loader || !is_file($loader)) {
    fwrite(STDERR, "Pass a disposable WordPress wp-load.php path.\n");
    exit(1);
}
require $loader;
if (get_option('admincafe_test_environment') !== 'local-disposable') {
    fwrite(STDERR, "Disposable database marker required.\n");
    exit(1);
}

use AdminCafe\Translation\Config;
use AdminCafe\Translation\GoogleProvider;
use AdminCafe\Translation\Module;
use AdminCafe\Translation\Provider;
use AdminCafe\Translation\Queue;
use AdminCafe\Translation\Source;

if (!class_exists(Module::class)) {
    fwrite(STDERR, "Sync the translation module before running.\n");
    exit(1);
}
$checks = 0;
$check = static function (bool $ok, string $label) use (&$checks): void {
    if (!$ok) { throw new RuntimeException('FAIL after ' . $checks . ' checks: ' . $label); }
    $checks++;
};
set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
});
$options = [];
foreach ([Config::OPTION, 'admincafe_translation_usage', 'admincafe_settings'] as $name) {
    $options[$name] = get_option($name, null);
}
$previousUser = get_current_user_id();
$products = [];
$users = [];
$jobIds = [];
$httpCalls = [];
$httpResponse = ['response' => ['code' => 200], 'body' => wp_json_encode(['data' => ['translations' => [['translatedText' => 'Tea &amp; milk']]]])];
$http = static function ($pre, array $args, string $url) use (&$httpCalls, &$httpResponse) {
    if ($url !== 'https://translation.googleapis.com/language/translate/v2') {
        return new WP_Error('test_http_blocked', 'All unexpected outbound requests are blocked.');
    }
    $httpCalls[] = ['url' => $url, 'args' => $args];
    return $httpResponse;
};
add_filter('pre_http_request', $http, PHP_INT_MAX, 3);
add_filter('pre_wp_mail', '__return_true');
$fakeKey = 'test_translation_key_0123456789';
$provider = new class implements Provider {
    public $during = null;
    public int $calls = 0;
    public function translate(array $texts, string $target): array|WP_Error {
        $this->calls++;
        if ($this->during) { ($this->during)(); }
        return array_map(static fn($text) => 'Translated ' . $text, $texts);
    }
};
$providerFilter = static fn() => $provider;
$makeProduct = static function () use (&$products): WC_Product_Simple {
    // Create fixtures with automation disabled; work only explicit fixture jobs.
    $product = new WC_Product_Simple();
    $product->set_name('چای امنیت آزمون');
    $product->set_description('<p>توضیح فارسی</p>');
    $product->set_short_description('توضیح کوتاه');
    $product->set_regular_price('123456');
    $product->set_sku('SECURITY-NO-EXPORT-' . wp_generate_uuid4());
    $product->set_status('publish');
    $product->save();
    $products[] = $product->get_id();
    return $product;
};
$jobFor = static function (int $id) use (&$jobIds): int {
    global $wpdb;
    Queue::enqueue('product', $id);
    $job = (int) $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . Queue::table() . ' WHERE scope=%s AND item_id=%d ORDER BY id DESC LIMIT 1', 'product', $id));
    if (!$job) { throw new RuntimeException('Fixture job was not queued.'); }
    $jobIds[] = $job;
    return $job;
};
$status = static function (int $job): string {
    global $wpdb;
    return (string) $wpdb->get_var($wpdb->prepare('SELECT status FROM ' . Queue::table() . ' WHERE id=%d', $job));
};
try {
    // A dedicated test manager avoids depending on pre-existing local roles/users.
    $manager = wp_insert_user(['user_login' => 'ac-security-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'subscriber']);
    if (is_wp_error($manager)) { throw new RuntimeException('Could not create test manager.'); }
    $users[] = $manager;
    (new WP_User($manager))->add_cap('admincafe_manage_settings');
    $request = new WP_REST_Request('GET', '/admincafe/v1/manage/translation/settings');
    wp_set_current_user(0);
    $check(is_wp_error(Module::permission($request)), 'Guest translation access denied');
    wp_set_current_user($manager);
    $check(is_wp_error(Module::permission($request)), 'Manager read without REST nonce denied');
    $request->set_header('X-WP-Nonce', 'wrong');
    $check(is_wp_error(Module::permission($request)), 'Invalid nonce denied');
    $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    $check(Module::permission($request) === true, 'Dedicated manager and nonce accepted');
    (new WP_User($manager))->remove_cap('admincafe_manage_settings');
    wp_set_current_user(0);
    wp_set_current_user($manager);
    $check(is_wp_error(Module::permission($request)), 'Authenticated user without capability denied');
    (new WP_User($manager))->add_cap('admincafe_manage_settings');
    wp_set_current_user(0);
    wp_set_current_user($manager);

    update_option(Config::OPTION, ['enabled' => false, 'daily_character_limit' => 100000, 'target_languages' => ['en'], 'version' => 'security-test', 'secret' => ''], false);
    $configured = Config::update(['api_key' => $fakeKey]);
    $check(!is_wp_error($configured) && $configured['configured'], 'Fake credential stored');
    $check(Config::credential() === $fakeKey, 'Encrypted credential round-trip');
    $check(!str_contains(wp_json_encode(Config::raw()), $fakeKey), 'Private option contains encrypted credential');
    $check(!array_key_exists('secret', Config::read()) && !str_contains(wp_json_encode(Config::read()), $fakeKey), 'Management read is redacted');
    $check(!is_wp_error(Config::update(['api_key' => ''])) && Config::credential() === $fakeKey, 'Blank credential preserves stored secret');
    foreach ([['enabled' => 1], ['daily_character_limit' => 0], ['target_languages' => ['fa']], ['api_key' => ['bad']], ['unknown' => true]] as $bad) {
        $before = Config::raw();
        $check(is_wp_error(Config::update($bad)) && Config::raw() === $before, 'Malformed config rejected without mutation');
    }
    update_option('admincafe_translation_usage', ['date' => gmdate('Y-m-d'), 'characters' => 0], false);
    $google = new GoogleProvider();
    $check($google->translate(['چای'], 'zh') === ['Tea & milk'], 'HTML entities safely decoded from plain-text response');
    $wire = $httpCalls[0];
    $body = json_decode($wire['args']['body'], true);
    $check($wire['args']['headers']['X-Goog-Api-Key'] === $fakeKey && !str_contains($wire['url'], $fakeKey), 'Official header auth keeps key out of URL');
    $check($body === ['q' => ['چای'], 'source' => 'fa', 'target' => 'zh-CN', 'format' => 'text', 'model' => 'nmt'], 'Only canonical plain text in explicit Persian request');
    $check($wire['args']['redirection'] === 0 && $wire['args']['sslverify'] === true && $wire['args']['timeout'] <= 30 && $wire['args']['limit_response_size'] <= 262144, 'Transport redirects, timeout and response size bounded');
    $check(str_contains($wire['args']['body'], 'چای') && strlen($wire['args']['body']) < 100000, 'Unicode body stays within Basic wire-byte bound');
    $httpResponse['body'] = wp_json_encode(['data' => ['translations' => [['translatedText' => '<script>bad()</script><b>Tea</b>']]]]);
    $check($google->translate(['چای'], 'en') === ['Tea'], 'Provider markup removed');
    $httpResponse['body'] = wp_json_encode(['data' => ['translations' => []]]);
    $check(is_wp_error($google->translate(['چای'], 'en')), 'Missing translation rejected');
    foreach ([[429, '', true], [503, '', true], [403, 'userRateLimitExceeded', true], [403, 'dailyLimitExceeded', false], [403, 'keyInvalid', false]] as [$code, $reason, $retryable]) {
        $httpResponse = ['response' => ['code' => $code], 'body' => wp_json_encode(['error' => ['message' => $fakeKey, 'errors' => [['reason' => $reason]]]])];
        $error = $google->translate(['چای'], 'en');
        $check(is_wp_error($error) && $error->get_error_data()['retryable'] === $retryable && !str_contains($error->get_error_message(), $fakeKey), 'Provider failure classification and redaction');
    }
    $httpResponse = new WP_Error('http_request_failed', 'Transport detail ' . $fakeKey);
    $error = $google->translate(['چای'], 'en');
    $check(is_wp_error($error) && $error->get_error_data()['retryable'] && !str_contains($error->get_error_message(), $fakeKey), 'Transport errors sanitized');

    $product = $makeProduct();
    $source = Source::read('product', $product->get_id());
    $check(array_keys($source['fields']) === ['name', 'description', 'short_description'] && !str_contains(wp_json_encode($source['fields']), $product->get_sku()) && !str_contains(wp_json_encode($source['fields']), '123456'), 'Source whitelist excludes SKU, image, money and inventory');
    $hash = $source['hash'];
    $product->set_regular_price('999999');
    $product->set_stock_quantity(17);
    $product->save();
    $check(Source::read('product', $product->get_id())['hash'] === $hash, 'Price and inventory changes preserve source hash');
    Source::manual('product', $product->get_id(), ['en' => ['name' => '']]);
    $check(!Source::eligible('product', $product->get_id(), $source, 'en', 'name'), 'Intentional empty manual field protected');
    delete_post_meta($product->get_id(), Source::MANUAL);
    // Keep all test queue work within disposable products and use a mock provider.
    $settings = get_option('admincafe_settings', []);
    $settings['enabled_languages'] = ['fa', 'en', 'zh', 'tr'];
    $settings['menu_category_ids'] = [];
    update_option('admincafe_settings', $settings, false);
    add_filter('admincafe_translation_provider', $providerFilter);
    Config::update(['enabled' => true]);
    Queue::install();
    $job = $jobFor($product->get_id());
    $provider->during = static function () use ($product): void {
        update_post_meta($product->get_id(), '_admincafe_translations', ['en' => ['name' => 'Manager chosen name']]);
        Source::manual('product', $product->get_id(), ['en' => ['name' => 'Manager chosen name']]);
    };
    Queue::work($job);
    $check(get_post_meta($product->get_id(), '_admincafe_translations', true)['en']['name'] === 'Manager chosen name', 'Manual edit during provider response wins');
    $check(in_array($status($job), ['completed', 'skipped'], true), 'Worker handles concurrent manual edit without failure');
    $calls = $provider->calls;
    global $wpdb;
    $countBefore = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . Queue::table() . ' WHERE scope=%s AND item_id=%d', 'product', $product->get_id()));
    $product = wc_get_product($product->get_id());
    $product->set_regular_price('888888');
    $product->save();
    Queue::enqueue('product', $product->get_id());
    $countAfter = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . Queue::table() . ' WHERE scope=%s AND item_id=%d', 'product', $product->get_id()));
    $check($countAfter === $countBefore && $provider->calls === $calls, 'Price-only save deduplicates completed source version');

    Config::update(['enabled' => false]);
    $stale = $makeProduct();
    Config::update(['enabled' => true]);
    $job = $jobFor($stale->get_id());
    $provider->during = static function () use ($stale): void {
        $fresh = wc_get_product($stale->get_id());
        $fresh->set_name('متن فارسی تازه');
        $fresh->save();
    };
    Queue::work($job);
    $check($status($job) === 'skipped' && !get_post_meta($stale->get_id(), '_admincafe_translations', true), 'Changed canonical source discards stale network result');
    Config::update(['enabled' => false]);
    $empty = $makeProduct();
    add_post_meta($empty->get_id(), '_admincafe_translations', [], true);
    $snapshot = Source::read('product', $empty->get_id());
    $captured = '';
    $sqlCapture = static function (string $sql) use (&$captured, $empty): string {
        if (str_starts_with($sql, 'UPDATE ') && str_contains($sql, '_admincafe_translations') && str_contains($sql, 'post_id=' . $empty->get_id())) { $captured = $sql; }
        return $sql;
    };
    add_filter('query', $sqlCapture);
    try {
        $check(Source::commit('product', $empty->get_id(), $snapshot, ['en' => ['name' => 'Generated']], ['en' => ['name' => hash('sha256', 'Generated')]]), 'Existing empty metadata commits through strict CAS');
    } finally { remove_filter('query', $sqlCapture); }
    $check(str_contains($captured, "AND meta_value='a:0:{}'"), 'Empty previous metadata remains an explicit SQL comparison');
    $snapshot = Source::read('product', $empty->get_id());
    $injected = false;
    // Inject a separate committed edit just before the worker starts its transaction.
    // SQLite cannot prove simultaneous MySQL row-lock scheduling; this tests the stale-write boundary.
    $race = static function (string $sql) use (&$injected, $empty): string {
        if (!$injected && $sql === 'START TRANSACTION') {
            $injected = true;
            update_post_meta($empty->get_id(), '_admincafe_translations', ['en' => ['name' => 'Concurrent manual name']]);
            Source::manual('product', $empty->get_id(), ['en' => ['name' => 'Concurrent manual name']]);
        }
        return $sql;
    };
    add_filter('query', $race);
    try {
        $check(!Source::commit('product', $empty->get_id(), $snapshot, ['en' => ['name' => 'Stale generated name']], []), 'Concurrent map edit prevents worker commit');
    } finally { remove_filter('query', $race); }
    $check($injected && get_post_meta($empty->get_id(), '_admincafe_translations', true)['en']['name'] === 'Concurrent manual name', 'Concurrent manual text survives rejected transaction');
    $check(!str_contains(wp_json_encode(Queue::status()), $fakeKey), 'Queue status never returns secret or raw provider detail');
    echo "Translation security: {$checks} checks passed; all outbound HTTP blocked or mocked.\n";
} finally {
    remove_filter('admincafe_translation_provider', $providerFilter);
    // Disable worker hooks before cleanup and restore every option modified here.
    $disabled = Config::raw();
    $disabled['enabled'] = false;
    update_option(Config::OPTION, $disabled, false);
    global $wpdb;
    foreach ($products as $id) {
        $rows = $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . Queue::table() . ' WHERE scope=%s AND item_id=%d', 'product', $id));
        foreach ($rows as $job) {
            wp_clear_scheduled_hook('admincafe_translation_work', [(int) $job]);
            if (function_exists('as_unschedule_all_actions')) { as_unschedule_all_actions('admincafe_translation_work', [(int) $job], 'admincafe-translation'); }
        }
        $wpdb->delete(Queue::table(), ['scope' => 'product', 'item_id' => $id]);
        wp_delete_post($id, true);
    }
    foreach ($options as $name => $value) {
        if ($value === null) { delete_option($name); } else { update_option($name, $value, false); }
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($users as $id) { wp_delete_user($id); }
    wp_set_current_user($previousUser);
    remove_filter('pre_http_request', $http, PHP_INT_MAX);
}
