<?php
namespace RMM\FrontendDashboard;

if (!defined('ABSPATH')) {
    exit;
}

$is_logged_in = is_user_logged_in();
$user = wp_get_current_user();
$is_auth = $is_logged_in && AuthController::is_authorized($user);
$slug = \RMM\Core\Settings::get('dashboard_slug', 'restaurant-portal');

// Branches available (scoped to authorized user branches)
$branches = [];
if ($is_auth) {
    $allowed_branch_ids = AuthController::get_user_branches($user->ID);
    if (!empty($allowed_branch_ids)) {
        $branches = get_terms([
            'taxonomy'   => \RMM\Branches\BranchTaxonomy::TAXONOMY,
            'hide_empty' => false,
            'include'    => $allowed_branch_ids,
        ]);
        if (is_wp_error($branches)) {
            $branches = [];
        }
    }
}
$active_branch_id = \RMM\Branches\BranchContext::get_active_branch_id();
if (!empty($branches)) {
    $allowed_ids_map = wp_list_pluck($branches, 'term_id');
    if (!in_array($active_branch_id, $allowed_ids_map, true)) {
        $active_branch_id = (int) $allowed_ids_map[0];
    }
}

$all_enabled_langs = \RMM\I18n\MultiLangManager::get_enabled_languages();
$trans_langs = array_filter($all_enabled_langs, function($k) { return $k !== 'fa'; }, ARRAY_FILTER_USE_KEY);

$login_error = isset($_GET['login_error']) ? wp_strip_all_tags(urldecode($_GET['login_error'])) : '';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php _e('پیشخوان هوشمند رستوران | منو آنلاین فندق', 'fondogh-online-menu'); ?></title>
    
    <!-- Theme Flash Prevention Script -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('rmm_portal_theme');
                if (!savedTheme) {
                    savedTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-theme', savedTheme);
            } catch (e) {}
        })();
    </script>

    <!-- Google Fonts: Vazirmatn -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- PWA Meta -->
    <link rel="manifest" href="<?php echo esc_url(home_url('/' . $slug . '/manifest.json')); ?>">
    <meta name="theme-color" content="#dc2626">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="منو فندق">

    <!-- Fonts & Portal CSS -->
    <link rel="stylesheet" href="<?php echo esc_url(RMM_PLUGIN_URL . 'assets/css/portal-pwa.css?v=' . RMM_VERSION); ?>">
</head>
<body class="rmm-portal-body">

    <!-- Ambient Glowing Orbs for Glassmorphism Depth -->
    <div class="rmm-ambient-orb orb-1" aria-hidden="true"></div>
    <div class="rmm-ambient-orb orb-2" aria-hidden="true"></div>
    <div class="rmm-ambient-orb orb-3" aria-hidden="true"></div>

    <!-- Modern Toast Notifications Container -->
    <div id="rmm-toast-container" class="rmm-toast-container" aria-live="polite"></div>

<?php if (!$is_auth) : ?>
    <!-- GLASSMORPHIC LOGIN SCREEN -->
    <div class="rmm-login-container">
        <!-- Floating Theme Switcher on Login -->
        <button id="rmm-login-theme-btn" class="rmm-login-theme-btn" title="<?php _e('تغییر تم روشن / تیره', 'fondogh-online-menu'); ?>" type="button">
            <span class="theme-icon sun">☀️</span>
            <span class="theme-icon moon">🌙</span>
        </button>

        <div class="rmm-login-card">
            <div class="rmm-login-logo">
                <div class="rmm-logo-glow-ring">
                    <span class="rmm-logo-icon">🍽️</span>
                </div>
                <h2><?php _e('ورود به پیشخوان رستوران', 'fondogh-online-menu'); ?></h2>
                <p><?php _e('سامانه مدیریت آنلاین منو و سفارشات هوشمند فندق', 'fondogh-online-menu'); ?></p>
            </div>

            <?php if (!empty($login_error)) : ?>
                <div class="rmm-login-alert">
                    <span>⚠️</span> <span><?php echo esc_html($login_error); ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(home_url('/' . $slug . '/')); ?>" class="rmm-login-form">
                <?php wp_nonce_field('rmm_portal_login', 'rmm_login_nonce'); ?>
                
                <div class="rmm-form-group">
                    <label for="log"><?php _e('نام کاربری یا ایمیل:', 'fondogh-online-menu'); ?></label>
                    <div class="rmm-input-with-icon">
                        <span class="input-icon">👤</span>
                        <input type="text" name="log" id="log" required autocomplete="username" placeholder="<?php _e('نام کاربری...', 'fondogh-online-menu'); ?>">
                    </div>
                </div>

                <div class="rmm-form-group">
                    <label for="pwd"><?php _e('رمز عبور:', 'fondogh-online-menu'); ?></label>
                    <div class="rmm-input-with-icon">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="pwd" id="pwd" required autocomplete="current-password" placeholder="••••••••">
                    </div>
                </div>

                <div class="rmm-form-row">
                    <label class="rmm-checkbox-label">
                        <input type="checkbox" name="rememberme" value="forever">
                        <span><?php _e('مرا به خاطر بسپار', 'fondogh-online-menu'); ?></span>
                    </label>
                </div>

                <button type="submit" name="rmm_portal_login_submit" class="rmm-btn-login">
                    <span><?php _e('ورود به پیشخوان مدیریت', 'fondogh-online-menu'); ?></span>
                    <span class="rmm-btn-arrow">&larr;</span>
                </button>
            </form>

            <div class="rmm-login-footer">
                <a href="<?php echo esc_url(home_url('/')); ?>">&rarr; <?php _e('بازگشت به صفحه اصلی سایت', 'fondogh-online-menu'); ?></a>
            </div>
        </div>
    </div>

<?php else : ?>
    <!-- STANDALONE DASHBOARD APP -->
    <div id="rmm-portal-app" class="rmm-portal-layout">
        
        <!-- GLASSMORPHIC TOPBAR -->
        <header class="rmm-portal-header">
            <div class="rmm-header-left">
                <div class="rmm-brand">
                    <span class="rmm-brand-badge">PRO</span>
                    <h3><?php echo esc_html(get_bloginfo('name')); ?></h3>
                </div>

                <?php if (!empty($branches)) : ?>
                    <div class="rmm-branch-select-wrap">
                        <span class="branch-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M11.9999 14.1695C9.86988 14.1695 8.12988 12.4395 8.12988 10.2995C8.12988 8.15945 9.86988 6.43945 11.9999 6.43945C14.1299 6.43945 15.8699 8.16945 15.8699 10.3095C15.8699 12.4495 14.1299 14.1695 11.9999 14.1695ZM11.9999 7.93945C10.6999 7.93945 9.62988 8.99945 9.62988 10.3095C9.62988 11.6195 10.6899 12.6795 11.9999 12.6795C13.3099 12.6795 14.3699 11.6195 14.3699 10.3095C14.3699 8.99945 13.2999 7.93945 11.9999 7.93945Z"/> <path d="M11.9999 22.76C10.5199 22.76 9.02993 22.2 7.86993 21.09C4.91993 18.25 1.65993 13.72 2.88993 8.33C3.99993 3.44 8.26993 1.25 11.9999 1.25C11.9999 1.25 11.9999 1.25 12.0099 1.25C15.7399 1.25 20.0099 3.44 21.1199 8.34C22.3399 13.73 19.0799 18.25 16.1299 21.09C14.9699 22.2 13.4799 22.76 11.9999 22.76ZM11.9999 2.75C9.08993 2.75 5.34993 4.3 4.35993 8.66C3.27993 13.37 6.23993 17.43 8.91993 20C10.6499 21.67 13.3599 21.67 15.0899 20C17.7599 17.43 20.7199 13.37 19.6599 8.66C18.6599 4.3 14.9099 2.75 11.9999 2.75Z"/> </svg></span>
                        <span class="branch-label"><?php _e('شعبه:', 'fondogh-online-menu'); ?></span>
                        <select id="rmm-portal-branch-select">
                            <?php foreach ($branches as $branch) : ?>
                                <option value="<?php echo esc_attr($branch->term_id); ?>" <?php selected($branch->term_id, $active_branch_id); ?>>
                                    <?php echo esc_html($branch->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <div class="rmm-header-right">
                <!-- Live Pulsing Clock -->
                <div class="rmm-clock-pill">
                    <span class="rmm-live-dot"></span>
                    <span id="rmm-live-clock">--:--:--</span>
                </div>

                <!-- Theme Toggle Button (Light/Dark Glassmorphism) -->
                <button id="rmm-theme-toggle-btn" class="rmm-icon-btn rmm-theme-btn" title="<?php _e('تغییر تم (روشن / تیره)', 'fondogh-online-menu'); ?>" aria-label="Toggle Theme">
                    <svg class="theme-icon sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <svg class="theme-icon moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                </button>

                <!-- Audio Alert Toggle Button -->
                <button id="rmm-audio-toggle-btn" class="rmm-icon-btn active" title="<?php _e('صدای آلارم سفارشات جدید', 'fondogh-online-menu'); ?>" aria-label="Audio alert">
                    🔔
                </button>

                <!-- Settings Quick Button (Direct Access for Mobile & Tablet) -->
                <button id="rmm-quick-settings-btn" class="rmm-icon-btn rmm-btn-settings-quick" title="<?php _e('تنظیمات و شیفت کاری شعبه', 'fondogh-online-menu'); ?>" aria-label="Settings">
                    ⚙️
                </button>

                <!-- User profile chip -->
                <div class="rmm-user-chip">
                    <span class="rmm-user-avatar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></span>
                    <span class="rmm-user-name"><?php echo esc_html($user->display_name); ?></span>
                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg('rmm_action', 'logout', home_url('/' . $slug . '/')), 'rmm_portal_logout')); ?>" class="rmm-btn-logout" title="<?php _e('خروج از حساب', 'fondogh-online-menu'); ?>">
                        🚪
                    </a>
                </div>
            </div>
        </header>

        <!-- NAVIGATION TABS -->
        <nav class="rmm-portal-nav" id="rmm-portal-nav">
            <!-- Tab 1: Orders -->
            <button class="rmm-tab-btn active" data-tab="orders">
                <span class="tab-icon-wrap">
                    <svg class="rmm-nav-svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M12 10.2796C10.98 10.2796 9.96 10.0996 8.99 9.74956C8.6 9.60956 8.4 9.17956 8.54 8.78956C8.69 8.39956 9.12 8.19956 9.51 8.33956C11.12 8.91956 12.89 8.91956 14.5 8.33956C14.89 8.19956 15.32 8.39956 15.46 8.78956C15.6 9.17956 15.4 9.60956 15.01 9.74956C14.04 10.0996 13.02 10.2796 12 10.2796Z"/> <path d="M19.0701 22.75C18.5601 22.75 18.0001 22.6 17.4601 22.29L12.5801 19.58C12.2901 19.42 11.7201 19.42 11.4301 19.58L6.55007 22.29C5.56007 22.84 4.55007 22.9 3.78007 22.44C3.01007 21.99 2.57007 21.08 2.57007 19.95V5.86C2.57007 3.32 4.64007 1.25 7.18007 1.25H16.8301C19.3701 1.25 21.4401 3.32 21.4401 5.86V19.95C21.4401 21.08 21.0001 21.99 20.2301 22.44C19.8801 22.65 19.4801 22.75 19.0701 22.75ZM12.0001 17.96C12.4701 17.96 12.9301 18.06 13.3001 18.27L18.1801 20.98C18.6901 21.27 19.1601 21.33 19.4601 21.15C19.7601 20.97 19.9301 20.54 19.9301 19.95V5.86C19.9301 4.15 18.5301 2.75 16.8201 2.75H7.18007C5.47007 2.75 4.07007 4.15 4.07007 5.86V19.95C4.07007 20.54 4.24007 20.98 4.54007 21.15C4.84007 21.32 5.30007 21.27 5.82007 20.98L10.7001 18.27C11.0701 18.06 11.5301 17.96 12.0001 17.96Z"/> <path d="M19.0701 22.75C18.5601 22.75 18.0001 22.6 17.4601 22.29L12.5801 19.58C12.2901 19.42 11.7201 19.42 11.4301 19.58L6.55007 22.29C5.56007 22.84 4.55007 22.9 3.78007 22.44C3.01007 21.99 2.57007 21.08 2.57007 19.95V5.86C2.57007 3.32 4.64007 1.25 7.18007 1.25H16.8301C19.3701 1.25 21.4401 3.32 21.4401 5.86V19.95C21.4401 21.08 21.0001 21.99 20.2301 22.44C19.8801 22.65 19.4801 22.75 19.0701 22.75ZM12.0001 17.96C12.4701 17.96 12.9301 18.06 13.3001 18.27L18.1801 20.98C18.6901 21.27 19.1601 21.33 19.4601 21.15C19.7601 20.97 19.9301 20.54 19.9301 19.95V5.86C19.9301 4.15 18.5301 2.75 16.8201 2.75H7.18007C5.47007 2.75 4.07007 4.15 4.07007 5.86V19.95C4.07007 20.54 4.24007 20.98 4.54007 21.15C4.84007 21.32 5.30007 21.27 5.82007 20.98L10.7001 18.27C11.0701 18.06 11.5301 17.96 12.0001 17.96Z"/> </svg>
                    <span id="rmm-pending-badge" class="rmm-counter-pill" style="display:none;">0</span>
                </span>
                <span class="tab-label-text"><?php _e('سفارشات', 'fondogh-online-menu'); ?></span>
            </button>

            <!-- Tab 2: Menu -->
            <button class="rmm-tab-btn" data-tab="menu">
                <span class="tab-icon-wrap">
                    <svg class="rmm-nav-svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M16.3799 22.7493H3.23993C2.28993 22.7493 1.40993 22.3093 0.839932 21.5393C0.259932 20.7593 0.0899403 19.7792 0.37994 18.8492L4.58993 5.31921C4.96993 4.05921 6.12995 3.19922 7.44995 3.19922H19.7499C20.9599 3.19922 22.0499 3.91931 22.5099 5.03931C22.7599 5.61931 22.8099 6.27932 22.6599 6.92932L19.2899 20.4592C18.9699 21.8092 17.7699 22.7493 16.3799 22.7493ZM7.45993 4.70923C6.80993 4.70923 6.21993 5.13929 6.02993 5.76929L1.81994 19.2993C1.67994 19.7693 1.75993 20.2593 2.05993 20.6593C2.33993 21.0393 2.77994 21.2593 3.24994 21.2593H16.3899C17.08 21.2593 17.6799 20.7893 17.8399 20.1193L21.2099 6.57922C21.2899 6.24922 21.2699 5.91927 21.1399 5.62927C20.9 5.05927 20.3699 4.69922 19.7599 4.69922H7.45993V4.70923Z"/> <path d="M20.78 22.7509H16C15.59 22.7509 15.25 22.4109 15.25 22.0009C15.25 21.5909 15.59 21.2509 16 21.2509H20.78C21.19 21.2509 21.57 21.081 21.85 20.781C22.13 20.481 22.27 20.081 22.24 19.671L21.25 6.05099C21.22 5.64099 21.53 5.28094 21.94 5.25094C22.35 5.23094 22.71 5.53089 22.74 5.94089L23.73 19.561C23.79 20.381 23.5 21.201 22.94 21.801C22.39 22.411 21.6 22.7509 20.78 22.7509Z"/> <path d="M9.67977 7.13069C9.61977 7.13069 9.55977 7.12067 9.49977 7.11067C9.09977 7.01067 8.84979 6.61064 8.94979 6.20064L9.98976 1.88069C10.0898 1.48069 10.4898 1.23064 10.8998 1.33064C11.2998 1.43064 11.5498 1.83068 11.4498 2.24068L10.4098 6.56074C10.3298 6.90074 10.0198 7.13069 9.67977 7.13069Z"/> <path d="M16.3799 7.13974C16.3299 7.13974 16.2699 7.13972 16.2199 7.11972C15.8199 7.02972 15.56 6.62971 15.64 6.22971L16.5799 1.88974C16.6699 1.47974 17.0699 1.22979 17.4699 1.30979C17.8699 1.38979 18.1299 1.79968 18.0499 2.19968L17.1099 6.53977C17.0399 6.89977 16.7299 7.13974 16.3799 7.13974Z"/> <path d="M15.7002 12.75H7.7002C7.2902 12.75 6.9502 12.41 6.9502 12C6.9502 11.59 7.2902 11.25 7.7002 11.25H15.7002C16.1102 11.25 16.4502 11.59 16.4502 12C16.4502 12.41 16.1102 12.75 15.7002 12.75Z"/> <path d="M14.7002 16.75H6.7002C6.2902 16.75 5.9502 16.41 5.9502 16C5.9502 15.59 6.2902 15.25 6.7002 15.25H14.7002C15.1102 15.25 15.4502 15.59 15.4502 16C15.4502 16.41 15.1102 16.75 14.7002 16.75Z"/> </svg>
                </span>
                <span class="tab-label-text"><?php _e('منو', 'fondogh-online-menu'); ?></span>
            </button>

            <!-- Center Floating Action Button (+ Add Food) -->
            <div class="rmm-nav-fab-notch">
                <button type="button" class="rmm-nav-fab-btn" id="rmm-mobile-fab-add-food" title="<?php esc_attr_e('افزودن غذای جدید', 'fondogh-online-menu'); ?>">
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="#ffffff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            </div>

            <!-- Tab 3: QR Tables -->
            <button class="rmm-tab-btn" data-tab="qrcode">
                <span class="tab-icon-wrap">
                    <svg class="rmm-nav-svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M8.27 22.75H4.23C2.22 22.75 1.25 21.82 1.25 19.9V4.1C1.25 2.18 2.23 1.25 4.23 1.25H8.27C10.28 1.25 11.25 2.18 11.25 4.1V19.9C11.25 21.82 10.27 22.75 8.27 22.75ZM4.23 2.75C2.96 2.75 2.75 3.09 2.75 4.1V19.9C2.75 20.91 2.96 21.25 4.23 21.25H8.27C9.54 21.25 9.75 20.91 9.75 19.9V4.1C9.75 3.09 9.54 2.75 8.27 2.75H4.23Z"/> <path d="M19.77 13.75H15.73C13.72 13.75 12.75 12.82 12.75 10.9V4.1C12.75 2.18 13.73 1.25 15.73 1.25H19.77C21.78 1.25 22.75 2.18 22.75 4.1V10.9C22.75 12.82 21.77 13.75 19.77 13.75ZM15.73 2.75C14.46 2.75 14.25 3.09 14.25 4.1V10.9C14.25 11.91 14.46 12.25 15.73 12.25H19.77C21.04 12.25 21.25 11.91 21.25 10.9V4.1C21.25 3.09 21.04 2.75 19.77 2.75H15.73Z"/> <path d="M19.77 22.75H15.73C13.72 22.75 12.75 21.82 12.75 19.9V18.1C12.75 16.18 13.73 15.25 15.73 15.25H19.77C21.78 15.25 22.75 16.18 22.75 18.1V19.9C22.75 21.82 21.77 22.75 19.77 22.75ZM15.73 16.75C14.46 16.75 14.25 17.09 14.25 18.1V19.9C14.25 20.91 14.46 21.25 15.73 21.25H19.77C21.04 21.25 21.25 20.91 21.25 19.9V18.1C21.25 17.09 21.04 16.75 19.77 16.75H15.73Z"/> </svg>
                </span>
                <span class="tab-label-text"><?php _e('میزها', 'fondogh-online-menu'); ?></span>
            </button>

            <!-- Tab 4: Branch Settings -->
            <button class="rmm-tab-btn" data-tab="settings">
                <span class="tab-icon-wrap">
                    <svg class="rmm-nav-svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M11.9999 14.1695C9.86988 14.1695 8.12988 12.4395 8.12988 10.2995C8.12988 8.15945 9.86988 6.43945 11.9999 6.43945C14.1299 6.43945 15.8699 8.16945 15.8699 10.3095C15.8699 12.4495 14.1299 14.1695 11.9999 14.1695ZM11.9999 7.93945C10.6999 7.93945 9.62988 8.99945 9.62988 10.3095C9.62988 11.6195 10.6899 12.6795 11.9999 12.6795C13.3099 12.6795 14.3699 11.6195 14.3699 10.3095C14.3699 8.99945 13.2999 7.93945 11.9999 7.93945Z"/> <path d="M11.9999 22.76C10.5199 22.76 9.02993 22.2 7.86993 21.09C4.91993 18.25 1.65993 13.72 2.88993 8.33C3.99993 3.44 8.26993 1.25 11.9999 1.25C11.9999 1.25 11.9999 1.25 12.0099 1.25C15.7399 1.25 20.0099 3.44 21.1199 8.34C22.3399 13.73 19.0799 18.25 16.1299 21.09C14.9699 22.2 13.4799 22.76 11.9999 22.76ZM11.9999 2.75C9.08993 2.75 5.34993 4.3 4.35993 8.66C3.27993 13.37 6.23993 17.43 8.91993 20C10.6499 21.67 13.3599 21.67 15.0899 20C17.7599 17.43 20.7199 13.37 19.6599 8.66C18.6599 4.3 14.9099 2.75 11.9999 2.75Z"/> </svg>
                </span>
                <span class="tab-label-text"><?php _e('شعبه', 'fondogh-online-menu'); ?></span>
            </button>
        </nav>

        <!-- MAIN CONTENT AREA -->
        <main class="rmm-portal-main">

            <!-- TAB 1: LIVE ORDERS -->
            <section id="tab-orders" class="rmm-tab-content active">
                <div class="rmm-section-header">
                    <div>
                        <h2><?php _e('سفارشات زنده آشپزخانه (Kitchen Display System)', 'fondogh-online-menu'); ?></h2>
                        <p><?php _e('سفارشات دریافتی آنلاین و سر میز با آلارم صوتی لحظه‌ای', 'fondogh-online-menu'); ?></p>
                    </div>
                    <div class="rmm-filter-bar">
                        <button class="rmm-order-filter active" data-filter="all"><?php _e('همه', 'fondogh-online-menu'); ?></button>
                        <button class="rmm-order-filter" data-filter="processing"><?php _e('در حال آماده‌سازی', 'fondogh-online-menu'); ?></button>
                        <button class="rmm-order-filter" data-filter="completed"><?php _e('تحویل شده', 'fondogh-online-menu'); ?></button>
                    </div>
                </div>

                <div id="rmm-orders-grid" class="rmm-orders-grid">
                    <div class="rmm-loading-state"><?php _e('در حال دریافت آخرین سفارشات...', 'fondogh-online-menu'); ?></div>
                </div>
            </section>

            <!-- TAB 2: MENU & INVENTORY (ENHANCED USER-FRIENDLY MANAGEMENT) -->
            <section id="tab-menu" class="rmm-tab-content">
                
                <!-- KPI STATS CARDS -->
                <div class="rmm-stats-grid">
                    <div class="rmm-stat-card">
                        <div class="rmm-stat-icon stat-total"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M16.3799 22.7493H3.23993C2.28993 22.7493 1.40993 22.3093 0.839932 21.5393C0.259932 20.7593 0.0899403 19.7792 0.37994 18.8492L4.58993 5.31921C4.96993 4.05921 6.12995 3.19922 7.44995 3.19922H19.7499C20.9599 3.19922 22.0499 3.91931 22.5099 5.03931C22.7599 5.61931 22.8099 6.27932 22.6599 6.92932L19.2899 20.4592C18.9699 21.8092 17.7699 22.7493 16.3799 22.7493ZM7.45993 4.70923C6.80993 4.70923 6.21993 5.13929 6.02993 5.76929L1.81994 19.2993C1.67994 19.7693 1.75993 20.2593 2.05993 20.6593C2.33993 21.0393 2.77994 21.2593 3.24994 21.2593H16.3899C17.08 21.2593 17.6799 20.7893 17.8399 20.1193L21.2099 6.57922C21.2899 6.24922 21.2699 5.91927 21.1399 5.62927C20.9 5.05927 20.3699 4.69922 19.7599 4.69922H7.45993V4.70923Z"/> <path d="M20.78 22.7509H16C15.59 22.7509 15.25 22.4109 15.25 22.0009C15.25 21.5909 15.59 21.2509 16 21.2509H20.78C21.19 21.2509 21.57 21.081 21.85 20.781C22.13 20.481 22.27 20.081 22.24 19.671L21.25 6.05099C21.22 5.64099 21.53 5.28094 21.94 5.25094C22.35 5.23094 22.71 5.53089 22.74 5.94089L23.73 19.561C23.79 20.381 23.5 21.201 22.94 21.801C22.39 22.411 21.6 22.7509 20.78 22.7509Z"/> <path d="M9.67977 7.13069C9.61977 7.13069 9.55977 7.12067 9.49977 7.11067C9.09977 7.01067 8.84979 6.61064 8.94979 6.20064L9.98976 1.88069C10.0898 1.48069 10.4898 1.23064 10.8998 1.33064C11.2998 1.43064 11.5498 1.83068 11.4498 2.24068L10.4098 6.56074C10.3298 6.90074 10.0198 7.13069 9.67977 7.13069Z"/> <path d="M16.3799 7.13974C16.3299 7.13974 16.2699 7.13972 16.2199 7.11972C15.8199 7.02972 15.56 6.62971 15.64 6.22971L16.5799 1.88974C16.6699 1.47974 17.0699 1.22979 17.4699 1.30979C17.8699 1.38979 18.1299 1.79968 18.0499 2.19968L17.1099 6.53977C17.0399 6.89977 16.7299 7.13974 16.3799 7.13974Z"/> <path d="M15.7002 12.75H7.7002C7.2902 12.75 6.9502 12.41 6.9502 12C6.9502 11.59 7.2902 11.25 7.7002 11.25H15.7002C16.1102 11.25 16.4502 11.59 16.4502 12C16.4502 12.41 16.1102 12.75 15.7002 12.75Z"/> <path d="M14.7002 16.75H6.7002C6.2902 16.75 5.9502 16.41 5.9502 16C5.9502 15.59 6.2902 15.25 6.7002 15.25H14.7002C15.1102 15.25 15.4502 15.59 15.4502 16C15.4502 16.41 15.1102 16.75 14.7002 16.75Z"/> </svg></div>
                        <div class="rmm-stat-details">
                            <span class="rmm-stat-label"><?php _e('کل غذاهای منو', 'fondogh-online-menu'); ?></span>
                            <strong id="rmm-stat-total-foods" class="rmm-stat-value">0</strong>
                        </div>
                    </div>
                    <div class="rmm-stat-card">
                        <div class="rmm-stat-icon stat-instock"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                        <div class="rmm-stat-details">
                            <span class="rmm-stat-label"><?php _e('موجود در این شعبه', 'fondogh-online-menu'); ?></span>
                            <strong id="rmm-stat-instock-foods" class="rmm-stat-value">0</strong>
                        </div>
                    </div>
                    <div class="rmm-stat-card">
                        <div class="rmm-stat-icon stat-out"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg></div>
                        <div class="rmm-stat-details">
                            <span class="rmm-stat-label"><?php _e('ناموجود شده', 'fondogh-online-menu'); ?></span>
                            <strong id="rmm-stat-outofstock-foods" class="rmm-stat-value">0</strong>
                        </div>
                    </div>
                </div>

                <!-- SECTION HEADER & SEARCH / ADD BUTTON -->
                <div class="rmm-section-header rmm-menu-header">
                    <div>
                        <h2><?php _e('مدیریت سریع منو و موجودی غذاها', 'fondogh-online-menu'); ?></h2>
                        <p><?php _e('افزودن غذای جدید، تغییر وضعیت موجودی، ویرایش قیمت و ترجمه‌ها بدون نیاز به ورود به پیشخوان وردپرس', 'fondogh-online-menu'); ?></p>
                    </div>
                    <div class="rmm-menu-actions-bar">
                        <div class="rmm-search-box-wrap">
                            <span class="rmm-search-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></span>
                            <input type="text" id="rmm-menu-search-input" placeholder="<?php _e('جستجوی نام یا کد غذا...', 'fondogh-online-menu'); ?>">
                            <button id="rmm-clear-search-btn" class="rmm-clear-search-btn" style="display:none;" title="<?php _e('پاک کردن جستجو', 'fondogh-online-menu'); ?>">&times;</button>
                        </div>
                        <button id="rmm-manage-categories-btn" class="rmm-btn-secondary rmm-btn-manage-cats" type="button">
                            <span class="btn-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M7 10.75H5C2.58 10.75 1.25 9.42 1.25 7V5C1.25 2.58 2.58 1.25 5 1.25H7C9.42 1.25 10.75 2.58 10.75 5V7C10.75 9.42 9.42 10.75 7 10.75ZM5 2.75C3.42 2.75 2.75 3.42 2.75 5V7C2.75 8.58 3.42 9.25 5 9.25H7C8.58 9.25 9.25 8.58 9.25 7V5C9.25 3.42 8.58 2.75 7 2.75H5Z"/> <path d="M19 10.75H17C14.58 10.75 13.25 9.42 13.25 7V5C13.25 2.58 14.58 1.25 17 1.25H19C21.42 1.25 22.75 2.58 22.75 5V7C22.75 9.42 21.42 10.75 19 10.75ZM17 2.75C15.42 2.75 14.75 3.42 14.75 5V7C14.75 8.58 15.42 9.25 17 9.25H19C20.58 9.25 21.25 8.58 21.25 7V5C21.25 3.42 20.58 2.75 19 2.75H17Z"/> <path d="M19 22.75H17C14.58 22.75 13.25 21.42 13.25 19V17C13.25 14.58 14.58 13.25 17 13.25H19C21.42 13.25 22.75 14.58 22.75 17V19C22.75 21.42 21.42 22.75 19 22.75ZM17 14.75C15.42 14.75 14.75 15.42 14.75 17V19C14.75 20.58 15.42 21.25 17 21.25H19C20.58 21.25 21.25 20.58 21.25 19V17C21.25 15.42 20.58 14.75 19 14.75H17Z"/> <path d="M7 22.75H5C2.58 22.75 1.25 21.42 1.25 19V17C1.25 14.58 2.58 13.25 5 13.25H7C9.42 13.25 10.75 14.58 10.75 17V19C10.75 21.42 9.42 22.75 7 22.75ZM5 14.75C3.42 14.75 2.75 15.42 2.75 17V19C2.75 20.58 3.42 21.25 5 21.25H7C8.58 21.25 9.25 20.58 9.25 19V17C9.25 15.42 8.58 14.75 7 14.75H5Z"/> </svg></span>
                            <span><?php _e('مدیریت دسته‌بندی‌ها', 'fondogh-online-menu'); ?></span>
                        </button>
                        <button id="rmm-add-food-btn" class="rmm-btn-primary rmm-btn-add-food" type="button">
                            <span class="btn-icon">+</span>
                            <span><?php _e('افزودن غذای جدید', 'fondogh-online-menu'); ?></span>
                        </button>
                    </div>
                </div>

                <!-- CATEGORY FILTER CHIPS -->
                <div class="rmm-category-filter-wrap">
                    <div class="rmm-cat-chips-scroll" id="rmm-category-chips">
                        <button class="rmm-cat-chip active" data-cat="all">
                            <span><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M16.3799 22.7493H3.23993C2.28993 22.7493 1.40993 22.3093 0.839932 21.5393C0.259932 20.7593 0.0899403 19.7792 0.37994 18.8492L4.58993 5.31921C4.96993 4.05921 6.12995 3.19922 7.44995 3.19922H19.7499C20.9599 3.19922 22.0499 3.91931 22.5099 5.03931C22.7599 5.61931 22.8099 6.27932 22.6599 6.92932L19.2899 20.4592C18.9699 21.8092 17.7699 22.7493 16.3799 22.7493ZM7.45993 4.70923C6.80993 4.70923 6.21993 5.13929 6.02993 5.76929L1.81994 19.2993C1.67994 19.7693 1.75993 20.2593 2.05993 20.6593C2.33993 21.0393 2.77994 21.2593 3.24994 21.2593H16.3899C17.08 21.2593 17.6799 20.7893 17.8399 20.1193L21.2099 6.57922C21.2899 6.24922 21.2699 5.91927 21.1399 5.62927C20.9 5.05927 20.3699 4.69922 19.7599 4.69922H7.45993V4.70923Z"/> <path d="M20.78 22.7509H16C15.59 22.7509 15.25 22.4109 15.25 22.0009C15.25 21.5909 15.59 21.2509 16 21.2509H20.78C21.19 21.2509 21.57 21.081 21.85 20.781C22.13 20.481 22.27 20.081 22.24 19.671L21.25 6.05099C21.22 5.64099 21.53 5.28094 21.94 5.25094C22.35 5.23094 22.71 5.53089 22.74 5.94089L23.73 19.561C23.79 20.381 23.5 21.201 22.94 21.801C22.39 22.411 21.6 22.7509 20.78 22.7509Z"/> <path d="M9.67977 7.13069C9.61977 7.13069 9.55977 7.12067 9.49977 7.11067C9.09977 7.01067 8.84979 6.61064 8.94979 6.20064L9.98976 1.88069C10.0898 1.48069 10.4898 1.23064 10.8998 1.33064C11.2998 1.43064 11.5498 1.83068 11.4498 2.24068L10.4098 6.56074C10.3298 6.90074 10.0198 7.13069 9.67977 7.13069Z"/> <path d="M16.3799 7.13974C16.3299 7.13974 16.2699 7.13972 16.2199 7.11972C15.8199 7.02972 15.56 6.62971 15.64 6.22971L16.5799 1.88974C16.6699 1.47974 17.0699 1.22979 17.4699 1.30979C17.8699 1.38979 18.1299 1.79968 18.0499 2.19968L17.1099 6.53977C17.0399 6.89977 16.7299 7.13974 16.3799 7.13974Z"/> <path d="M15.7002 12.75H7.7002C7.2902 12.75 6.9502 12.41 6.9502 12C6.9502 11.59 7.2902 11.25 7.7002 11.25H15.7002C16.1102 11.25 16.4502 11.59 16.4502 12C16.4502 12.41 16.1102 12.75 15.7002 12.75Z"/> <path d="M14.7002 16.75H6.7002C6.2902 16.75 5.9502 16.41 5.9502 16C5.9502 15.59 6.2902 15.25 6.7002 15.25H14.7002C15.1102 15.25 15.4502 15.59 15.4502 16C15.4502 16.41 15.1102 16.75 14.7002 16.75Z"/> </svg></span>
                            <span><?php _e('همه دسته‌ها', 'fondogh-online-menu'); ?></span>
                            <span class="rmm-chip-count" id="rmm-all-count">0</span>
                        </button>
                    </div>
                </div>

                <!-- MENU TABLE WRAP -->
                <div class="rmm-menu-table-wrap">
                    <table class="rmm-table" id="rmm-products-table">
                        <thead>
                            <tr>
                                <th><?php _e('تصویر', 'fondogh-online-menu'); ?></th>
                                <th><?php _e('نام غذا', 'fondogh-online-menu'); ?></th>
                                <th><?php _e('دسته‌بندی', 'fondogh-online-menu'); ?></th>
                                <th><?php _e('قیمت عادی (تومان)', 'fondogh-online-menu'); ?></th>
                                <th><?php _e('قیمت ویژه (تومان)', 'fondogh-online-menu'); ?></th>
                                <th><?php _e('وضعیت موجودی در این شعبه', 'fondogh-online-menu'); ?></th>
                                <th><?php _e('عملیات', 'fondogh-online-menu'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="rmm-products-body">
                            <tr><td colspan="7" class="rmm-center"><?php _e('در حال بارگذاری غذاها...', 'fondogh-online-menu'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- TAB 3: QR CODE GENERATOR -->
            <section id="tab-qrcode" class="rmm-tab-content">
                <div class="rmm-section-header">
                    <div>
                        <h2><?php _e('تولید و چاپ QR Code اختصاصی میزها', 'fondogh-online-menu'); ?></h2>
                        <p><?php _e('با اسکن این کیوآر کد توسط مشتری، شماره میز خودکار در سفارش و فیش آشپزخانه درج می‌شود.', 'fondogh-online-menu'); ?></p>
                    </div>
                </div>

                <div class="rmm-qr-builder-wrap">
                    <div class="rmm-qr-controls">
                        <label><?php _e('شماره میز را وارد کنید:', 'fondogh-online-menu'); ?></label>
                        <div class="rmm-input-row">
                            <input type="number" id="rmm-qr-table-num" value="1" min="1" max="200">
                            <button id="rmm-generate-qr-btn" class="rmm-btn-primary"><?php _e('تولید QR کد', 'fondogh-online-menu'); ?></button>
                            <button id="rmm-print-qr-btn" class="rmm-btn-secondary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> <?php _e('چاپ کارت میز', 'fondogh-online-menu'); ?></button>
                        </div>
                    </div>

                    <div id="rmm-qr-preview-area" class="rmm-qr-preview-area">
                        <!-- QR Card dynamically placed here -->
                    </div>
                </div>
            </section>

            <!-- TAB 4: SETTINGS, SHIFT & BRANCHES -->
            <section id="tab-settings" class="rmm-tab-content">
                <div class="rmm-section-header">
                    <div>
                        <h2><?php _e('شیفت کاری و مدیریت شعب رستوران', 'fondogh-online-menu'); ?></h2>
                        <p><?php _e('باز یا بستن سفارش‌گیری، ویرایش اطلاعات تماس و آدرس شعبه‌ها و معرفی شعبه جدید', 'fondogh-online-menu'); ?></p>
                    </div>
                </div>

                <div class="rmm-shift-card">
                    <div class="rmm-shift-row">
                        <div>
                            <h3><?php _e('وضعیت سفارش‌گیری شعبه جاری:', 'fondogh-online-menu'); ?></h3>
                            <p id="rmm-branch-status-desc"><?php _e('اگر سفارش‌گیری را ببندید، مشتریان امکان افزودن به سبد خرید را نخواهند داشت.', 'fondogh-online-menu'); ?></p>
                        </div>
                        <div>
                            <button id="rmm-toggle-branch-btn" class="rmm-btn-status open" type="button">
                                🟢 <?php _e('شعبه باز است (کلیک برای بستن)', 'fondogh-online-menu'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Branch Information & Edit Card -->
                <div class="rmm-branch-management-card">
                    <div class="rmm-branch-card-header">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="rmm-branch-icon-svg" style="color:var(--rmm-primary); display:flex; align-items:center;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M11.9999 14.1695C9.86988 14.1695 8.12988 12.4395 8.12988 10.2995C8.12988 8.15945 9.86988 6.43945 11.9999 6.43945C14.1299 6.43945 15.8699 8.16945 15.8699 10.3095C15.8699 12.4495 14.1299 14.1695 11.9999 14.1695ZM11.9999 7.93945C10.6999 7.93945 9.62988 8.99945 9.62988 10.3095C9.62988 11.6195 10.6899 12.6795 11.9999 12.6795C13.3099 12.6795 14.3699 11.6195 14.3699 10.3095C14.3699 8.99945 13.2999 7.93945 11.9999 7.93945Z"/> <path d="M11.9999 22.76C10.5199 22.76 9.02993 22.2 7.86993 21.09C4.91993 18.25 1.65993 13.72 2.88993 8.33C3.99993 3.44 8.26993 1.25 11.9999 1.25C11.9999 1.25 11.9999 1.25 12.0099 1.25C15.7399 1.25 20.0099 3.44 21.1199 8.34C22.3399 13.73 19.0799 18.25 16.1299 21.09C14.9699 22.2 13.4799 22.76 11.9999 22.76ZM11.9999 2.75C9.08993 2.75 5.34993 4.3 4.35993 8.66C3.27993 13.37 6.23993 17.43 8.91993 20C10.6499 21.67 13.3599 21.67 15.0899 20C17.7599 17.43 20.7199 13.37 19.6599 8.66C18.6599 4.3 14.9099 2.75 11.9999 2.75Z"/> </svg></span>
                            <div>
                                <h3 style="margin:0; font-size:16px; font-weight:800; color:var(--rmm-text);"><?php _e('اطلاعات شعبه فعال', 'fondogh-online-menu'); ?></h3>
                                <p style="margin:3px 0 0; font-size:13px; color:var(--rmm-text-muted);" id="rmm-active-branch-desc"><?php _e('مشاهده و ویرایش مشخصات شعبه انتخاب شده', 'fondogh-online-menu'); ?></p>
                            </div>
                        </div>
                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <button type="button" class="rmm-btn-secondary" id="rmm-edit-current-branch-btn">
                                <span><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M15 22.75H9C3.57 22.75 1.25 20.43 1.25 15V9C1.25 3.57 3.57 1.25 9 1.25H11C11.41 1.25 11.75 1.59 11.75 2C11.75 2.41 11.41 2.75 11 2.75H9C4.39 2.75 2.75 4.39 2.75 9V15C2.75 19.61 4.39 21.25 9 21.25H15C19.61 21.25 21.25 19.61 21.25 15V13C21.25 12.59 21.59 12.25 22 12.25C22.41 12.25 22.75 12.59 22.75 13V15C22.75 20.43 20.43 22.75 15 22.75Z"/> <path d="M8.50008 17.6905C7.89008 17.6905 7.33008 17.4705 6.92008 17.0705C6.43008 16.5805 6.22008 15.8705 6.33008 15.1205L6.76008 12.1105C6.84008 11.5305 7.22008 10.7805 7.63008 10.3705L15.5101 2.49055C17.5001 0.500547 19.5201 0.500547 21.5101 2.49055C22.6001 3.58055 23.0901 4.69055 22.9901 5.80055C22.9001 6.70055 22.4201 7.58055 21.5101 8.48055L13.6301 16.3605C13.2201 16.7705 12.4701 17.1505 11.8901 17.2305L8.88008 17.6605C8.75008 17.6905 8.62008 17.6905 8.50008 17.6905ZM16.5701 3.55055L8.69008 11.4305C8.50008 11.6205 8.28008 12.0605 8.24008 12.3205L7.81008 15.3305C7.77008 15.6205 7.83008 15.8605 7.98008 16.0105C8.13008 16.1605 8.37008 16.2205 8.66008 16.1805L11.6701 15.7505C11.9301 15.7105 12.3801 15.4905 12.5601 15.3005L20.4401 7.42055C21.0901 6.77055 21.4301 6.19055 21.4801 5.65055C21.5401 5.00055 21.2001 4.31055 20.4401 3.54055C18.8401 1.94055 17.7401 2.39055 16.5701 3.55055Z"/> <path d="M19.8501 9.83027C19.7801 9.83027 19.7101 9.82027 19.6501 9.80027C17.0201 9.06027 14.9301 6.97027 14.1901 4.34027C14.0801 3.94027 14.3101 3.53027 14.7101 3.41027C15.1101 3.30027 15.5201 3.53027 15.6301 3.93027C16.2301 6.06027 17.9201 7.75027 20.0501 8.35027C20.4501 8.46027 20.6801 8.88027 20.5701 9.28027C20.4801 9.62027 20.1801 9.83027 19.8501 9.83027Z"/> </svg></span>
                                <span><?php _e('ویرایش مشخصات این شعبه', 'fondogh-online-menu'); ?></span>
                            </button>
                            <button type="button" class="rmm-btn-primary" id="rmm-create-new-branch-btn">
                                <span>+</span>
                                <span><?php _e('معرفی و ثبت شعبه جدید', 'fondogh-online-menu'); ?></span>
                            </button>
                        </div>
                    </div>

                    <div class="rmm-branch-details-grid" id="rmm-branch-details-view" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-top:20px;">
                        <div class="rmm-detail-item">
                            <span class="detail-label" style="font-size:12px; color:var(--rmm-text-muted); display:block; margin-bottom:4px;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M11.9999 14.1695C9.86988 14.1695 8.12988 12.4395 8.12988 10.2995C8.12988 8.15945 9.86988 6.43945 11.9999 6.43945C14.1299 6.43945 15.8699 8.16945 15.8699 10.3095C15.8699 12.4495 14.1299 14.1695 11.9999 14.1695ZM11.9999 7.93945C10.6999 7.93945 9.62988 8.99945 9.62988 10.3095C9.62988 11.6195 10.6899 12.6795 11.9999 12.6795C13.3099 12.6795 14.3699 11.6195 14.3699 10.3095C14.3699 8.99945 13.2999 7.93945 11.9999 7.93945Z"/> <path d="M11.9999 22.76C10.5199 22.76 9.02993 22.2 7.86993 21.09C4.91993 18.25 1.65993 13.72 2.88993 8.33C3.99993 3.44 8.26993 1.25 11.9999 1.25C11.9999 1.25 11.9999 1.25 12.0099 1.25C15.7399 1.25 20.0099 3.44 21.1199 8.34C22.3399 13.73 19.0799 18.25 16.1299 21.09C14.9699 22.2 13.4799 22.76 11.9999 22.76ZM11.9999 2.75C9.08993 2.75 5.34993 4.3 4.35993 8.66C3.27993 13.37 6.23993 17.43 8.91993 20C10.6499 21.67 13.3599 21.67 15.0899 20C17.7599 17.43 20.7199 13.37 19.6599 8.66C18.6599 4.3 14.9099 2.75 11.9999 2.75Z"/> </svg> <?php _e('نام شعبه:', 'fondogh-online-menu'); ?></span>
                            <strong class="detail-value" id="rmm-view-branch-name" style="font-size:14px; color:var(--rmm-text);">-</strong>
                        </div>
                        <div class="rmm-detail-item">
                            <span class="detail-label" style="font-size:12px; color:var(--rmm-text-muted); display:block; margin-bottom:4px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> <?php _e('شماره تماس:', 'fondogh-online-menu'); ?></span>
                            <strong class="detail-value" id="rmm-view-branch-phone" style="font-size:14px; color:var(--rmm-text);">-</strong>
                        </div>
                        <div class="rmm-detail-item">
                            <span class="detail-label" style="font-size:12px; color:var(--rmm-text-muted); display:block; margin-bottom:4px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> <?php _e('ساعات کاری:', 'fondogh-online-menu'); ?></span>
                            <strong class="detail-value" id="rmm-view-branch-hours" style="font-size:14px; color:var(--rmm-text);">-</strong>
                        </div>
                        <div class="rmm-detail-item" style="grid-column: 1 / -1;">
                            <span class="detail-label" style="font-size:12px; color:var(--rmm-text-muted); display:block; margin-bottom:4px;"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M11.9999 14.1695C9.86988 14.1695 8.12988 12.4395 8.12988 10.2995C8.12988 8.15945 9.86988 6.43945 11.9999 6.43945C14.1299 6.43945 15.8699 8.16945 15.8699 10.3095C15.8699 12.4495 14.1299 14.1695 11.9999 14.1695ZM11.9999 7.93945C10.6999 7.93945 9.62988 8.99945 9.62988 10.3095C9.62988 11.6195 10.6899 12.6795 11.9999 12.6795C13.3099 12.6795 14.3699 11.6195 14.3699 10.3095C14.3699 8.99945 13.2999 7.93945 11.9999 7.93945Z"/> <path d="M11.9999 22.76C10.5199 22.76 9.02993 22.2 7.86993 21.09C4.91993 18.25 1.65993 13.72 2.88993 8.33C3.99993 3.44 8.26993 1.25 11.9999 1.25C11.9999 1.25 11.9999 1.25 12.0099 1.25C15.7399 1.25 20.0099 3.44 21.1199 8.34C22.3399 13.73 19.0799 18.25 16.1299 21.09C14.9699 22.2 13.4799 22.76 11.9999 22.76ZM11.9999 2.75C9.08993 2.75 5.34993 4.3 4.35993 8.66C3.27993 13.37 6.23993 17.43 8.91993 20C10.6499 21.67 13.3599 21.67 15.0899 20C17.7599 17.43 20.7199 13.37 19.6599 8.66C18.6599 4.3 14.9099 2.75 11.9999 2.75Z"/> </svg> <?php _e('آدرس شعبه:', 'fondogh-online-menu'); ?></span>
                            <p class="detail-value" id="rmm-view-branch-address" style="margin:0; font-size:13px; color:var(--rmm-text); line-height:1.6;">-</p>
                        </div>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- GLASSMORPHIC MODAL: ADD / EDIT FOOD ITEM -->
    <div id="rmm-food-edit-modal" class="rmm-modal-backdrop" style="display:none;">
        <div class="rmm-modal-box">
            <button class="rmm-modal-close-btn" id="rmm-close-food-modal-btn" type="button" aria-label="Close modal">&times;</button>
            <div class="rmm-modal-header">
                <div class="modal-header-icon" style="color:var(--rmm-primary);"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M16.3799 22.7493H3.23993C2.28993 22.7493 1.40993 22.3093 0.839932 21.5393C0.259932 20.7593 0.0899403 19.7792 0.37994 18.8492L4.58993 5.31921C4.96993 4.05921 6.12995 3.19922 7.44995 3.19922H19.7499C20.9599 3.19922 22.0499 3.91931 22.5099 5.03931C22.7599 5.61931 22.8099 6.27932 22.6599 6.92932L19.2899 20.4592C18.9699 21.8092 17.7699 22.7493 16.3799 22.7493ZM7.45993 4.70923C6.80993 4.70923 6.21993 5.13929 6.02993 5.76929L1.81994 19.2993C1.67994 19.7693 1.75993 20.2593 2.05993 20.6593C2.33993 21.0393 2.77994 21.2593 3.24994 21.2593H16.3899C17.08 21.2593 17.6799 20.7893 17.8399 20.1193L21.2099 6.57922C21.2899 6.24922 21.2699 5.91927 21.1399 5.62927C20.9 5.05927 20.3699 4.69922 19.7599 4.69922H7.45993V4.70923Z"/> <path d="M20.78 22.7509H16C15.59 22.7509 15.25 22.4109 15.25 22.0009C15.25 21.5909 15.59 21.2509 16 21.2509H20.78C21.19 21.2509 21.57 21.081 21.85 20.781C22.13 20.481 22.27 20.081 22.24 19.671L21.25 6.05099C21.22 5.64099 21.53 5.28094 21.94 5.25094C22.35 5.23094 22.71 5.53089 22.74 5.94089L23.73 19.561C23.79 20.381 23.5 21.201 22.94 21.801C22.39 22.411 21.6 22.7509 20.78 22.7509Z"/> <path d="M9.67977 7.13069C9.61977 7.13069 9.55977 7.12067 9.49977 7.11067C9.09977 7.01067 8.84979 6.61064 8.94979 6.20064L9.98976 1.88069C10.0898 1.48069 10.4898 1.23064 10.8998 1.33064C11.2998 1.43064 11.5498 1.83068 11.4498 2.24068L10.4098 6.56074C10.3298 6.90074 10.0198 7.13069 9.67977 7.13069Z"/> <path d="M16.3799 7.13974C16.3299 7.13974 16.2699 7.13972 16.2199 7.11972C15.8199 7.02972 15.56 6.62971 15.64 6.22971L16.5799 1.88974C16.6699 1.47974 17.0699 1.22979 17.4699 1.30979C17.8699 1.38979 18.1299 1.79968 18.0499 2.19968L17.1099 6.53977C17.0399 6.89977 16.7299 7.13974 16.3799 7.13974Z"/> <path d="M15.7002 12.75H7.7002C7.2902 12.75 6.9502 12.41 6.9502 12C6.9502 11.59 7.2902 11.25 7.7002 11.25H15.7002C16.1102 11.25 16.4502 11.59 16.4502 12C16.4502 12.41 16.1102 12.75 15.7002 12.75Z"/> <path d="M14.7002 16.75H6.7002C6.2902 16.75 5.9502 16.41 5.9502 16C5.9502 15.59 6.2902 15.25 6.7002 15.25H14.7002C15.1102 15.25 15.4502 15.59 15.4502 16C15.4502 16.41 15.1102 16.75 14.7002 16.75Z"/> </svg></div>
                <div>
                    <h3 id="rmm-food-modal-title" class="rmm-modal-title">
                        <?php _e('افزودن غذای جدید به منو', 'fondogh-online-menu'); ?>
                    </h3>
                    <p class="rmm-modal-desc"><?php _e('مشخصات، قیمت، دسته‌بندی و ترجمه غذا را مشخص کنید.', 'fondogh-online-menu'); ?></p>
                </div>
            </div>

            <form id="rmm-food-form" class="rmm-modal-form">
                <input type="hidden" id="rmm-food-form-id" value="0">

                <!-- Section 1: Basic Information -->
                <div class="rmm-form-card">
                    <h4 class="rmm-form-card-title">📝 <?php _e('مشخصات پایه غذا', 'fondogh-online-menu'); ?></h4>
                    <div class="rmm-form-group">
                        <label><?php _e('نام غذا (فارسی / زبان اصلی) *', 'fondogh-online-menu'); ?></label>
                        <input type="text" id="rmm-food-form-name" required placeholder="<?php _e('مثلاً: چلوکباب کوبیده نگین‌دار', 'fondogh-online-menu'); ?>">
                    </div>

                    <div class="rmm-grid-2">
                        <div class="rmm-form-group">
                            <label><?php _e('دسته‌بندی غذا', 'fondogh-online-menu'); ?></label>
                            <select id="rmm-food-form-category">
                                <option value="0"><?php _e('-- بدون دسته‌بندی --', 'fondogh-online-menu'); ?></option>
                            </select>
                        </div>
                        <div class="rmm-form-group">
                            <label><?php _e('نشان ویژه غذا', 'fondogh-online-menu'); ?></label>
                            <select id="rmm-food-form-badge">
                                <option value="none"><?php _e('بدون نشان', 'fondogh-online-menu'); ?></option>
                                <option value="chef">⭐ <?php _e('پیشنهاد سرآشپز', 'fondogh-online-menu'); ?></option>
                                <option value="spicy">🌶️ <?php _e('تند و اسپایسی', 'fondogh-online-menu'); ?></option>
                                <option value="vegan">🌱 <?php _e('گیاهی', 'fondogh-online-menu'); ?></option>
                                <option value="popular">🔥 <?php _e('پرفروش', 'fondogh-online-menu'); ?></option>
                                <option value="new">✨ <?php _e('غذای جدید', 'fondogh-online-menu'); ?></option>
                                <option value="custom">🎨 <?php _e('نشان دلخواه و سفارشی', 'fondogh-online-menu'); ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- بخش تنظیمات اختصاصی نشان ویژه (متن، آیکون و تصویر) -->
                    <div id="rmm-badge-extra-settings" class="rmm-badge-extra-box" style="display:none; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 12px 14px; margin-top: 10px;">
                        <span style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:8px;">
                            🏷️ <?php _e('ویرایش عنوان، آیکون یا تصویر نشان ویژه غذا:', 'fondogh-online-menu'); ?>
                        </span>
                        <div class="rmm-grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1.3fr; gap: 12px; align-items: start;">
                            <div>
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;"><?php _e('عنوان دلخواه نشان:', 'fondogh-online-menu'); ?></label>
                                <input type="text" id="rmm-food-form-badge-text" placeholder="مثلاً: ویژه روز، پرطرفدار...">
                            </div>
                            <div>
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;"><?php _e('آیکون یا ایموجی نشان:', 'fondogh-online-menu'); ?></label>
                                <input type="text" id="rmm-food-form-badge-icon" placeholder="مثلاً: 👑، 💎، 🌶️، ⭐">
                            </div>
                            <div>
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px;"><?php _e('تصویر یا لوگوی نشان:', 'fondogh-online-menu'); ?></label>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <input type="text" id="rmm-food-form-badge-image" placeholder="آدرس یا آپلود عکس" style="flex: 1; font-size: 12px;">
                                    <button type="button" id="rmm-badge-upload-btn" class="rmm-btn-secondary" style="padding: 6px 10px; font-size: 12px; white-space: nowrap;">📷 <?php _e('آپلود', 'fondogh-online-menu'); ?></button>
                                    <input type="file" id="rmm-badge-file-input" accept="image/*" style="display:none;">
                                </div>
                                <div id="rmm-badge-img-preview" style="margin-top: 6px; display: none; align-items: center; gap: 8px;">
                                    <img id="rmm-badge-img-tag" src="" style="width: 28px; height: 28px; object-fit: contain; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff;">
                                    <button type="button" id="rmm-badge-img-remove" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 13px; font-weight: bold;" title="حذف تصویر">✕</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Food Image & Gallery Album -->
                <div class="rmm-form-card">
                    <h4 class="rmm-form-card-title">📸 <?php _e('تصویر اصلی و آلبوم تصاویر غذا', 'fondogh-online-menu'); ?></h4>
                    <div class="rmm-image-upload-section">
                        <!-- Main / Featured Image -->
                        <div class="rmm-main-image-block">
                            <label class="rmm-upload-label"><?php _e('تصویر اصلی شاخص غذا:', 'fondogh-online-menu'); ?></label>
                            <input type="file" id="rmm-main-image-file" accept="image/*" style="display:none;">
                            <input type="hidden" id="rmm-food-form-image-id" value="0">
                            
                            <div class="rmm-image-drop-area" id="rmm-main-image-drop">
                                <div class="rmm-empty-upload-prompt" id="rmm-main-upload-prompt">
                                    <span class="upload-icon">📷</span>
                                    <span class="upload-text"><?php _e('برای انتخاب تصویر اصلی کلیک کنید یا عکس را بکشید اینجا', 'fondogh-online-menu'); ?></span>
                                    <span class="upload-subtext"><?php _e('فرمت‌های مجاز: JPG, PNG, WEBP (حداکثر ۵ مگابایت)', 'fondogh-online-menu'); ?></span>
                                </div>
                                <div class="rmm-main-preview-wrap" id="rmm-main-preview-wrap" style="display:none;">
                                    <img id="rmm-main-image-preview" src="" alt="پیش‌نمایش تصویر اصلی">
                                    <div class="rmm-preview-overlay-actions">
                                        <button type="button" class="rmm-btn-mini rmm-btn-change-image" id="rmm-btn-change-main-img">🔄 <?php _e('تغییر عکس', 'fondogh-online-menu'); ?></button>
                                        <button type="button" class="rmm-btn-mini rmm-btn-remove-image" id="rmm-btn-remove-main-img">🗑️ <?php _e('حذف عکس', 'fondogh-online-menu'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gallery Album Images -->
                        <div class="rmm-gallery-image-block">
                            <div class="rmm-gallery-header-row">
                                <div>
                                    <label class="rmm-upload-label"><?php _e('آلبوم تصاویر بیشتر (اسلایدر در پاپ‌آپ محصول):', 'fondogh-online-menu'); ?></label>
                                    <span class="rmm-gallery-sublabel"><?php _e('می‌توانید چند عکس با دیزاین‌های مختلف یا زاویه‌های دیگر اضافه کنید', 'fondogh-online-menu'); ?></span>
                                </div>
                                <button type="button" class="rmm-btn-mini rmm-btn-add-gallery" id="rmm-btn-add-gallery">
                                    <span>+</span> <?php _e('افزودن عکس به آلبوم', 'fondogh-online-menu'); ?>
                                </button>
                                <input type="file" id="rmm-gallery-image-file" accept="image/*" multiple style="display:none;">
                            </div>

                            <div class="rmm-gallery-grid-strip" id="rmm-gallery-grid-strip">
                                <div class="rmm-gallery-empty-hint" id="rmm-gallery-empty-hint">
                                    <?php _e('عکسی در آلبوم ثبت نشده است. این تصاویر در پاپ‌آپ به عنوان اسلایدر نمایش داده می‌شوند.', 'fondogh-online-menu'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Pricing -->
                <div class="rmm-form-card">
                    <h4 class="rmm-form-card-title">💰 <?php _e('قیمت‌گذاری و تخفیف', 'fondogh-online-menu'); ?></h4>
                    <div class="rmm-grid-2">
                        <div class="rmm-form-group">
                            <label><?php _e('قیمت عادی (تومان) *', 'fondogh-online-menu'); ?></label>
                            <input type="text" inputmode="numeric" id="rmm-food-form-price" class="rmm-thousand-sep" required placeholder="0">
                        </div>
                        <div class="rmm-form-group">
                            <label><?php _e('قیمت تخفیف‌دار / فروش ویژه (تومان)', 'fondogh-online-menu'); ?></label>
                            <input type="text" inputmode="numeric" id="rmm-food-form-sale-price" class="rmm-thousand-sep" placeholder="<?php _e('اختیاری', 'fondogh-online-menu'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Nutritional & Ingredients -->
                <div class="rmm-form-card">
                    <h4 class="rmm-form-card-title">🥗 <?php _e('اطلاعات تکمیلی و مواد تشکیل‌دهنده', 'fondogh-online-menu'); ?></h4>
                    <div class="rmm-grid-2">
                        <div class="rmm-form-group">
                            <label><?php _e('ارزش غذایی و کالری', 'fondogh-online-menu'); ?></label>
                            <input type="text" id="rmm-food-form-calories" placeholder="<?php _e('مثلاً: 520 Kcal', 'fondogh-online-menu'); ?>">
                        </div>
                        <div class="rmm-form-group">
                            <label><?php _e('زمان آماده‌سازی (دقیقه)', 'fondogh-online-menu'); ?></label>
                            <input type="number" id="rmm-food-form-prep-time" placeholder="<?php _e('مثلاً: 25', 'fondogh-online-menu'); ?>">
                        </div>
                    </div>

                    <div class="rmm-form-group">
                        <label><?php _e('توضیحات کوتاه و معرفی غذا (Description)', 'fondogh-online-menu'); ?></label>
                        <textarea id="rmm-food-form-desc" rows="2" placeholder="<?php _e('توضیحات جذاب درباره طعم، بافت و نحوه طبخ این غذا...', 'fondogh-online-menu'); ?>"></textarea>
                    </div>

                    <div class="rmm-form-group">
                        <label><?php _e('مواد تشکیل‌دهنده و ترکیبات (Ingredients)', 'fondogh-online-menu'); ?></label>
                        <textarea id="rmm-food-form-ingredients" rows="2" placeholder="<?php _e('گوشت چرخ‌کرده، زعفران، پیاز، کره محلی...', 'fondogh-online-menu'); ?>"></textarea>
                    </div>
                </div>

                <!-- Section 4: Multilingual & AI Auto-Translate -->
                <?php if (!empty($trans_langs)) : ?>
                    <div class="rmm-form-card rmm-ai-card">
                        <div class="rmm-ai-card-header">
                            <div>
                                <strong class="rmm-ai-title">
                                    <span>🌐</span> <?php _e('ترجمه و زبان‌های فعال منو', 'fondogh-online-menu'); ?>
                                </strong>
                                <span class="rmm-ai-subtitle"><?php _e('تکمیل خودکار نام، توضیحات و ترکیبات با هوش مصنوعی', 'fondogh-online-menu'); ?></span>
                            </div>
                            <button type="button" id="rmm-ai-translate-btn" class="rmm-ai-trigger-btn">
                                <span id="rmm-ai-spin-icon">✨</span>
                                <span id="rmm-ai-btn-text"><?php _e('ترجمه خودکار هوشمند', 'fondogh-online-menu'); ?></span>
                            </button>
                        </div>

                        <div class="rmm-trans-rows-list">
                            <?php foreach ($trans_langs as $tcode => $tdata) : ?>
                                <div class="rmm-lang-trans-row" data-lang="<?php echo esc_attr($tcode); ?>">
                                    <div class="rmm-trans-lang-label">
                                        <span class="flag"><?php echo esc_html($tdata['flag']); ?></span>
                                        <span class="native"><?php echo esc_html($tdata['native']); ?></span>
                                        <span class="name">(<?php echo esc_html($tdata['name']); ?>)</span>
                                    </div>
                                    <div class="rmm-grid-3">
                                        <div>
                                            <input type="text" class="rmm-trans-name-input" data-lang="<?php echo esc_attr($tcode); ?>" placeholder="<?php printf(__('نام به %s', 'fondogh-online-menu'), $tdata['native']); ?>">
                                        </div>
                                        <div>
                                            <input type="text" class="rmm-trans-desc-input" data-lang="<?php echo esc_attr($tcode); ?>" placeholder="<?php printf(__('توضیح به %s', 'fondogh-online-menu'), $tdata['native']); ?>">
                                        </div>
                                        <div>
                                            <input type="text" class="rmm-trans-ingredients-input" data-lang="<?php echo esc_attr($tcode); ?>" placeholder="<?php printf(__('ترکیبات به %s', 'fondogh-online-menu'), $tdata['native']); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section 5: Branch Availability -->
                <?php if (!empty($branches)) : ?>
                    <div class="rmm-form-card">
                        <h4 class="rmm-form-card-title">🏢 <?php _e('انتخاب شعب عرضه این غذا', 'fondogh-online-menu'); ?></h4>
                        <div class="rmm-branches-check-grid">
                            <?php foreach ($branches as $b) : ?>
                                <label class="rmm-branch-pill-checkbox">
                                    <input type="checkbox" name="rmm_food_branches[]" value="<?php echo esc_attr($b->term_id); ?>" class="rmm-branch-checkbox" checked>
                                    <span class="check-box-custom"></span>
                                    <span class="branch-pill-name"><?php echo esc_html($b->name); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Modal Actions -->
                <div class="rmm-modal-actions">
                    <button type="button" class="rmm-btn-secondary" id="rmm-cancel-food-btn"><?php _e('انصراف', 'fondogh-online-menu'); ?></button>
                    <button type="submit" class="rmm-btn-primary rmm-btn-save-modal" id="rmm-save-food-btn">
                        <span><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M17 21.25H7C3.35 21.25 1.25 19.15 1.25 15.5V8.5C1.25 4.85 3.35 2.75 7 2.75H14C14.41 2.75 14.75 3.09 14.75 3.5C14.75 3.91 14.41 4.25 14 4.25H7C4.14 4.25 2.75 5.64 2.75 8.5V15.5C2.75 18.36 4.14 19.75 7 19.75H17C19.86 19.75 21.25 18.36 21.25 15.5V10.5C21.25 10.09 21.59 9.75 22 9.75C22.41 9.75 22.75 10.09 22.75 10.5V15.5C22.75 19.15 20.65 21.25 17 21.25Z"/> <path d="M11.9998 12.8707C11.1598 12.8707 10.3098 12.6108 9.65978 12.0808L6.52978 9.58076C6.20978 9.32076 6.14978 8.85075 6.40978 8.53075C6.66978 8.21075 7.13977 8.15076 7.45977 8.41076L10.5898 10.9108C11.3498 11.5208 12.6398 11.5208 13.3998 10.9108L14.5798 9.97076C14.8998 9.71076 15.3798 9.76075 15.6298 10.0908C15.8898 10.4108 15.8398 10.8908 15.5098 11.1408L14.3298 12.0808C13.6898 12.6108 12.8398 12.8707 11.9998 12.8707Z"/> <path d="M19.5 8.75C17.71 8.75 16.25 7.29 16.25 5.5C16.25 3.71 17.71 2.25 19.5 2.25C21.29 2.25 22.75 3.71 22.75 5.5C22.75 7.29 21.29 8.75 19.5 8.75ZM19.5 3.75C18.54 3.75 17.75 4.54 17.75 5.5C17.75 6.46 18.54 7.25 19.5 7.25C20.46 7.25 21.25 6.46 21.25 5.5C21.25 4.54 20.46 3.75 19.5 3.75Z"/> </svg></span>
                        <span><?php _e('ذخیره غذا در منو', 'fondogh-online-menu'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- GLASSMORPHIC MODAL: CATEGORY MANAGER LIST -->
    <div id="rmm-category-modal" class="rmm-modal-backdrop" style="display:none;">
        <div class="rmm-modal-box rmm-modal-box-md">
            <button class="rmm-modal-close-btn" id="rmm-close-cat-modal-btn" type="button" aria-label="Close modal">&times;</button>
            <div class="rmm-modal-header">
                <div class="modal-header-icon" style="color:var(--rmm-primary);"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M7 10.75H5C2.58 10.75 1.25 9.42 1.25 7V5C1.25 2.58 2.58 1.25 5 1.25H7C9.42 1.25 10.75 2.58 10.75 5V7C10.75 9.42 9.42 10.75 7 10.75ZM5 2.75C3.42 2.75 2.75 3.42 2.75 5V7C2.75 8.58 3.42 9.25 5 9.25H7C8.58 9.25 9.25 8.58 9.25 7V5C9.25 3.42 8.58 2.75 7 2.75H5Z"/> <path d="M19 10.75H17C14.58 10.75 13.25 9.42 13.25 7V5C13.25 2.58 14.58 1.25 17 1.25H19C21.42 1.25 22.75 2.58 22.75 5V7C22.75 9.42 21.42 10.75 19 10.75ZM17 2.75C15.42 2.75 14.75 3.42 14.75 5V7C14.75 8.58 15.42 9.25 17 9.25H19C20.58 9.25 21.25 8.58 21.25 7V5C21.25 3.42 20.58 2.75 19 2.75H17Z"/> <path d="M19 22.75H17C14.58 22.75 13.25 21.42 13.25 19V17C13.25 14.58 14.58 13.25 17 13.25H19C21.42 13.25 22.75 14.58 22.75 17V19C22.75 21.42 21.42 22.75 19 22.75ZM17 14.75C15.42 14.75 14.75 15.42 14.75 17V19C14.75 20.58 15.42 21.25 17 21.25H19C20.58 21.25 21.25 20.58 21.25 19V17C21.25 15.42 20.58 14.75 19 14.75H17Z"/> <path d="M7 22.75H5C2.58 22.75 1.25 21.42 1.25 19V17C1.25 14.58 2.58 13.25 5 13.25H7C9.42 13.25 10.75 14.58 10.75 17V19C10.75 21.42 9.42 22.75 7 22.75ZM5 14.75C3.42 14.75 2.75 15.42 2.75 17V19C2.75 20.58 3.42 21.25 5 21.25H7C8.58 21.25 9.25 20.58 9.25 19V17C9.25 15.42 8.58 14.75 7 14.75H5Z"/> </svg></div>
                <div>
                    <h3 class="rmm-modal-title"><?php _e('مدیریت و اولویت‌بندی دسته‌بندی‌ها', 'fondogh-online-menu'); ?></h3>
                    <p class="rmm-modal-desc"><?php _e('تعیین ترتیب نمایش دسته‌ها در منوی مشتری، افزودن دسته‌بندی و ویرایش ترجمه‌ها', 'fondogh-online-menu'); ?></p>
                </div>
            </div>

            <!-- راهنمای اولویت‌بندی دسته‌بندی‌ها -->
            <div class="rmm-cat-priority-alert" style="background:rgba(59, 130, 246, 0.08); border:1px solid rgba(59, 130, 246, 0.25); border-radius:12px; padding:12px 14px; margin-bottom:16px; display:flex; align-items:flex-start; gap:10px; font-size:12.5px; line-height:1.6; color:var(--rmm-text-main);">
                <span style="font-size:18px;">💡</span>
                <div>
                    <strong style="color:var(--rmm-primary);"><?php _e('راهنمای اولویت‌بندی منو:', 'fondogh-online-menu'); ?></strong>
                    <?php _e('با دکمه‌های ⬆️ و ⬇️ در هر ردیف، ترتیب نمایش دسته‌بندی‌ها را در منوی آنلاین مشتری تنظیم کنید (مثلاً غذای اصلی در ردیف ۱ اولویت و نوشیدنی‌ها در انتها). تغییرات فوراً ذخیره می‌شوند.', 'fondogh-online-menu'); ?>
                </div>
            </div>

            <div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <button type="button" class="rmm-btn-primary" id="rmm-create-new-cat-btn">
                    <span>+</span>
                    <span><?php _e('افزودن دسته‌بندی جدید', 'fondogh-online-menu'); ?></span>
                </button>
                <button type="button" class="rmm-btn-secondary" id="rmm-save-cat-order-btn" style="display:inline-flex; align-items:center; gap:6px;">
                    <span>💾</span>
                    <span><?php _e('ذخیره ترتیب اولویت‌ها', 'fondogh-online-menu'); ?></span>
                </button>
            </div>

            <div id="rmm-category-list-container" class="rmm-category-list-container">
                <div class="rmm-loading-state"><?php _e('در حال بارگذاری دسته‌بندی‌ها...', 'fondogh-online-menu'); ?></div>
            </div>
        </div>
    </div>

    <!-- GLASSMORPHIC MODAL: ADD / EDIT CATEGORY -->
    <div id="rmm-category-edit-modal" class="rmm-modal-backdrop" style="display:none;">
        <div class="rmm-modal-box rmm-modal-box-sm">
            <button class="rmm-modal-close-btn" id="rmm-close-cat-edit-modal-btn" type="button" aria-label="Close modal">&times;</button>
            <div class="rmm-modal-header">
                <div class="modal-header-icon" style="color:var(--rmm-primary);"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M7 10.75H5C2.58 10.75 1.25 9.42 1.25 7V5C1.25 2.58 2.58 1.25 5 1.25H7C9.42 1.25 10.75 2.58 10.75 5V7C10.75 9.42 9.42 10.75 7 10.75ZM5 2.75C3.42 2.75 2.75 3.42 2.75 5V7C2.75 8.58 3.42 9.25 5 9.25H7C8.58 9.25 9.25 8.58 9.25 7V5C9.25 3.42 8.58 2.75 7 2.75H5Z"/> <path d="M19 10.75H17C14.58 10.75 13.25 9.42 13.25 7V5C13.25 2.58 14.58 1.25 17 1.25H19C21.42 1.25 22.75 2.58 22.75 5V7C22.75 9.42 21.42 10.75 19 10.75ZM17 2.75C15.42 2.75 14.75 3.42 14.75 5V7C14.75 8.58 15.42 9.25 17 9.25H19C20.58 9.25 21.25 8.58 21.25 7V5C21.25 3.42 20.58 2.75 19 2.75H17Z"/> <path d="M19 22.75H17C14.58 22.75 13.25 21.42 13.25 19V17C13.25 14.58 14.58 13.25 17 13.25H19C21.42 13.25 22.75 14.58 22.75 17V19C22.75 21.42 21.42 22.75 19 22.75ZM17 14.75C15.42 14.75 14.75 15.42 14.75 17V19C14.75 20.58 15.42 21.25 17 21.25H19C20.58 21.25 21.25 20.58 21.25 19V17C21.25 15.42 20.58 14.75 19 14.75H17Z"/> <path d="M7 22.75H5C2.58 22.75 1.25 21.42 1.25 19V17C1.25 14.58 2.58 13.25 5 13.25H7C9.42 13.25 10.75 14.58 10.75 17V19C10.75 21.42 9.42 22.75 7 22.75ZM5 14.75C3.42 14.75 2.75 15.42 2.75 17V19C2.75 20.58 3.42 21.25 5 21.25H7C8.58 21.25 9.25 20.58 9.25 19V17C9.25 15.42 8.58 14.75 7 14.75H5Z"/> </svg></div>
                <div>
                    <h3 id="rmm-cat-edit-modal-title" class="rmm-modal-title"><?php _e('تعریف دسته‌بندی غذا', 'fondogh-online-menu'); ?></h3>
                    <p class="rmm-modal-desc"><?php _e('مشخصات، تصویر شاخص و ترجمه‌های هوشمند دسته', 'fondogh-online-menu'); ?></p>
                </div>
            </div>

            <form id="rmm-category-form" class="rmm-modal-form">
                <input type="hidden" id="rmm-cat-form-id" value="0">
                <input type="hidden" id="rmm-cat-form-thumb-id" value="0">

                <div class="rmm-form-card">
                    <div class="rmm-form-group">
                        <label><?php _e('نام دسته‌بندی (فارسی / زبان اصلی) *', 'fondogh-online-menu'); ?></label>
                        <input type="text" id="rmm-cat-form-name" required placeholder="<?php _e('مثلاً: پیتزا و پیش‌غذا', 'fondogh-online-menu'); ?>">
                    </div>

                    <div class="rmm-form-group">
                        <label><?php _e('توضیح کوتاه دسته‌بندی (اختیاری)', 'fondogh-online-menu'); ?></label>
                        <textarea id="rmm-cat-form-desc" rows="2" placeholder="<?php _e('توضیح کوتاه برای نمایش در بالای منو...', 'fondogh-online-menu'); ?>"></textarea>
                    </div>

                    <div class="rmm-form-group">
                        <label><?php _e('اولویت نمایش در منو (رتبه عددی)', 'fondogh-online-menu'); ?></label>
                        <input type="number" id="rmm-cat-form-order" min="1" placeholder="<?php _e('مثلاً: ۱ برای اولویت اول و نمایش در بالای منو', 'fondogh-online-menu'); ?>">
                        <span style="font-size:11px; color:var(--rmm-text-muted); display:block; margin-top:3px;"><?php _e('عدد کمتر به معنی اولویت بالاتر و نمایش در ابتدای منوی آنلاین است.', 'fondogh-online-menu'); ?></span>
                    </div>

                    <!-- Category Thumbnail Dropzone -->
                    <div class="rmm-form-group">
                        <label><?php _e('تصویر شاخص دسته‌بندی (تبدیل خودکار به WebP)', 'fondogh-online-menu'); ?></label>
                        <div class="rmm-image-dropzone" id="rmm-cat-image-dropzone">
                            <input type="file" id="rmm-cat-image-file-input" accept="image/*" style="display:none;">
                            <div class="dropzone-empty" id="rmm-cat-dropzone-empty">
                                <span class="dropzone-icon">🖼️</span>
                                <p class="dropzone-text"><strong><?php _e('کلیک کنید', 'fondogh-online-menu'); ?></strong> <?php _e('یا تصویر دسته‌بندی را به اینجا بکشید', 'fondogh-online-menu'); ?></p>
                                <span class="dropzone-upload-btn">📷 <?php _e('انتخاب و آپلود تصویر دسته', 'fondogh-online-menu'); ?></span>
                                <span class="dropzone-hint"><?php _e('فرمت‌های JPG، PNG یا WebP - با فشرده‌سازی خودکار', 'fondogh-online-menu'); ?></span>
                            </div>
                            <div class="dropzone-preview" id="rmm-cat-dropzone-preview" style="display:none;">
                                <img src="" id="rmm-cat-preview-img" alt="پیش‌نمایش تصویر دسته">
                                <div class="dropzone-preview-actions">
                                    <button type="button" class="rmm-btn-mini rmm-btn-change-image" id="rmm-cat-change-img-btn">🔄 <?php _e('تغییر تصویر', 'fondogh-online-menu'); ?></button>
                                    <button type="button" class="dropzone-remove-btn" id="rmm-cat-remove-img-btn" title="<?php _e('حذف تصویر', 'fondogh-online-menu'); ?>">🗑️ <?php _e('حذف تصویر', 'fondogh-online-menu'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Smart Translation for Category -->
                <?php if (!empty($trans_langs)) : ?>
                    <div class="rmm-form-card rmm-ai-card">
                        <div class="rmm-ai-header">
                            <div>
                                <h4 class="rmm-form-card-title" style="margin:0; border:none; padding:0;">✨ <?php _e('ترجمه چندزبانه هوشمند دسته', 'fondogh-online-menu'); ?></h4>
                                <p style="margin:4px 0 0; font-size:12px; color:var(--rmm-text-muted);"><?php _e('با یک کلیک نام و توضیحات دسته‌بندی را به تمام زبان‌ها ترجمه کنید.', 'fondogh-online-menu'); ?></p>
                            </div>
                            <button type="button" id="rmm-cat-ai-translate-btn" class="rmm-btn-ai">
                                <span class="ai-sparkle">✨</span>
                                <span class="ai-btn-text"><?php _e('ترجمه خودکار با هوش مصنوعی', 'fondogh-online-menu'); ?></span>
                                <span class="ai-btn-loader" style="display:none;">⏳</span>
                            </button>
                        </div>

                        <!-- Multilingual Fields for Category -->
                        <div class="rmm-translations-grid" style="margin-top:14px;">
                            <?php foreach ($trans_langs as $lang_code => $l_data) : ?>
                                <div class="rmm-lang-box" data-lang="<?php echo esc_attr($lang_code); ?>">
                                    <div class="rmm-lang-box-header">
                                        <span class="rmm-lang-flag"><?php echo esc_html($l_data['flag']); ?></span>
                                        <strong><?php echo esc_html($l_data['native']); ?> (<?php echo esc_html($l_data['name']); ?>)</strong>
                                    </div>
                                    <div class="rmm-form-group" style="margin-top:8px;">
                                        <input type="text" class="rmm-cat-trans-name" data-lang="<?php echo esc_attr($lang_code); ?>" placeholder="<?php printf(__('نام دسته به %s...', 'fondogh-online-menu'), $l_data['native']); ?>">
                                    </div>
                                    <div class="rmm-form-group" style="margin-top:6px;">
                                        <input type="text" class="rmm-cat-trans-desc" data-lang="<?php echo esc_attr($lang_code); ?>" placeholder="<?php printf(__('توضیح کوتاه به %s...', 'fondogh-online-menu'), $l_data['native']); ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="rmm-modal-actions">
                    <button type="button" class="rmm-btn-secondary" id="rmm-cancel-cat-btn"><?php _e('انصراف', 'fondogh-online-menu'); ?></button>
                    <button type="submit" class="rmm-btn-primary rmm-btn-save-modal" id="rmm-save-cat-btn">
                        <span><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M17 21.25H7C3.35 21.25 1.25 19.15 1.25 15.5V8.5C1.25 4.85 3.35 2.75 7 2.75H14C14.41 2.75 14.75 3.09 14.75 3.5C14.75 3.91 14.41 4.25 14 4.25H7C4.14 4.25 2.75 5.64 2.75 8.5V15.5C2.75 18.36 4.14 19.75 7 19.75H17C19.86 19.75 21.25 18.36 21.25 15.5V10.5C21.25 10.09 21.59 9.75 22 9.75C22.41 9.75 22.75 10.09 22.75 10.5V15.5C22.75 19.15 20.65 21.25 17 21.25Z"/> <path d="M11.9998 12.8707C11.1598 12.8707 10.3098 12.6108 9.65978 12.0808L6.52978 9.58076C6.20978 9.32076 6.14978 8.85075 6.40978 8.53075C6.66978 8.21075 7.13977 8.15076 7.45977 8.41076L10.5898 10.9108C11.3498 11.5208 12.6398 11.5208 13.3998 10.9108L14.5798 9.97076C14.8998 9.71076 15.3798 9.76075 15.6298 10.0908C15.8898 10.4108 15.8398 10.8908 15.5098 11.1408L14.3298 12.0808C13.6898 12.6108 12.8398 12.8707 11.9998 12.8707Z"/> <path d="M19.5 8.75C17.71 8.75 16.25 7.29 16.25 5.5C16.25 3.71 17.71 2.25 19.5 2.25C21.29 2.25 22.75 3.71 22.75 5.5C22.75 7.29 21.29 8.75 19.5 8.75ZM19.5 3.75C18.54 3.75 17.75 4.54 17.75 5.5C17.75 6.46 18.54 7.25 19.5 7.25C20.46 7.25 21.25 6.46 21.25 5.5C21.25 4.54 20.46 3.75 19.5 3.75Z"/> </svg></span>
                        <span><?php _e('ذخیره دسته‌بندی', 'fondogh-online-menu'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- GLASSMORPHIC MODAL: ADD / EDIT BRANCH -->
    <div id="rmm-branch-modal" class="rmm-modal-backdrop" style="display:none;">
        <div class="rmm-modal-box rmm-modal-box-sm">
            <button class="rmm-modal-close-btn" id="rmm-close-branch-modal-btn" type="button" aria-label="Close modal">&times;</button>
            <div class="rmm-modal-header">
                <div class="modal-header-icon" style="color:var(--rmm-primary);"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M11.9999 14.1695C9.86988 14.1695 8.12988 12.4395 8.12988 10.2995C8.12988 8.15945 9.86988 6.43945 11.9999 6.43945C14.1299 6.43945 15.8699 8.16945 15.8699 10.3095C15.8699 12.4495 14.1299 14.1695 11.9999 14.1695ZM11.9999 7.93945C10.6999 7.93945 9.62988 8.99945 9.62988 10.3095C9.62988 11.6195 10.6899 12.6795 11.9999 12.6795C13.3099 12.6795 14.3699 11.6195 14.3699 10.3095C14.3699 8.99945 13.2999 7.93945 11.9999 7.93945Z"/> <path d="M11.9999 22.76C10.5199 22.76 9.02993 22.2 7.86993 21.09C4.91993 18.25 1.65993 13.72 2.88993 8.33C3.99993 3.44 8.26993 1.25 11.9999 1.25C11.9999 1.25 11.9999 1.25 12.0099 1.25C15.7399 1.25 20.0099 3.44 21.1199 8.34C22.3399 13.73 19.0799 18.25 16.1299 21.09C14.9699 22.2 13.4799 22.76 11.9999 22.76ZM11.9999 2.75C9.08993 2.75 5.34993 4.3 4.35993 8.66C3.27993 13.37 6.23993 17.43 8.91993 20C10.6499 21.67 13.3599 21.67 15.0899 20C17.7599 17.43 20.7199 13.37 19.6599 8.66C18.6599 4.3 14.9099 2.75 11.9999 2.75Z"/> </svg></div>
                <div>
                    <h3 id="rmm-branch-modal-title" class="rmm-modal-title"><?php _e('مشخصات شعبه رستوران', 'fondogh-online-menu'); ?></h3>
                    <p class="rmm-modal-desc"><?php _e('اطلاعات تماس، آدرس و حالت کاری شعبه', 'fondogh-online-menu'); ?></p>
                </div>
            </div>

            <form id="rmm-branch-form" class="rmm-modal-form">
                <input type="hidden" id="rmm-branch-form-id" value="0">

                <div class="rmm-form-card">
                    <div class="rmm-form-group">
                        <label><?php _e('نام شعبه *', 'fondogh-online-menu'); ?></label>
                        <input type="text" id="rmm-branch-form-name" required placeholder="<?php _e('مثلاً: شعبه مرکزی تجریش', 'fondogh-online-menu'); ?>">
                    </div>

                    <div class="rmm-grid-2">
                        <div class="rmm-form-group">
                            <label><?php _e('شماره تماس شعبه', 'fondogh-online-menu'); ?></label>
                            <input type="text" id="rmm-branch-form-phone" placeholder="02188888888">
                        </div>
                        <div class="rmm-form-group">
                            <label><?php _e('ساعات کاری شعبه', 'fondogh-online-menu'); ?></label>
                            <input type="text" id="rmm-branch-form-hours" placeholder="۱۲:۰۰ الی ۲۴:۰۰">
                        </div>
                    </div>

                    <div class="rmm-form-group">
                        <label><?php _e('آدرس پستی دقیق شعبه', 'fondogh-online-menu'); ?></label>
                        <textarea id="rmm-branch-form-address" rows="2" placeholder="<?php _e('تهران، تجریش، خیابان...', 'fondogh-online-menu'); ?>"></textarea>
                    </div>

                    <div class="rmm-form-group">
                        <label><?php _e('حالت کاری منوی این شعبه', 'fondogh-online-menu'); ?></label>
                        <select id="rmm-branch-form-mode">
                            <option value="global"><?php _e('پیروی از تنظیمات سراسری افزونه', 'fondogh-online-menu'); ?></option>
                            <option value="online_delivery"><?php _e('سفارش آنلاین و بیرون‌بر', 'fondogh-online-menu'); ?></option>
                            <option value="dine_in"><?php _e('سفارش سر میز با QR Code', 'fondogh-online-menu'); ?></option>
                            <option value="view_only"><?php _e('کاتالوگ و فقط مشاهده منو (بدون سفارش)', 'fondogh-online-menu'); ?></option>
                        </select>
                    </div>

                    <div class="rmm-form-row" style="margin-top:10px;">
                        <label class="rmm-checkbox-label">
                            <input type="checkbox" id="rmm-branch-form-open" value="1" checked>
                            <span><?php _e('شعبه فعال و آماده دریافت سفارش است', 'fondogh-online-menu'); ?></span>
                        </label>
                    </div>
                </div>

                <div class="rmm-modal-actions">
                    <button type="button" class="rmm-btn-secondary" id="rmm-cancel-branch-btn"><?php _e('انصراف', 'fondogh-online-menu'); ?></button>
                    <button type="submit" class="rmm-btn-primary rmm-btn-save-modal" id="rmm-save-branch-btn">
                        <span><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M17 21.25H7C3.35 21.25 1.25 19.15 1.25 15.5V8.5C1.25 4.85 3.35 2.75 7 2.75H14C14.41 2.75 14.75 3.09 14.75 3.5C14.75 3.91 14.41 4.25 14 4.25H7C4.14 4.25 2.75 5.64 2.75 8.5V15.5C2.75 18.36 4.14 19.75 7 19.75H17C19.86 19.75 21.25 18.36 21.25 15.5V10.5C21.25 10.09 21.59 9.75 22 9.75C22.41 9.75 22.75 10.09 22.75 10.5V15.5C22.75 19.15 20.65 21.25 17 21.25Z"/> <path d="M11.9998 12.8707C11.1598 12.8707 10.3098 12.6108 9.65978 12.0808L6.52978 9.58076C6.20978 9.32076 6.14978 8.85075 6.40978 8.53075C6.66978 8.21075 7.13977 8.15076 7.45977 8.41076L10.5898 10.9108C11.3498 11.5208 12.6398 11.5208 13.3998 10.9108L14.5798 9.97076C14.8998 9.71076 15.3798 9.76075 15.6298 10.0908C15.8898 10.4108 15.8398 10.8908 15.5098 11.1408L14.3298 12.0808C13.6898 12.6108 12.8398 12.8707 11.9998 12.8707Z"/> <path d="M19.5 8.75C17.71 8.75 16.25 7.29 16.25 5.5C16.25 3.71 17.71 2.25 19.5 2.25C21.29 2.25 22.75 3.71 22.75 5.5C22.75 7.29 21.29 8.75 19.5 8.75ZM19.5 3.75C18.54 3.75 17.75 4.54 17.75 5.5C17.75 6.46 18.54 7.25 19.5 7.25C20.46 7.25 21.25 6.46 21.25 5.5C21.25 4.54 20.46 3.75 19.5 3.75Z"/> </svg></span>
                        <span><?php _e('ذخیره اطلاعات شعبه', 'fondogh-online-menu'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden Iframe for Direct 80mm Receipt Printing -->
    <iframe id="rmm-receipt-print-frame" style="display:none; width:0; height:0; border:none;"></iframe>

    <?php
    $branches_list = [];
    if (!empty($branches)) {
        foreach ($branches as $b) {
            $is_open = get_term_meta($b->term_id, '_rmm_is_open', true) !== '0';
            $branches_list[] = [
                'id'           => $b->term_id,
                'name'         => html_entity_decode($b->name, ENT_QUOTES, 'UTF-8'),
                'slug'         => $b->slug,
                'phone'        => get_term_meta($b->term_id, '_rmm_phone', true) ?: '',
                'address'      => get_term_meta($b->term_id, '_rmm_address', true) ?: '',
                'hours'        => get_term_meta($b->term_id, '_rmm_hours', true) ?: '',
                'mode'         => get_term_meta($b->term_id, '_rmm_mode', true) ?: 'global',
                'is_open'      => $is_open,
                'min_order'    => floatval(get_term_meta($b->term_id, '_rmm_min_order', true) ?: 0),
                'tables_count' => absint(get_term_meta($b->term_id, '_rmm_tables_count', true) ?: 10),
            ];
        }
    }
    ?>
    <!-- Config for SPA -->
    <script>
        window.rmmPortalConfig = {
            rest_url: '<?php echo esc_url_raw(rest_url('rmm/v1/portal/')); ?>',
            nonce: '<?php echo wp_create_nonce('wp_rest'); ?>',
            active_branch_id: <?php echo absint($active_branch_id); ?>,
            enable_sound: <?php echo (\RMM\Core\Settings::get('enable_sound_alert', 'yes') === 'yes') ? 'true' : 'false'; ?>,
            receipt_template_url: '<?php echo esc_url(RMM_PLUGIN_URL . 'includes/FrontendDashboard/Views/thermal-receipt.php'); ?>',
            trans_langs: <?php echo wp_json_encode(array_keys($trans_langs)); ?>,
            branches: <?php echo wp_json_encode($branches_list); ?>
        };
    </script>
    <script src="<?php echo esc_url(RMM_PLUGIN_URL . 'assets/js/portal-pwa.js?v=' . RMM_VERSION); ?>"></script>
<?php endif; ?>

</body>
</html>
