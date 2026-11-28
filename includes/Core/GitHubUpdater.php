<?php
namespace RMM\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides native WordPress updates from the public GitHub Releases feed.
 *
 * @package FondoghOnlineMenu
 */
class GitHubUpdater {

    const REPOSITORY = 'https://github.com/altafishahram/fandoogh-rest';
    const RELEASE_API = 'https://api.github.com/repos/altafishahram/fandoogh-rest/releases/latest';
    const PACKAGE_NAME = 'fondogh-online-menu.zip';
    const PLUGIN_SLUG = 'fondogh-online-menu';
    const CACHE_KEY = 'rmm_github_latest_release_v3';
    const CACHE_TTL = 10 * MINUTE_IN_SECONDS;

    public function __construct() {
        add_filter('update_plugins_github.com', [$this, 'filter_update'], 10, 4);
        add_filter('plugins_api', [$this, 'filter_plugin_info'], 20, 3);
        add_filter('upgrader_pre_download', [$this, 'verify_package_integrity'], 10, 3);
    }

    /**
     * Add the latest release to WordPress' normal plugin update transient.
     *
     * @param mixed  $update
     * @param array  $plugin_data
     * @param string $plugin_file
     * @param array  $locales
     * @return mixed
     */
    public function filter_update($update, $plugin_data, $plugin_file, $locales) {
        if (!defined('RMM_PLUGIN_BASENAME') || $plugin_file !== RMM_PLUGIN_BASENAME) {
            return $update;
        }

        $release = $this->get_latest_release();
        if (!is_array($release)) {
            return $update;
        }

        $update_object = self::create_update_object($release, RMM_VERSION, $plugin_file);
        return $update_object ?: $update;
    }

    /**
     * Provide the details modal shown by the WordPress Plugins screen.
     *
     * @param mixed  $result
     * @param string $action
     * @param object $args
     * @return mixed
     */
    public function filter_plugin_info($result, $action, $args) {
        if (
            $action !== 'plugin_information'
            || !is_object($args)
            || empty($args->slug)
            || $args->slug !== self::PLUGIN_SLUG
        ) {
            return $result;
        }

        $release = $this->get_latest_release();
        if (!is_array($release)) {
            return $result;
        }

        $version = self::get_release_version($release);
        $package = self::get_package_url($release);
        $package_hash = self::get_package_sha256($release);
        if (!$version || !$package || !$package_hash) {
            return $result;
        }

        $changelog = !empty($release['body']) ? wp_kses_post($release['body']) : '';
        $compatibility = self::get_compatibility_metadata();

        return (object) [
            'name'          => 'منو آنلاین فندق (Fondogh Online Menu)',
            'slug'          => self::PLUGIN_SLUG,
            'version'       => $version,
            'author'        => 'Fondogh Team',
            'author_profile'=> self::REPOSITORY,
            'homepage'      => self::REPOSITORY,
            'requires'      => $compatibility['requires'],
            'tested'        => $compatibility['tested'],
            'requires_php'  => $compatibility['requires_php'],
            'download_link' => $package,
            'last_updated'  => isset($release['published_at']) ? $release['published_at'] : '',
            'sections'      => [
                'description' => 'افزونه مدیریت منوی آنلاین و سفارش‌گیری رستوران و کافه.',
                'changelog'   => $changelog,
            ],
        ];
    }

    /**
     * Fetch and cache the latest stable GitHub release.
     *
     * @return array|false
     */
    private function get_latest_release() {
        $cached = get_transient(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $response = wp_remote_get(self::RELEASE_API, [
            'timeout'     => 8,
            'redirection' => 3,
            'headers'     => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'Fondogh-Online-Menu-WordPress-Updater',
            ],
        ]);

        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $release = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($release) || !self::get_release_version($release) || !self::get_package_url($release) || !self::get_package_sha256($release)) {
            return false;
        }

        set_transient(self::CACHE_KEY, $release, self::CACHE_TTL);
        return $release;
    }

    /**
     * Return a normalized release version without its optional leading v.
     *
     * @param array $release
     * @return string|false
     */
    private static function get_release_version($release) {
        if (empty($release['tag_name']) || !is_string($release['tag_name'])) {
            return false;
        }

        $version = preg_replace('/^v/i', '', trim($release['tag_name']));
        if (!preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)) {
            return false;
        }

        return $version;
    }

    /**
     * Return the exact release ZIP asset after validating its download URL.
     *
     * @param array $release
     * @return array|false
     */
    private static function get_package_asset($release) {
        if (empty($release['assets']) || !is_array($release['assets'])) {
            return false;
        }

        foreach ($release['assets'] as $asset) {
            if (
                !is_array($asset)
                || empty($asset['name'])
                || $asset['name'] !== self::PACKAGE_NAME
                || empty($asset['browser_download_url'])
                || !is_string($asset['browser_download_url'])
            ) {
                continue;
            }

            $parts = parse_url($asset['browser_download_url']);
            if (
                is_array($parts)
                && isset($parts['scheme'], $parts['host'], $parts['path'])
                && strtolower($parts['scheme']) === 'https'
                && strtolower($parts['host']) === 'github.com'
                && strpos($parts['path'], '/altafishahram/fandoogh-rest/releases/download/') === 0
            ) {
                return $asset;
            }
        }

        return false;
    }

    /**
     * Return the SHA-256 digest published by GitHub for the exact ZIP asset.
     *
     * @param array $release
     * @return string|false
     */
    private static function get_package_sha256($release) {
        $asset = self::get_package_asset($release);
        if (!$asset || empty($asset['digest']) || !is_string($asset['digest'])) {
            return false;
        }

        if (!preg_match('/^sha256:([a-f0-9]{64})$/i', $asset['digest'], $matches)) {
            return false;
        }

        return strtolower($matches[1]);
    }

    /**
     * Only accept the ZIP asset produced by this repository's release workflow.
     *
     * @param array $release
     * @return string|false
     */
    private static function get_package_url($release) {
        $asset = self::get_package_asset($release);
        return $asset ? $asset['browser_download_url'] : false;
    }

    /**
     * Read WordPress/PHP compatibility from the plugin header and readme.
     *
     * Conservative fallbacks keep update metadata safe if a file cannot be read.
     *
     * @return array
     */
    public static function get_compatibility_metadata() {
        $plugin_file = defined('RMM_PLUGIN_FILE')
            ? RMM_PLUGIN_FILE
            : dirname(__DIR__, 2) . '/fondogh-online-menu.php';
        $readme_file = dirname($plugin_file) . '/readme.txt';

        $plugin_header = is_readable($plugin_file) ? file_get_contents($plugin_file) : '';
        $readme = is_readable($readme_file) ? file_get_contents($readme_file) : '';

        $requires = self::read_metadata_value($plugin_header, 'Requires at least', '5.8');
        $requires_php = self::read_metadata_value($plugin_header, 'Requires PHP', '8.2');
        $tested = self::read_metadata_value($readme, 'Tested up to', '7.1');

        return [
            'requires' => $requires,
            'tested' => $tested,
            'requires_php' => $requires_php,
        ];
    }

    /**
     * Extract a metadata value from a plugin header or readme header.
     *
     * @param string $contents
     * @param string $label
     * @param string $fallback
     * @return string
     */
    private static function read_metadata_value($contents, $label, $fallback) {
        if (preg_match('/^\\s*(?:\\*\\s*)?' . preg_quote($label, '/') . ':\\s*([^\\r\\n]+)/mi', $contents, $matches)) {
            $value = trim($matches[1]);
            if ($value !== '') {
                return $value;
            }
        }

        return $fallback;
    }

    /**
     * Verify the downloaded plugin ZIP against GitHub's SHA-256 asset digest.
     *
     * This detects corrupted or substituted package bytes between release
     * metadata retrieval and installation. The digest is supplied by GitHub's
     * Releases API; it is not a publisher signature and does not protect against
     * compromise of the repository/release account itself.
     *
     * @param string|WP_Error|false $reply
     * @param string $package
     * @param object $upgrader
     * @return string|WP_Error|false
     */
    public function verify_package_integrity($reply, $package, $upgrader) {
        if ($reply !== false) {
            return $reply;
        }

        $release = $this->get_latest_release();
        if (!is_array($release)) {
            return new \WP_Error('rmm_release_integrity_unavailable', 'Fondogh update verification failed: trusted release metadata is unavailable.');
        }

        $expected_url = self::get_package_url($release);
        if (!$expected_url || !is_string($package) || $package !== $expected_url) {
            return $reply;
        }

        $expected_hash = self::get_package_sha256($release);
        if (!$expected_hash || !function_exists('download_url')) {
            return new \WP_Error('rmm_package_integrity_unavailable', 'Fondogh update verification failed: a valid SHA-256 digest is unavailable.');
        }

        $downloaded_file = download_url($package, 300);
        if (is_wp_error($downloaded_file)) {
            return $downloaded_file;
        }

        $actual_hash = is_string($downloaded_file) && is_file($downloaded_file)
            ? hash_file('sha256', $downloaded_file)
            : false;

        if (!is_string($actual_hash) || !hash_equals($expected_hash, strtolower($actual_hash))) {
            if (is_string($downloaded_file) && is_file($downloaded_file)) {
                @unlink($downloaded_file);
            }

            return new \WP_Error('rmm_package_integrity_failed', 'Fondogh update blocked: the downloaded ZIP SHA-256 digest does not match the published release digest.');
        }

        return $downloaded_file;
    }

    /**
     * Build a WordPress update response from a release payload.
     * Kept pure so version and package validation can be unit tested.
     *
     * @param array  $release
     * @param string $current_version
     * @param string $plugin_file
     * @return object|false
     */
    public static function create_update_object($release, $current_version, $plugin_file) {
        if (!is_array($release)) {
            return false;
        }

        $version = self::get_release_version($release);
        $package = self::get_package_url($release);
        $package_hash = self::get_package_sha256($release);
        if (!$version || !$package || !$package_hash || !version_compare($version, $current_version, '>')) {
            return false;
        }

        $compatibility = self::get_compatibility_metadata();

        return (object) [
            'id'          => self::REPOSITORY,
            'slug'        => self::PLUGIN_SLUG,
            'plugin'      => $plugin_file,
            // WordPress requires `version` for Update URI responses and maps it to `new_version`.
            'version'     => $version,
            'new_version' => $version,
            'url'         => self::REPOSITORY,
            'package'     => $package,
            'package_hash'=> $package_hash,
            'tested'      => $compatibility['tested'],
            'requires'    => $compatibility['requires'],
            'requires_php'=> $compatibility['requires_php'],
        ];
    }
}
