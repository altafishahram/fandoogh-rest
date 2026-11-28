<?php

use PHPUnit\Framework\TestCase;
use RMM\Core\GitHubUpdater;

class GitHubUpdaterTest extends TestCase {

    private function release($tag = 'v1.3.7', $package = 'https://github.com/altafishahram/fandoogh-rest/releases/download/v1.3.7/fondogh-online-menu.zip') {
        return [
            'tag_name' => $tag,
            'html_url' => 'https://github.com/altafishahram/fandoogh-rest/releases/tag/' . $tag,
            'signature_verified' => true,
            'signed_manifest' => [
                'tag' => $tag,
                'version' => ltrim($tag, 'v'),
                'package' => 'fondogh-online-menu.zip',
                'sha256' => str_repeat('a', 64),
            ],
            'assets' => [
                [
                    'name' => 'fondogh-online-menu.zip',
                    'browser_download_url' => $package,
                ],
            ],
        ];
    }

    public function test_builds_wordpress_update_for_newer_release() {
        $update = GitHubUpdater::create_update_object($this->release(), '1.3.6', 'fondogh-online-menu/fondogh-online-menu.php');
        $this->assertIsObject($update);
        $this->assertSame('1.3.7', $update->version);
        $this->assertSame('1.3.7', $update->new_version);
        $this->assertSame('fondogh-online-menu/fondogh-online-menu.php', $update->plugin);
        $this->assertSame('a' . str_repeat('a', 63), $update->package_hash);
    }

    public function test_does_not_offer_same_or_older_version() {
        $this->assertFalse(GitHubUpdater::create_update_object($this->release('v1.3.6'), '1.3.6', 'plugin.php'));
        $this->assertFalse(GitHubUpdater::create_update_object($this->release('v1.3.5'), '1.3.6', 'plugin.php'));
    }

    public function test_rejects_non_github_or_non_https_package_urls() {
        $this->assertFalse(GitHubUpdater::create_update_object($this->release('v1.3.7', 'http://github.com/altafishahram/fandoogh-rest/releases/download/v1.3.7/fondogh-online-menu.zip'), '1.3.6', 'plugin.php'));
        $this->assertFalse(GitHubUpdater::create_update_object($this->release('v1.3.7', 'https://evil.example/fondogh-online-menu.zip'), '1.3.6', 'plugin.php'));
    }

    public function test_rejects_unsigned_release_metadata() {
        $release = $this->release();
        unset($release['signature_verified']);
        $this->assertFalse(GitHubUpdater::create_update_object($release, '1.3.6', 'plugin.php'));
    }

    public function test_accepts_valid_detached_signature_and_manifest() {
        $manifest = '{"package":"fondogh-online-menu.zip","schema":1,"sha256":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","tag":"v1.3.7","version":"1.3.7"}' . "\n";
        $signature = 'XWk4DoZOafZyi+Kdc+ujxIXLW/UbvLCuvyAHBnaGO1AWjo3C5ZQUjSiCwNj2AsXpPcuuL8xO5oxVtWB9/JrJ0FT5p79uZ19c60Vq2TFeUH/93L2UEU1J1n5EHiSPS80Eq3jh6KA8a53FgnQUlP1wVu5/X8dmPkiN6mIS1ykBWHJlvAawyfkr9AbLQ6ps+25/F+Q+9y0Rbb4UMUkPMwxvU+NNVf3b0Oy+u1E2AG0pBbtraiKcphRTuu5bwJ/zS+WZ/K5aJl7OabfrZwHsGEUj46YPDD24npdC4XJ6lctBEN3B9bveT/FPsDxnPgzvvxIG8Cy8aXgVz6+/vAxEaQsG+QynOHdpA+sNTuMFuiTTNmRsp+eWXc4SBY8kzZN9pAkhQikyjHz+yTQKVbQy6EyuwAnZi+6Q2sW8WcXHcN+ESi/X12VoVvsA/1W/Wz6uzRTO7PxL3Kc5POUWF1P2zlNi+SNrYnhdaLl+W7n4N8mqdPkDRqD0sAembcWu7UdnDRqr';
        $verified = GitHubUpdater::verify_signed_manifest($manifest, $signature, 'v1.3.7');
        $this->assertIsArray($verified);
        $this->assertSame('1.3.7', $verified['version']);
    }

    public function test_rejects_invalid_manifest_signature_and_metadata() {
        $manifest = json_encode([
            'schema' => 1,
            'tag' => 'v1.3.7',
            'version' => '1.3.7',
            'package' => 'fondogh-online-menu.zip',
            'sha256' => str_repeat('a', 64),
        ], JSON_UNESCAPED_SLASHES);
        $this->assertFalse(GitHubUpdater::verify_signed_manifest($manifest, base64_encode('not-a-signature'), 'v1.3.7'));
        $this->assertFalse(GitHubUpdater::verify_signed_manifest($manifest, base64_encode('not-a-signature'), 'v1.3.8'));
    }

    public function test_rejects_release_without_manifest_digest() {
        $release = $this->release();
        unset($release['signed_manifest']['sha256']);
        $this->assertFalse(GitHubUpdater::create_update_object($release, '1.3.6', 'plugin.php'));
    }

    public function test_update_metadata_matches_plugin_header_and_readme() {
        $metadata = GitHubUpdater::get_compatibility_metadata();
        $this->assertSame('8.2', $metadata['requires_php']);
        $this->assertSame('5.8', $metadata['requires']);
        $this->assertSame('7.1', $metadata['tested']);
    }
}
