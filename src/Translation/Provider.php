<?php
namespace AdminCafe\Translation;
defined('ABSPATH') || exit;
interface Provider {
    public function translate(array $texts, string $target): array|\WP_Error;
}
