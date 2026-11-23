<?php
namespace RMM\DesignSystem;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsPage {

    const OPTION_KEY = 'rmm_design_system';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menu'], 70);
        add_action('admin_post_rmm_studio_frame', [__CLASS__, 'render_studio_frame']);
        add_action('wp_ajax_rmm_save_design_system', [__CLASS__, 'ajax_save_settings']);
        add_action('wp_ajax_rmm_reset_design_system', [__CLASS__, 'ajax_reset_settings']);
    }

    public static function register_menu() {
        add_submenu_page(
            'rmm-settings',
            __('استودیو دیزاین سیستم فندق', 'fondogh-online-menu'),
            __('🎨 دیزاین سیستم', 'fondogh-online-menu'),
            'manage_options',
            'rmm-design-system',
            [__CLASS__, 'render_admin_shell']
        );
    }

    public static function get_settings() {
        $saved = get_option(self::OPTION_KEY, null);
        if (!is_array($saved)) {
            return TokenSchema::get_default_settings();
        }
        return TokenSchema::sanitize_settings($saved);
    }

    public static function render_admin_shell() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('شما اجازهٔ دسترسی به این بخش را ندارید.', 'fondogh-online-menu'));
        }

        $frame_url = wp_nonce_url(admin_url('admin-post.php?action=rmm_studio_frame'), 'rmm_studio_frame');
        ?>
        <style>
            #wpwrap, #wpcontent, #wpbody, #wpbody-content {
                min-height: 0 !important;
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
            }
            #wpcontent { padding-left: 0 !important; padding-right: 0 !important; }
            #wpfooter { display: none !important; }
            .rmm-admin-frame-wrap {
                position: fixed;
                top: 32px;
                bottom: 0;
                left: 160px;
                right: 0;
                z-index: 10;
                overflow: hidden;
                background: #f8fafc;
            }
            body.rtl .rmm-admin-frame-wrap {
                left: 0;
                right: 160px;
            }
            body.folded .rmm-admin-frame-wrap {
                left: 36px;
                right: 0;
            }
            body.rtl.folded .rmm-admin-frame-wrap {
                left: 0;
                right: 36px;
            }
            @media only screen and (max-width: 960px) {
                body.auto-fold .rmm-admin-frame-wrap {
                    left: 36px;
                    right: 0;
                }
                body.rtl.auto-fold .rmm-admin-frame-wrap {
                    left: 0;
                    right: 36px;
                }
            }
            @media screen and (max-width: 782px) {
                .rmm-admin-frame-wrap,
                body.rtl .rmm-admin-frame-wrap,
                body.folded .rmm-admin-frame-wrap,
                body.rtl.folded .rmm-admin-frame-wrap,
                body.auto-fold .rmm-admin-frame-wrap,
                body.rtl.auto-fold .rmm-admin-frame-wrap {
                    top: 46px;
                    left: 0;
                    right: 0;
                }
            }
            #rmm-studio-iframe {
                width: 100%;
                height: 100%;
                border: 0;
                display: block;
            }
        </style>
        <div class="rmm-admin-frame-wrap">
            <iframe id="rmm-studio-iframe" src="<?php echo esc_url($frame_url); ?>" title="استودیو دیزاین سیستم فندق"></iframe>
        </div>
        <?php
    }

    public static function render_studio_frame() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('دسترسی غیرمجاز.', 'fondogh-online-menu'), 403);
        }
        check_admin_referer('rmm_studio_frame');

        $template_file = RMM_PLUGIN_PATH . 'admin/studio/studio-template.html';
        if (!file_exists($template_file)) {
            wp_die(esc_html__('قالب استودیو پیدا نشد.', 'fondogh-online-menu'), 500);
        }

        $html = file_get_contents($template_file);
        $settings = self::get_settings();
        $defaults = TokenSchema::get_default_settings();

        $boot_config = [
            'version'    => defined('RMM_VERSION') ? RMM_VERSION : '1.0.0',
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'saveNonce'  => wp_create_nonce('rmm_save_design_system'),
            'resetNonce' => wp_create_nonce('rmm_reset_design_system'),
            'pluginUrl'  => defined('RMM_PLUGIN_URL') ? RMM_PLUGIN_URL : '',
            'settings'   => $settings,
            'defaults'   => $defaults,
        ];

        $boot_script = '<script>window.RMM_STUDIO_CONFIG = ' . wp_json_encode($boot_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>';

        $active_mode   = isset($settings['activeMode']) ? $settings['activeMode'] : 'light';
        $active_tokens = isset($settings[$active_mode]) ? $settings[$active_mode] : TokenSchema::get_default_tokens($active_mode);
        $token_css     = CssCompiler::build_variables_block($active_tokens, '#rmm-preview-root');

        $html = str_replace(
            ['{{PLUGIN_URL}}', '{{VERSION}}', '{{RMM_BOOT_CONFIG}}', '{{RMM_COMPONENT_CSS}}', '{{RMM_TOKEN_CSS}}', '{{RMM_ACTIVE_MODE}}'],
            [
                esc_url(RMM_PLUGIN_URL),
                esc_attr(defined('RMM_VERSION') ? RMM_VERSION : '1.0.0'),
                $boot_script,
                '<style id="rmm-ds-component-css">' . CssCompiler::get_component_mapping_css() . '</style>',
                '<style id="rmm-ds-token-css">' . $token_css . '</style>',
                esc_attr($active_mode),
            ],
            $html
        );

        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    public static function ajax_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('دسترسی غیرمجاز.', 'fondogh-online-menu')], 403);
        }
        check_ajax_referer('rmm_save_design_system', 'nonce');

        $raw_json = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : '';
        $decoded = is_string($raw_json) ? json_decode($raw_json, true) : null;
        if (!is_array($decoded)) {
            wp_send_json_error(['message' => __('داده‌های دریافتی نامعتبر است.', 'fondogh-online-menu')], 400);
        }

        $clean = TokenSchema::sanitize_settings($decoded);
        update_option(self::OPTION_KEY, $clean, false); // autoload = false

        $compiled = CssCompiler::compile_and_save($clean);

        wp_send_json_success([
            'message'  => __('تنظیمات دیزاین سیستم با موفقیت ذخیره و استایل‌ها کامپایل شدند.', 'fondogh-online-menu'),
            'settings' => $clean,
            'compiled' => $compiled,
        ]);
    }

    public static function ajax_reset_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('دسترسی غیرمجاز.', 'fondogh-online-menu')], 403);
        }
        check_ajax_referer('rmm_reset_design_system', 'nonce');

        $defaults = TokenSchema::get_default_settings();
        update_option(self::OPTION_KEY, $defaults, false);

        $compiled = CssCompiler::compile_and_save($defaults);

        wp_send_json_success([
            'message'  => __('تنظیمات دیزاین سیستم به حالت پیش‌فرض بازگردانی شد.', 'fondogh-online-menu'),
            'settings' => $defaults,
            'compiled' => $compiled,
        ]);
    }
}
