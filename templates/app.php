<?php
defined('ABSPATH') || exit;
$language = $panel ? 'fa' : \FandooghRest\Localization\Language::current();
$publicSettings = \FandooghRest\Core\Settings::publicSettings($language);
?>
<!doctype html>
<html lang="<?php echo esc_attr(\FandooghRest\Localization\Language::htmlLocale($language)); ?>" dir="<?php echo esc_attr(\FandooghRest\Localization\Language::direction($language)); ?>">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?php echo esc_attr(\FandooghRest\Core\Settings::get('accent')); ?>">
    <meta name="robots" content="<?php echo $panel ? 'noindex,nofollow' : 'index,follow'; ?>">
    <title><?php echo esc_html(($panel ? \FandooghRest\Core\Settings::get('restaurant_name') : $publicSettings['restaurant_name']) . ' | ' . ($panel ? __('Management panel', 'fandoogh-rest') : __('Menu', 'fandoogh-rest'))); ?></title>
    <?php if ($panel): ?>
        <link rel="manifest" href="<?php echo esc_url(\FandooghRest\Core\Settings::panelUrl() . 'manifest.webmanifest'); ?>">
        <link rel="apple-touch-icon" href="<?php echo esc_url(FANDOOGH_REST_URL . 'assets/icon-192.png'); ?>">
    <?php endif; ?>
    <?php wp_head(); ?>
</head>
<body class="admincafe-standalone" style="margin:0;background:<?php echo esc_attr(\FandooghRest\Core\Settings::get('background')); ?>">
<?php wp_body_open(); ?>
<?php echo \FandooghRest\Core\Assets::mount($panel ? 'panel' : 'menu', ['standalone' => true, 'logoutUrl' => wp_nonce_url(add_query_arg('logout', 1, \FandooghRest\Core\Settings::panelUrl()), 'admincafe_logout')]); ?>
<noscript><p><?php echo esc_html__('Enable browser JavaScript to use the menu and panel.', 'fandoogh-rest'); ?></p></noscript>
<?php wp_footer(); ?>
</body>
</html>
