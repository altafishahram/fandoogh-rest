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
    const CACHE_KEY = 'rmm_github_latest_release_v1';

    public function __construct() {
        add_filter('update_plugins_github.com', [$this, 'filter_update'], 10, 4);
        add_filter('plugins_api', [$this, 'filter_plugin_info'], 20, 3);
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
        if (!$version || !$package) {
            return $result;
        }

        $changelog = !empty($release['body']) ? wp_kses_post($release['body']) : '';

        return (object) [
            'name'          => 'منو آنلاین فندق (Fondogh Online Menu)',
            'slug'          => self::PLUGIN_SLUG,
            'version'       => $version,
            'author'        => 'Fondogh Team',
            'author_profile'=> self::REPOSITORY,
            'homepage'      => self::REPOSITORY,
            'requires'      => '5.8',
            'tested'        => '7.1',
            'requires_php'  => '7.4',
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
        if (!is_array($release) || !self::get_release_version($release) || !self::get_package_url($release)) {
            return false;
        }

        set_transient(self::CACHE_KEY, $release, 6 * HOUR_IN_SECONDS);
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
     * Only accept the ZIP asset produced by this repository's release workflow.
     *
     * @param array $release
     * @return string|false
     */
    private static function get_package_url($release) {
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
                return $asset['browser_download_url'];
            }
        }

        return false;
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
        if (!$version || !$package || !version_compare($version, $current_version, '>')) {
            return false;
        }

        return (object) [
            'id'          => self::REPOSITORY,
            'slug'        => self::PLUGIN_SLUG,
            'plugin'      => $plugin_file,
            'new_version' => $version,
            'url'         => self::REPOSITORY,
            'package'     => $package,
            'tested'      => '7.1',
            'requires'    => '5.8',
            'requires_php'=> '7.4',
        ];
    }
}
