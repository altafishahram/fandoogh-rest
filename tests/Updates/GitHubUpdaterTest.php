<?php

use PHPUnit\Framework\TestCase;
use RMM\Core\GitHubUpdater;

class GitHubUpdaterTest extends TestCase {

    private function release($tag = 'v1.3.2', $package = 'https://github.com/altafishahram/fandoogh-rest/releases/download/v1.3.2/fondogh-online-menu.zip') {
        return [
            'tag_name' => $tag,
            'html_url' => 'https://github.com/altafishahram/fandoogh-rest/releases/tag/' . $tag,
            'assets' => [
                [
                    'name' => 'fondogh-online-menu.zip',
                    'browser_download_url' => $package,
                ],
            ],
        ];
    }

    public function test_builds_wordpress_update_for_newer_release() {
        $update = GitHubUpdater::create_update_object(
            $this->release(),
            '1.3.1',
            'fondogh-online-menu/fondogh-online-menu.php'
        );

        $this->assertIsObject($update);
        $this->assertSame('1.3.2', $update->new_version);
        $this->assertSame('fondogh-online-menu/fondogh-online-menu.php', $update->plugin);
        $this->assertSame(
            'https://github.com/altafishahram/fandoogh-rest/releases/download/v1.3.2/fondogh-online-menu.zip',
            $update->package
        );
    }

    public function test_does_not_offer_same_or_older_version() {
        $this->assertFalse(
            GitHubUpdater::create_update_object(
                $this->release('v1.3.1'),
                '1.3.1',
                'fondogh-online-menu/fondogh-online-menu.php'
            )
        );

        $this->assertFalse(
            GitHubUpdater::create_update_object(
                $this->release('v1.3.0'),
                '1.3.1',
                'fondogh-online-menu/fondogh-online-menu.php'
            )
        );
    }

    public function test_rejects_non_github_or_non_https_package_urls() {
        $this->assertFalse(
            GitHubUpdater::create_update_object(
                $this->release('v1.3.2', 'http://github.com/altafishahram/fandoogh-rest/releases/download/v1.3.2/fondogh-online-menu.zip'),
                '1.3.1',
                'fondogh-online-menu/fondogh-online-menu.php'
            )
        );

        $this->assertFalse(
            GitHubUpdater::create_update_object(
                $this->release('v1.3.2', 'https://evil.example/fondogh-online-menu.zip'),
                '1.3.1',
                'fondogh-online-menu/fondogh-online-menu.php'
            )
        );
    }

    public function test_rejects_malformed_release_version() {
        $this->assertFalse(
            GitHubUpdater::create_update_object(
                $this->release('latest'),
                '1.3.1',
                'fondogh-online-menu/fondogh-online-menu.php'
            )
        );
    }

    public function test_rejects_release_without_expected_package_asset() {
        $release = $this->release();
        $release['assets'][0]['name'] = 'source-code.zip';

        $this->assertFalse(
            GitHubUpdater::create_update_object(
                $release,
                '1.3.1',
                'fondogh-online-menu/fondogh-online-menu.php'
            )
        );
    }
}
