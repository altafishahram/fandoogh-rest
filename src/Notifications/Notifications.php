<?php
namespace AdminCafe\Notifications;

use AdminCafe\Core\Settings;
use AdminCafe\Localization\Language;
use AdminCafe\Rest\Management;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

defined('ABSPATH') || exit;

/** Durable in-panel events and standards-based push, queued independently of browser tabs. */
final class Notifications
{
    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
        add_action('admincafe_order_created', [$this, 'newOrder']);
        add_action('admincafe_send_push', [$this, 'deliver'], 10, 4);
        add_action('admincafe_cleanup', [$this, 'cleanup']);
        add_filter('admincafe_management_bootstrap', static function (array $data): array {
            $data['push'] = self::pushStatus();
            return $data;
        });
    }

    public static function pushStatus(): array
    {
        $reason = '';
        if (!is_ssl()) {
            $reason = __('Push notifications require HTTPS.', 'admincafe');
        } elseif (!class_exists(WebPush::class)) {
            $reason = __('The Web Push dependency is missing. Install the packaged release.', 'admincafe');
        } elseif (!extension_loaded('openssl') || !extension_loaded('curl') || !extension_loaded('mbstring')) {
            $reason = __('Enable the PHP openssl, curl and mbstring extensions for push notifications.', 'admincafe');
        }
        $keys = get_option('admincafe_vapid', []);
        if (!$reason && empty($keys['publicKey'])) {
            try {
                $keys = VAPID::createVapidKeys();
                if (!add_option('admincafe_vapid', $keys, '', false)) {
                    $keys = get_option('admincafe_vapid', []);
                }
            } catch (\Throwable $error) {
                $reason = __('The server could not generate push credentials.', 'admincafe');
            }
        }
        return ['public_key' => $keys['publicKey'] ?? '', 'available' => $reason === '', 'reason' => $reason];
    }

    public function routes(): void
    {
        $permission = static fn(\WP_REST_Request $request) => Management::permission($request, 'admincafe_receive_notifications');
        foreach ([
            '/manage/notifications' => ['GET', 'listing'],
            '/manage/notifications/read' => ['POST', 'markRead'],
            '/manage/push/devices' => ['GET', 'devices'],
            '/manage/push/test' => ['POST', 'test'],
        ] as $path => [$method, $callback]) {
            register_rest_route('admincafe/v1', $path, ['methods' => $method, 'permission_callback' => $permission, 'callback' => [$this, $callback]]);
        }
        register_rest_route('admincafe/v1', '/manage/push/subscribe', [
            ['methods' => 'POST', 'permission_callback' => $permission, 'callback' => [$this, 'subscribe']],
            ['methods' => 'DELETE', 'permission_callback' => $permission, 'callback' => [$this, 'unsubscribe']],
        ]);
        register_rest_route('admincafe/v1', '/manage/push/devices/(?P<id>[a-f0-9]{64})', [
            'methods' => 'PATCH', 'permission_callback' => $permission, 'callback' => [$this, 'updateDevice'],
        ]);
    }

    public function newOrder(int $id): void
    {
        $order = wc_get_order($id);
        if (!$order || !$order->get_meta('_admincafe_channel') || $order->get_status() === 'checkout-draft') {
            return;
        }
        global $wpdb;
        $channel = (string) $order->get_meta('_admincafe_channel');
        $channels = ['table' => Language::staffText('Table'), 'counter' => Language::staffText('Counter'), 'pickup' => Language::staffText('Pickup'), 'delivery' => Language::staffText('Delivery')];
        $title = sprintf(Language::staffText('New order #%s'), $order->get_order_number());
        $body = ($channels[$channel] ?? $channel) . ($channel === 'table' ? ' · ' . sanitize_text_field($order->get_meta('_admincafe_table_label')) : '');
        $key = 'new:' . $id;
        // A UNIQUE event key makes repeated Woo callbacks harmless.
        $inserted = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->prefix}admincafe_events (event_key,order_id,channel,title,body,created_at) VALUES (%s,%d,%s,%s,%s,%s)", $key, $id, $channel, $title, $body, current_time('mysql', true)));
        if (!$inserted) {
            return;
        }
        $eventId = (int) $wpdb->insert_id;
        foreach (get_users(['capability' => 'admincafe_receive_notifications', 'fields' => 'ID']) as $userId) {
            foreach ((array) get_user_meta((int) $userId, '_admincafe_push_devices', true) as $deviceKey => $device) {
                if (in_array($channel, $device['channels'] ?? ['table', 'counter', 'pickup', 'delivery'], true)) {
                    self::queue($eventId, (int) $userId, (string) $deviceKey, 0);
                }
            }
        }
    }

    private static function queue(int $eventId, int $userId, string $deviceKey, int $attempt): void
    {
        $args = [$eventId, $userId, $deviceKey, $attempt];
        if (function_exists('as_enqueue_async_action')) {
            if ($attempt === 0) {
                as_enqueue_async_action('admincafe_send_push', $args, 'admincafe');
            } else {
                as_schedule_single_action(time() + min(900, 30 * (2 ** $attempt)), 'admincafe_send_push', $args, 'admincafe');
            }
        } else {
            wp_schedule_single_event(time() + ($attempt ? min(900, 30 * (2 ** $attempt)) : 1), 'admincafe_send_push', $args);
        }
    }

    public function deliver(int $eventId, int $userId, string $deviceKey, int $attempt = 0): void
    {
        if (!user_can($userId, 'admincafe_receive_notifications')) {
            delete_user_meta($userId, '_admincafe_push_devices');
            return;
        }
        $devices = (array) get_user_meta($userId, '_admincafe_push_devices', true);
        $previousDevices = $devices;
        $device = $devices[$deviceKey] ?? null;
        if (!$device || is_wp_error(self::validateSubscription($device['subscription'] ?? []))) {
            return;
        }
        global $wpdb;
        $event = $eventId ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}admincafe_events WHERE id=%d", $eventId), ARRAY_A) : null;
        if ($eventId && !$event) {
            return;
        }
        if ($event && !in_array($event['channel'], $device['channels'] ?? ['table', 'counter', 'pickup', 'delivery'], true)) {
            return;
        }
        $status = self::pushStatus();
        if (!$status['available']) {
            $devices[$deviceKey]['last_error'] = $status['reason'];
            update_user_meta($userId, '_admincafe_push_devices', $devices, $previousDevices);
            return;
        }
        $keys = get_option('admincafe_vapid');
        $payload = [
            'title' => $event['title'] ?? __('AdminCafe test notification', 'admincafe'),
            'body' => $event['body'] ?? __('Notifications are working on this device.', 'admincafe'),
            'tag' => 'admincafe-' . ($eventId ?: 'test'),
            'url' => Settings::panelUrl() . ($event ? '#orders/' . (int) $event['order_id'] : '#notifications'),
            'icon' => ADMINCAFE_URL . 'assets/icon-192.png',
        ];
        try {
            $sender = new WebPush(['VAPID' => ['subject' => home_url('/'), 'publicKey' => $keys['publicKey'], 'privateKey' => $keys['privateKey']]], ['TTL' => 3600, 'urgency' => 'high'], 10);
            $report = $sender->sendOneNotification(Subscription::create($device['subscription']), wp_json_encode($payload, JSON_UNESCAPED_UNICODE));
            if ($report->isSubscriptionExpired()) {
                unset($devices[$deviceKey]);
            } elseif (!$report->isSuccess()) {
                $devices[$deviceKey]['last_error'] = __('The browser push service could not deliver the notification.', 'admincafe');
                if ($attempt < 3) {
                    self::queue($eventId, $userId, $deviceKey, $attempt + 1);
                }
            } else {
                $devices[$deviceKey]['last_error'] = '';
                $devices[$deviceKey]['last_sent'] = gmdate(DATE_ATOM);
            }
        } catch (\Throwable $error) {
            $devices[$deviceKey]['last_error'] = __('Push delivery failed. Check outbound network access and PHP extensions.', 'admincafe');
            if ($attempt < 3) {
                self::queue($eventId, $userId, $deviceKey, $attempt + 1);
            }
        }
        // Compare the previous value so a job cannot resurrect an unsubscribed device.
        update_user_meta($userId, '_admincafe_push_devices', $devices, $previousDevices);
    }

    public function listing(\WP_REST_Request $request): array
    {
        global $wpdb;
        $userId = get_current_user_id();
        $after = absint($request->get_param('after'));
        $events = $wpdb->get_results($wpdb->prepare("SELECT e.*, IF(r.event_id IS NULL,0,1) AS `read` FROM {$wpdb->prefix}admincafe_events e LEFT JOIN {$wpdb->prefix}admincafe_event_reads r ON e.id=r.event_id AND r.user_id=%d WHERE e.id>%d ORDER BY e.id DESC LIMIT 100", $userId, $after), ARRAY_A);
        foreach ($events as &$event) {
            $event['id'] = (int) $event['id'];
            $event['order_id'] = (int) $event['order_id'];
            $event['read'] = (bool) $event['read'];
            $event['created_at'] = str_replace(' ', 'T', $event['created_at']) . 'Z';
            unset($event['event_key']);
        }
        unset($event);
        $unread = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}admincafe_events e LEFT JOIN {$wpdb->prefix}admincafe_event_reads r ON e.id=r.event_id AND r.user_id=%d WHERE r.event_id IS NULL", $userId));
        return ['events' => $events, 'unread' => $unread, 'push' => self::pushStatus()];
    }

    public function markRead(\WP_REST_Request $request): array|\WP_Error
    {
        $ids = $request->get_param('ids');
        if (!is_array($ids) || count($ids) > 100) {
            return new \WP_Error('admincafe_events', __('Supply up to 100 notification IDs.', 'admincafe'), ['status' => 400]);
        }
        global $wpdb;
        foreach (array_unique(array_map('absint', $ids)) as $id) {
            $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->prefix}admincafe_event_reads (event_id,user_id) SELECT id,%d FROM {$wpdb->prefix}admincafe_events WHERE id=%d", get_current_user_id(), $id));
        }
        return ['read' => true];
    }

    public static function validateSubscription(array $subscription): bool|\WP_Error
    {
        $endpoint = $subscription['endpoint'] ?? '';
        $url = is_string($endpoint) ? wp_parse_url($endpoint) : null;
        $host = strtolower($url['host'] ?? '');
        $exact = ['fcm.googleapis.com', 'updates.push.services.mozilla.com', 'web.push.apple.com'];
        $allowed = in_array($host, $exact, true) || preg_match('/^[a-z0-9.-]+\.(push\.apple\.com|notify\.windows\.com|wns\.windows\.com)$/D', $host);
        $keys = $subscription['keys'] ?? [];
        $decode = static fn($key) => is_string($key) && preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $key) ? base64_decode(strtr($key, '-_', '+/'), true) : false;
        $public = is_array($keys) ? $decode($keys['p256dh'] ?? '') : false;
        $auth = is_array($keys) ? $decode($keys['auth'] ?? '') : false;
        if (!$url || ($url['scheme'] ?? '') !== 'https' || !$allowed || isset($url['user']) || isset($url['pass']) || isset($url['fragment']) || (($url['port'] ?? 443) !== 443) || strlen($endpoint) > 2048 || !$public || strlen($public) !== 65 || ord($public[0]) !== 4 || !$auth || strlen($auth) !== 16) {
            return new \WP_Error('admincafe_subscription', __('Invalid browser push subscription.', 'admincafe'), ['status' => 400]);
        }
        return true;
    }

    public function subscribe(\WP_REST_Request $request): array|\WP_Error
    {
        $input = $request->get_json_params();
        if (!is_array($input) || !is_array($input['subscription'] ?? null)) {
            return new \WP_Error('admincafe_subscription', __('A browser subscription is required.', 'admincafe'), ['status' => 400]);
        }
        $valid = self::validateSubscription($input['subscription']);
        if (is_wp_error($valid)) {
            return $valid;
        }
        $status = self::pushStatus();
        if (!$status['available']) {
            return new \WP_Error('admincafe_push_unavailable', $status['reason'], ['status' => 409]);
        }
        $devices = (array) get_user_meta(get_current_user_id(), '_admincafe_push_devices', true);
        $key = hash('sha256', $input['subscription']['endpoint']);
        if (!isset($devices[$key]) && count($devices) >= 10) {
            return new \WP_Error('admincafe_device_limit', __('Remove an old device before adding another.', 'admincafe'), ['status' => 409]);
        }
        $channels = $input['channels'] ?? ['table', 'counter', 'pickup', 'delivery'];
        if (!is_array($channels)) {
            return new \WP_Error('admincafe_channels', __('Choose valid notification channels.', 'admincafe'), ['status' => 400]);
        }
        if (array_filter($channels, static fn($channel): bool => !is_string($channel) || !in_array($channel, ['table', 'counter', 'pickup', 'delivery'], true))) {
            return new \WP_Error('admincafe_channels', __('Choose valid notification channels.', 'admincafe'), ['status' => 400]);
        }
        $devices[$key] = [
            'subscription' => ['endpoint' => $input['subscription']['endpoint'], 'keys' => array_intersect_key($input['subscription']['keys'], ['p256dh' => true, 'auth' => true]), 'contentEncoding' => 'aes128gcm'],
            'label' => sanitize_text_field(mb_substr(is_scalar($input['label'] ?? '') ? (string) ($input['label'] ?? '') : '', 0, 100)),
            'channels' => array_values(array_intersect(['table', 'counter', 'pickup', 'delivery'], $channels)),
            'created_at' => gmdate(DATE_ATOM), 'last_error' => '',
        ];
        update_user_meta(get_current_user_id(), '_admincafe_push_devices', $devices);
        return ['subscribed' => true];
    }

    public function unsubscribe(\WP_REST_Request $request): array
    {
        $endpoint = $request->get_param('endpoint');
        $devices = (array) get_user_meta(get_current_user_id(), '_admincafe_push_devices', true);
        unset($devices[hash('sha256', is_string($endpoint) ? $endpoint : '')]);
        update_user_meta(get_current_user_id(), '_admincafe_push_devices', $devices);
        return ['deleted' => true];
    }

    public function devices(): array
    {
        $devices = [];
        foreach ((array) get_user_meta(get_current_user_id(), '_admincafe_push_devices', true) as $key => $device) {
            $devices[] = ['id' => $key, 'endpoint' => $device['subscription']['endpoint'], 'label' => $device['label'], 'channels' => $device['channels'], 'created_at' => $device['created_at'], 'last_error' => $device['last_error'] ?? '', 'last_sent' => $device['last_sent'] ?? null];
        }
        return $devices;
    }

    public function updateDevice(\WP_REST_Request $request): array|\WP_Error
    {
        $key = (string) $request->get_param('id');
        $channels = $request->get_param('channels');
        if (!is_array($channels) || array_filter($channels, static fn($channel): bool => !is_string($channel) || !in_array($channel, ['table', 'counter', 'pickup', 'delivery'], true))) {
            return new \WP_Error('admincafe_channels', __('Choose valid notification channels.', 'admincafe'), ['status' => 400]);
        }
        $devices = (array) get_user_meta(get_current_user_id(), '_admincafe_push_devices', true);
        if (!isset($devices[$key])) {
            return new \WP_Error('admincafe_device', __('Device not found.', 'admincafe'), ['status' => 404]);
        }
        $previous = $devices;
        $devices[$key]['channels'] = array_values(array_unique($channels));
        if (!update_user_meta(get_current_user_id(), '_admincafe_push_devices', $devices, $previous) && $devices !== $previous) {
            return new \WP_Error('admincafe_device_busy', __('Device settings changed. Refresh and try again.', 'admincafe'), ['status' => 409]);
        }
        return ['updated' => true];
    }

    public function test(): array|\WP_Error
    {
        $status = self::pushStatus();
        if (!$status['available']) {
            return new \WP_Error('admincafe_push_unavailable', $status['reason'], ['status' => 409]);
        }
        $devices = (array) get_user_meta(get_current_user_id(), '_admincafe_push_devices', true);
        if (!$devices) {
            return new \WP_Error('admincafe_no_device', __('Enable notifications on this device first.', 'admincafe'), ['status' => 400]);
        }
        foreach ($devices as $key => $device) {
            self::queue(0, get_current_user_id(), $key, 0);
        }
        return ['queued' => count($devices)];
    }

    public function cleanup(): void
    {
        global $wpdb;
        $cutoff = gmdate('Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS);
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}admincafe_events WHERE created_at<%s", $cutoff));
        $wpdb->query("DELETE FROM {$wpdb->prefix}admincafe_event_reads WHERE event_id NOT IN (SELECT id FROM {$wpdb->prefix}admincafe_events)");
        // Retain the non-autoload request ledger. Deleting it would make an old retry
        // capable of creating a second order, especially after an interrupted request.
    }
}
