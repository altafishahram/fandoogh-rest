<?php
namespace RMM\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Settings {

    private static $options = null;

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menus']);
        add_action('admin_init', [$this, 'register_settings_fields']);
    }

    /**
     * Get single option value
     */
    public static function get($key, $default = '') {
        if (self::$options === null) {
            self::$options = get_option('rmm_settings', []);
        }
        return isset(self::$options[$key]) ? self::$options[$key] : $default;
    }

    /**
     * Update an option value
     */
    public static function set($key, $value) {
        $options = get_option('rmm_settings', []);
        $options[$key] = $value;
        self::$options = $options;
        return update_option('rmm_settings', $options);
    }

    /**
     * Check if global mode is view_only
     */
    public static function is_view_only() {
        return self::get('operation_mode', 'online_delivery') === 'view_only';
    }

    /**
     * Check if branches are active
     */
    public static function is_branches_enabled() {
        return self::get('enable_branches', 'yes') === 'yes';
    }

    public function register_admin_menus() {
        add_menu_page(
            __('منو آنلاین فندق', 'fondogh-online-menu'),
            __('منو آنلاین فندق', 'fondogh-online-menu'),
            'manage_restaurant_menu',
            'rmm-settings',
            [$this, 'render_settings_page'],
            'dashicons-food',
            56
        );

        add_submenu_page(
            'rmm-settings',
            __('تنظیمات منو آنلاین فندق', 'fondogh-online-menu'),
            __('تنظیمات سیستم', 'fondogh-online-menu'),
            'manage_options',
            'rmm-settings',
            [$this, 'render_settings_page']
        );

        $portal_slug = self::get('dashboard_slug', 'restaurant-portal');
        add_submenu_page(
            'rmm-settings',
            __('ورود به پنل فرانت‌اند', 'fondogh-online-menu'),
            __('🚀 پیشخوان PWA فندق', 'fondogh-online-menu'),
            'read',
            'rmm-portal-launch',
            [$this, 'render_portal_launch_page']
        );

        add_action('admin_footer', function () use ($portal_slug) {
            $url = home_url('/' . $portal_slug . '/');
            ?>
            <script>
            jQuery(document).ready(function($) {
                $('a[href*="rmm-portal-launch"]').attr('href', '<?php echo esc_url($url); ?>').attr('target', '_blank');
            });
            </script>
            <?php
        });
    }

    public function render_portal_launch_page() {
        $slug = self::get('dashboard_slug', 'restaurant-portal');
        $portal_url = home_url('/' . $slug . '/');
        ?>
        <div class="wrap rmm-admin-inline-b397f0fa">
            <div class="rmm-admin-inline-f59db8d4">
                <div class="rmm-admin-inline-a285a465">🍽️</div>
                <h2 class="rmm-admin-inline-d8197cf3"><?php _e('پیشخوان هوشمند رستوران فندق', 'fondogh-online-menu'); ?></h2>
                <p class="rmm-admin-inline-5cbe4a2e"><?php _e('در حال انتقال به پیشخوان مستقل فرانت‌اند...', 'fondogh-online-menu'); ?></p>
                <a href="<?php echo esc_url($portal_url); ?>" target="_blank" class="button button-primary button-hero" style="font-weight: bold; border-radius: 8px;">
                    🚀 <?php _e('ورود به پیشخوان در تب جدید', 'fondogh-online-menu'); ?> &rarr;
                </a>
            </div>
        </div>
        <script>
            window.location.href = '<?php echo esc_url($portal_url); ?>';
        </script>
        <?php
    }

    public function register_settings_fields() {
        register_setting('rmm_settings_group', 'rmm_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);
    }

    public function sanitize_settings($input) {
        $clean = [];
        $clean['operation_mode']     = in_array($input['operation_mode'] ?? '', ['view_only', 'dine_in', 'online_delivery']) ? $input['operation_mode'] : 'online_delivery';
        $clean['catalog_hide_price'] = isset($input['catalog_hide_price']) && $input['catalog_hide_price'] === 'yes' ? 'yes' : 'no';
        $clean['catalog_action_btn'] = sanitize_text_field($input['catalog_action_btn'] ?? 'whatsapp');
        $clean['whatsapp_number']    = preg_replace('/[^0-9]/', '', $input['whatsapp_number'] ?? '');
        $clean['phone_number']       = sanitize_text_field($input['phone_number'] ?? '');
        $clean['enable_branches']    = isset($input['enable_branches']) && $input['enable_branches'] === 'yes' ? 'yes' : 'no';
        $clean['dashboard_slug']     = sanitize_title($input['dashboard_slug'] ?? 'restaurant-portal');
        $clean['default_language']   = sanitize_text_field($input['default_language'] ?? 'fa');
        $clean['enable_multi_lang']  = isset($input['enable_multi_lang']) && $input['enable_multi_lang'] === 'yes' ? 'yes' : 'no';
        $clean['enable_sound_alert'] = isset($input['enable_sound_alert']) && $input['enable_sound_alert'] === 'yes' ? 'yes' : 'no';
        $clean['enable_happy_hour']  = isset($input['enable_happy_hour']) && $input['enable_happy_hour'] === 'yes' ? 'yes' : 'no';
        $clean['remove_data_on_uninstall'] = isset($input['remove_data_on_uninstall']) && $input['remove_data_on_uninstall'] === 'yes' ? 'yes' : 'no';

        // Multi-Language & AI Sanitization
        $clean['enable_lang_entrance_popup'] = isset($input['enable_lang_entrance_popup']) && $input['enable_lang_entrance_popup'] === 'yes' ? 'yes' : 'no';
        
        $all_langs = array_keys(\RMM\I18n\MultiLangManager::get_all_languages());
        $enabled_langs = [];
        if (isset($input['enabled_languages']) && is_array($input['enabled_languages'])) {
            foreach ($input['enabled_languages'] as $l) {
                if (in_array($l, $all_langs)) {
                    $enabled_langs[] = sanitize_key($l);
                }
            }
        }
        if (empty($enabled_langs)) {
            $enabled_langs = ['fa', 'en'];
        }
        $clean['enabled_languages'] = $enabled_langs;

        $clean['ai_translate_provider'] = in_array($input['ai_translate_provider'] ?? '', ['google_free', 'gemini']) ? $input['ai_translate_provider'] : 'google_free';

        // The key is never printed in the form, so an empty field means
        // "keep the stored key" rather than "erase the key".
        $stored_settings = get_option('rmm_settings', []);
        $submitted_key   = sanitize_text_field($input['gemini_api_key'] ?? '');
        $is_masked       = (bool) preg_match('/^[\*•·\s]+$/', $submitted_key);
        $clean['gemini_api_key'] = ($submitted_key === '' || $is_masked)
            ? sanitize_text_field($stored_settings['gemini_api_key'] ?? '')
            : $submitted_key;

        // Image Optimization & Auto-WebP
        $clean['enable_webp_conversion'] = isset($input['enable_webp_conversion']) && $input['enable_webp_conversion'] === 'no' ? 'no' : 'yes';
        $clean['image_max_width']        = absint($input['image_max_width'] ?? 1200) ?: 1200;
        $clean['image_max_height']       = absint($input['image_max_height'] ?? 1200) ?: 1200;
        $clean['webp_quality']           = max(10, min(100, absint($input['webp_quality'] ?? 82))) ?: 82;
        $clean['image_max_size_kb']      = absint($input['image_max_size_kb'] ?? 3072) ?: 3072;

        return $clean;
    }

    public function render_settings_page() {
        $settings = get_option('rmm_settings', []);
        $mode = $settings['operation_mode'] ?? 'online_delivery';
        $portal_slug = $settings['dashboard_slug'] ?? 'restaurant-portal';
        $all_languages = \RMM\I18n\MultiLangManager::get_all_languages();
        $enabled_languages = $settings['enabled_languages'] ?? ['fa', 'en'];
        $ai_provider = $settings['ai_translate_provider'] ?? 'google_free';
        ?>
        
        <div class="wrap rmm-admin-wrap rmm-admin-inline-b2f79bf5">
            <div class="rmm-admin-inline-d4260324">
                
                <div class="rmm-admin-inline-95e59fba rmm-admin-flex-row">
                    <div>
                        <h1 class="rmm-admin-inline-c956b2fc rmm-admin-flex-row">
                            <span class="rmm-admin-inline-6893c627">🍱</span> <?php _e('تنظیمات جامع منو آنلاین فندق (Fondogh Menu Pro)', 'fondogh-online-menu'); ?>
                        </h1>
                        <p class="rmm-admin-inline-bb7756b7">
                            <?php printf(__('نسخه %s | سازگار با ووکامرس و المنتور | سیستم ماژولار چندشعبه‌ای فندق', 'fondogh-online-menu'), RMM_VERSION); ?>
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo esc_url(home_url('/' . $portal_slug . '/')); ?>" target="_blank" class="button button-primary" style="background: #0284c7; border-color: #0284c7; padding: 8px 18px; border-radius: 8px; font-weight: 600;">
                            <?php _e('مشاهده پیشخوان فرانت‌اند PWA', 'fondogh-online-menu'); ?> &rarr;
                        </a>
                    </div>
                </div>

                <form method="post" action="options.php">
                    <?php settings_fields('rmm_settings_group'); ?>

                    <!-- Tab 1: Operation Modes -->
                    <div class="rmm-admin-inline-93f1e0f9">
                        <h2 class="rmm-admin-inline-27cbfcb0">
                            <?php _e('۱. حالت کاری منو (Operation Modes)', 'fondogh-online-menu'); ?>
                        </h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row" class="rmm-admin-inline-074f3071"><?php _e('حالت پیش‌فرض منوی رستوران:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <fieldset>
                                        <label class="rmm-admin-inline-accceaef">
                                            <input type="radio" name="rmm_settings[operation_mode]" value="online_delivery" <?php checked($mode, 'online_delivery'); ?>>
                                            <strong><?php _e('سفارش آنلاین و دلیوری (Online Delivery)', 'fondogh-online-menu'); ?></strong>
                                            <p class="description rmm-admin-inline-d10a493d"><?php _e('فرآیند کامل سبد خرید، محاسبه هزینه ارسال و اتصال به درگاه پرداخت ووکامرس.', 'fondogh-online-menu'); ?></p>
                                        </label>

                                        <label class="rmm-admin-inline-accceaef">
                                            <input type="radio" name="rmm_settings[operation_mode]" value="dine_in" <?php checked($mode, 'dine_in'); ?>>
                                            <strong><?php _e('سفارش حضوری سر میز با QR Code (Dine-in Table Ordering)', 'fondogh-online-menu'); ?></strong>
                                            <p class="description rmm-admin-inline-d10a493d"><?php _e('سفارش مستقیم سر میز؛ حذف فیلدهای آدرس پستی در تسویه‌حساب و ثبت خودکار شماره میز در سفارش.', 'fondogh-online-menu'); ?></p>
                                        </label>

                                        <label class="rmm-admin-inline-306f7259">
                                            <input type="radio" name="rmm_settings[operation_mode]" value="view_only" <?php checked($mode, 'view_only'); ?>>
                                            <strong><?php _e('حالت کاتالوگ و ویترین آنلاین (View-Only / Digital Menu)', 'fondogh-online-menu'); ?></strong>
                                            <p class="description rmm-admin-inline-d10a493d"><?php _e('فقط برای مشاهده غذاها، قیمت و ترکیبات؛ سبد خرید غیرفعال و تمام اسکریپت‌های سنگین ووکامرس به طور خودکار حذف می‌شوند.', 'fondogh-online-menu'); ?></p>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>

                            <tr class="rmm-view-only-field" style="<?php echo ($mode === 'view_only') ? '' : 'display:none;'; ?>">
                                <th scope="row"><?php _e('پنهان‌سازی قیمت‌ها در حالت کاتالوگ:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="rmm_settings[catalog_hide_price]" value="yes" <?php checked($settings['catalog_hide_price'] ?? 'no', 'yes'); ?>>
                                        <?php _e('قیمت تمام غذاها در منوی کاتالوگی مخفی شود.', 'fondogh-online-menu'); ?>
                                    </label>
                                </td>
                            </tr>

                            <tr class="rmm-view-only-field" style="<?php echo ($mode === 'view_only') ? '' : 'display:none;'; ?>">
                                <th scope="row"><?php _e('دکمه جایگزین دکمه خرید:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <select name="rmm_settings[catalog_action_btn]" class="rmm-admin-inline-ca0689d3">
                                        <option value="none" <?php selected($settings['catalog_action_btn'] ?? 'whatsapp', 'none'); ?>><?php _e('بدون دکمه (صرفاً نمایش)', 'fondogh-online-menu'); ?></option>
                                        <option value="whatsapp" <?php selected($settings['catalog_action_btn'] ?? 'whatsapp', 'whatsapp'); ?>><?php _e('دکمه سفارش یا استعلام در واتساپ', 'fondogh-online-menu'); ?></option>
                                        <option value="call" <?php selected($settings['catalog_action_btn'] ?? 'whatsapp', 'call'); ?>><?php _e('دکمه تماس تلفنی با رستوران', 'fondogh-online-menu'); ?></option>
                                    </select>
                                </td>
                            </tr>

                            <tr class="rmm-view-only-field" style="<?php echo ($mode === 'view_only') ? '' : 'display:none;'; ?>">
                                <th scope="row"><?php _e('شماره تماس رستوران:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <input type="text" name="rmm_settings[phone_number]" value="<?php echo esc_attr($settings['phone_number'] ?? ''); ?>" class="regular-text" placeholder="مثلاً: 02188888888">
                                </td>
                            </tr>

                            <tr class="rmm-view-only-field" style="<?php echo ($mode === 'view_only') ? '' : 'display:none;'; ?>">
                                <th scope="row"><?php _e('شماره واتساپ (همراه با کد کشور بدون صفر و +):', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <input type="text" name="rmm_settings[whatsapp_number]" value="<?php echo esc_attr($settings['whatsapp_number'] ?? ''); ?>" class="regular-text" placeholder="مثلاً: 989123456789">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Tab 2: Branches & Standalone Portal -->
                    <div class="rmm-admin-inline-acb3fb7c">
                        <h2 class="rmm-admin-inline-43843d6f">
                            <?php _e('۲. مدیریت شعب و پیشخوان اختصاصی فرانت‌اند (PWA Portal)', 'fondogh-online-menu'); ?>
                        </h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('سیستم چندشعبه‌ای (Multi-Branch):', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="rmm_settings[enable_branches]" value="yes" <?php checked($settings['enable_branches'] ?? 'yes', 'yes'); ?>>
                                        <?php _e('فعال‌سازی تفکیک قیمت و موجودی محصولات به ازای هر شعبه به صورت بهینه و سریع', 'fondogh-online-menu'); ?>
                                    </label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('نامک (اسلاگ) پیشخوان مجزا:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <input type="text" name="rmm_settings[dashboard_slug]" value="<?php echo esc_attr($portal_slug); ?>" class="regular-text">
                                    <p class="description">
                                        <?php printf(__('آدرس مستقیم ورود پرسنل بدون ورود به وردپرس: %s', 'fondogh-online-menu'), '<code>' . home_url('/' . $portal_slug . '/') . '</code>'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('صدای آلارم سفارش جدید:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="rmm_settings[enable_sound_alert]" value="yes" <?php checked($settings['enable_sound_alert'] ?? 'yes', 'yes'); ?>>
                                        <?php _e('پخش هشدار صوتی هنگام ثبت سفارش جدید در پنل آشپزخانه و پیشخوان فرانت‌اند', 'fondogh-online-menu'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Tab 3: Multi-Language & AI Translation -->
                    <div class="rmm-admin-inline-acb3fb7c">
                        <h2 class="rmm-admin-inline-f254b61a">
                            <?php _e('۳. سیستم چندزبانه پیشرفته و هوش مصنوعی مترجم (Multi-Language & AI Translation)', 'fondogh-online-menu'); ?>
                        </h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('زبان‌های فعال در منو:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <div class="rmm-admin-inline-b7ec7161">
                                        <?php foreach ($all_languages as $code => $data) : ?>
                                            <label class="rmm-admin-inline-bd3164f6 rmm-admin-flex-row">
                                                <input type="checkbox" name="rmm_settings[enabled_languages][]" value="<?php echo esc_attr($code); ?>" <?php checked(in_array($code, $enabled_languages), true); ?>>
                                                <span class="rmm-admin-inline-d6f2b1da"><?php echo esc_html($data['flag']); ?></span>
                                                <strong><?php echo esc_html($data['native']); ?></strong>
                                                <span class="rmm-admin-inline-c29e8801">(<?php echo esc_html($data['name']); ?>)</span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description rmm-admin-inline-649b8558">
                                        <?php _e('زبان‌های انتخاب شده در نوار تغییر زبان و پاپ‌آپ خوش‌آمدگویی ورودی فعال خواهند شد.', 'fondogh-online-menu'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('پاپ‌آپ انتخاب زبان در بدو ورود:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="rmm_settings[enable_lang_entrance_popup]" value="yes" <?php checked($settings['enable_lang_entrance_popup'] ?? 'no', 'yes'); ?>>
                                        <strong><?php _e('نمایش مودال جذاب انتخاب زبان و کشور در هنگام ورود کاربر به منو یا شعب', 'fondogh-online-menu'); ?></strong>
                                    </label>
                                    <p class="description">
                                        <?php _e('وقتی مشتری برای اولین بار وارد منوی آنلاین می‌شود، پنجره‌ای زیبا با پرچم کشورها باز شده و زبان انتخابی را در مرورگر او ذخیره می‌کند.', 'fondogh-online-menu'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('موتور هوش مصنوعی مترجم غذاها:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <select name="rmm_settings[ai_translate_provider]" id="rmm-ai-provider-select" class="rmm-admin-inline-41ccfad7">
                                        <option value="google_free" <?php selected($ai_provider, 'google_free'); ?>><?php _e('✨ مترجم سریع گوگل (Google Free - بدون نیاز به API Key)', 'fondogh-online-menu'); ?></option>
                                        <option value="gemini" <?php selected($ai_provider, 'gemini'); ?>><?php _e('🤖 هوش مصنوعی Google Gemini (دقت بالا در اصطلاحات آشپزی)', 'fondogh-online-menu'); ?></option>
                                    </select>
                                    <p class="description">
                                        <?php _e('در پیشخوان و فرم افزودن غذا، دکمه «ترجمه با هوش مصنوعی» اطلاعات غذا را با این موتور ترجمه می‌کند.', 'fondogh-online-menu'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr id="rmm-gemini-key-row" style="<?php echo ($ai_provider === 'gemini') ? '' : 'display:none;'; ?>">
                                <th scope="row"><?php _e('کلید اختصاصی Gemini API Key:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <input type="password" name="rmm_settings[gemini_api_key]" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo !empty($settings['gemini_api_key']) ? esc_attr__('کلید ذخیره شده — فقط برای تغییر وارد کنید', 'fondogh-online-menu') : 'AIzaSy...'; ?>">
                                    <p class="description">
                                        <?php _e('برای امنیت، کلید ذخیرهشده در منبع صفحه نمایش داده نمیشود. خالی گذاشتن این فیلد، کلید فعلی را حفظ میکند.', 'fondogh-online-menu'); ?>
                                    </p>
                                    <p class="description">
                                        <?php printf(__('کلید رایگان خود را می‌توانید از پنل %s دریافت نمایید.', 'fondogh-online-menu'), '<a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Tab 4: Image Optimization & WebP -->
                    <div class="rmm-admin-inline-acb3fb7c">
                        <h2 class="rmm-admin-inline-9832ec61">
                            <?php _e('۴. بهینه‌سازی، فشرده‌سازی و تبدیل خودکار به WebP (Image Optimization)', 'fondogh-online-menu'); ?>
                        </h2>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('تبدیل خودکار به WebP:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="rmm_settings[enable_webp_conversion]" value="yes" <?php checked($settings['enable_webp_conversion'] ?? 'yes', 'yes'); ?>>
                                        <strong><?php _e('تبدیل خودکار تمام تصاویر آپلود شده غذاها و دسته‌بندی‌ها به فرمت نسل جدید WebP', 'fondogh-online-menu'); ?></strong>
                                    </label>
                                    <p class="description">
                                        <?php _e('فرمت WebP سرعت بارگذاری منو را به شدت افزایش داده و تا ۷۰٪ حجم تصاویر را بدون افت کیفیت محسوس کاهش می‌دهد.', 'fondogh-online-menu'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('حداکثر ابعاد تصویر (عرض و ارتفاع):', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <div class="rmm-admin-inline-4518c18d rmm-admin-flex-row">
                                        <input type="number" name="rmm_settings[image_max_width]" value="<?php echo esc_attr($settings['image_max_width'] ?? 1200); ?>" style="width: 100px;">
                                        <span>&times;</span>
                                        <input type="number" name="rmm_settings[image_max_height]" value="<?php echo esc_attr($settings['image_max_height'] ?? 1200); ?>" style="width: 100px;">
                                        <span class="description"><?php _e('پیکسل (تصاویر بزرگ‌تر به صورت خودکار به این ابعاد با حفظ نسبت تغییر اندازه می‌یابند)', 'fondogh-online-menu'); ?></span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('کیفیت فشرده‌سازی WebP (کیفیت 1 تا 100):', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <div class="rmm-admin-inline-4518c18d rmm-admin-flex-row">
                                        <input type="number" name="rmm_settings[webp_quality]" min="10" max="100" value="<?php echo esc_attr($settings['webp_quality'] ?? 82); ?>" style="width: 80px;">
                                        <span>%</span>
                                        <span class="description"><?php _e('پیشنهادی: 82٪ (تعادل ایده‌آل بین حجم کم و کیفیت فوق‌العاده)', 'fondogh-online-menu'); ?></span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('حداکثر حجم مجاز فایل آپلود:', 'fondogh-online-menu'); ?></th>
                                <td>
                                    <div class="rmm-admin-inline-4518c18d rmm-admin-flex-row">
                                        <input type="number" name="rmm_settings[image_max_size_kb]" value="<?php echo esc_attr($settings['image_max_size_kb'] ?? 3072); ?>" style="width: 100px;">
                                        <span><?php _e('کیلوبایت (KB)', 'fondogh-online-menu'); ?></span>
                                        <span class="description"><?php _e('مثلاً: 3072 برای 3 مگابایت. فایل‌های سنگین‌تر از این مقدار قبل از پردازش رد می‌شوند.', 'fondogh-online-menu'); ?></span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php submit_button(__('ذخیره و به‌روزرسانی تنظیمات', 'fondogh-online-menu'), 'primary', 'submit', true, [
                        'style' => 'border-radius: 8px; font-weight: bold; padding: 6px 24px; font-size: 15px;'
                    ]); ?>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var radios = document.querySelectorAll('input[name="rmm_settings[operation_mode]"]');
            var viewOnlyFields = document.querySelectorAll('.rmm-view-only-field');
            radios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    viewOnlyFields.forEach(function(el) {
                        el.style.display = (radio.value === 'view_only') ? '' : 'none';
                    });
                });
            });

            var aiSelect = document.getElementById('rmm-ai-provider-select');
            var geminiRow = document.getElementById('rmm-gemini-key-row');
            if (aiSelect && geminiRow) {
                aiSelect.addEventListener('change', function() {
                    geminiRow.style.display = (this.value === 'gemini') ? '' : 'none';
                });
            }
        });
        </script>
        <?php
    }
}
