<?php
namespace RMM\Menu;

if (!defined('ABSPATH')) {
    exit;
}

class FoodMeta {

    public function __construct() {
        add_action('init', [$this, 'register_food_taxonomies']);
        add_action('add_meta_boxes', [$this, 'register_food_meta_box']);
        add_action('woocommerce_process_product_meta', [$this, 'save_food_meta']);
    }

    /**
     * Register Dietary Badges taxonomy
     */
    public function register_food_taxonomies() {
        $labels = [
            'name'                       => __('نشان‌های غذایی (Dietary Badges)', 'restaurant-menu-manager'),
            'singular_name'              => __('نشان غذایی', 'restaurant-menu-manager'),
            'search_items'               => __('جستجوی نشان‌ها', 'restaurant-menu-manager'),
            'all_items'                  => __('همه نشان‌ها', 'restaurant-menu-manager'),
            'edit_item'                  => __('ویرایش نشان', 'restaurant-menu-manager'),
            'update_item'                => __('به‌روزرسانی نشان', 'restaurant-menu-manager'),
            'add_new_item'               => __('افزودن نشان جدید (مثل تند، گیاهی، ویژه)', 'restaurant-menu-manager'),
            'new_item_name'              => __('نام نشان تازه', 'restaurant-menu-manager'),
            'menu_name'                  => __('نشان‌های غذایی', 'restaurant-menu-manager'),
        ];

        register_taxonomy('rmm_dietary_badge', ['product'], [
            'hierarchical'          => false,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_in_menu'          => 'rmm-settings',
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => ['slug' => 'dietary-badge'],
            'show_in_rest'          => true,
            'public'                => true,
        ]);
    }

    public function register_food_meta_box() {
        add_meta_box(
            'rmm_food_attributes_box',
            __('🍱 مشخصات رستورانی و ارزش غذایی (TastyMenu)', 'restaurant-menu-manager'),
            [$this, 'render_meta_box'],
            'product',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('rmm_save_food_meta_action', 'rmm_food_meta_nonce');

        $calories           = get_post_meta($post->ID, '_rmm_calories', true);
        $prep_time          = get_post_meta($post->ID, '_rmm_prep_time', true);
        $ingredients        = get_post_meta($post->ID, '_rmm_ingredients', true);
        $title_en           = get_post_meta($post->ID, '_rmm_title_en', true);
        $desc_en            = get_post_meta($post->ID, '_rmm_desc_en', true);
        $badge_icon         = get_post_meta($post->ID, '_rmm_badge_icon', true) ?: 'none';
        $badge_text         = get_post_meta($post->ID, '_rmm_badge_text', true);
        $badge_custom_icon  = get_post_meta($post->ID, '_rmm_badge_custom_icon', true);
        $badge_image        = get_post_meta($post->ID, '_rmm_badge_image', true);
        ?>
        <div class="rmm-food-metabox-wrap" style="padding: 10px 0;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('عنوان به زبان دوم (انگلیسی):', 'restaurant-menu-manager'); ?>
                    </label>
                    <input type="text" name="_rmm_title_en" value="<?php echo esc_attr($title_en); ?>" style="width: 100%;" placeholder="e.g. Double Beef Cheeseburger">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('زمان تقریبی آماده‌سازی (دقیقه):', 'restaurant-menu-manager'); ?>
                    </label>
                    <input type="number" name="_rmm_prep_time" value="<?php echo esc_attr($prep_time); ?>" style="width: 100%;" placeholder="مثلاً: 20">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('کالری و ارزش غذایی (Kcal):', 'restaurant-menu-manager'); ?>
                    </label>
                    <input type="text" name="_rmm_calories" value="<?php echo esc_attr($calories); ?>" style="width: 100%;" placeholder="مثلاً: 480 Kcal">
                </div>
                <div>
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                        <?php _e('نشان ویژه یا برجسته:', 'restaurant-menu-manager'); ?>
                    </label>
                    <select name="_rmm_badge_icon" style="width: 100%;">
                        <option value="none" <?php selected($badge_icon, 'none'); ?>><?php _e('بدون نشان', 'restaurant-menu-manager'); ?></option>
                        <option value="chef" <?php selected($badge_icon, 'chef'); ?>><?php _e('⭐ پیشنهاد ویژه سرآشپز', 'restaurant-menu-manager'); ?></option>
                        <option value="spicy" <?php selected($badge_icon, 'spicy'); ?>><?php _e('🌶️ تند و اسپایسی', 'restaurant-menu-manager'); ?></option>
                        <option value="vegan" <?php selected($badge_icon, 'vegan'); ?>><?php _e('🌱 گیاهی (گیاه‌خواران)', 'restaurant-menu-manager'); ?></option>
                        <option value="popular" <?php selected($badge_icon, 'popular'); ?>><?php _e('🔥 پرفروش‌ترین', 'restaurant-menu-manager'); ?></option>
                        <option value="new" <?php selected($badge_icon, 'new'); ?>><?php _e('✨ غذای جدید', 'restaurant-menu-manager'); ?></option>
                        <option value="custom" <?php selected($badge_icon, 'custom'); ?>><?php _e('🎨 نشان اختصاصی و دلخواه', 'restaurant-menu-manager'); ?></option>
                    </select>
                </div>
            </div>

            <!-- تنظیمات شخصی‌سازی نشان ویژه -->
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 15px;">
                <span style="font-weight: 700; display: block; margin-bottom: 8px; color: #334155;">✨ <?php _e('شخصی‌سازی عنوان، آیکون و تصویر نشان ویژه غذا:', 'restaurant-menu-manager'); ?></span>
                <div style="display: grid; grid-template-columns: 1.2fr 1fr 1.5fr; gap: 15px;">
                    <div>
                        <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px;">
                            <?php _e('متن / عنوان دلخواه نشان:', 'restaurant-menu-manager'); ?>
                        </label>
                        <input type="text" name="_rmm_badge_text" value="<?php echo esc_attr($badge_text); ?>" style="width: 100%;" placeholder="مثلاً: ویژه سرآشپز، تخفیف روز">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px;">
                            <?php _e('آیکون یا ایموجی نشان:', 'restaurant-menu-manager'); ?>
                        </label>
                        <input type="text" name="_rmm_badge_custom_icon" value="<?php echo esc_attr($badge_custom_icon); ?>" style="width: 100%;" placeholder="مثلاً: 👑، 💎، 🌶️، ⭐">
                    </div>
                    <div>
                        <label style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 4px;">
                            <?php _e('آدرس تصویر یا لوگوی نشان:', 'restaurant-menu-manager'); ?>
                        </label>
                        <input type="text" name="_rmm_badge_image" value="<?php echo esc_attr($badge_image); ?>" style="width: 100%;" placeholder="https://... یا آدرس عکس">
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                    <?php _e('ترکیبات و مواد تشکیل‌دهنده (Ingredients):', 'restaurant-menu-manager'); ?>
                </label>
                <input type="text" name="_rmm_ingredients" value="<?php echo esc_attr($ingredients); ?>" style="width: 100%;" placeholder="مثلاً: راسته گوساله، پنیر چدار، سس باربیکیو، قارچ، گوجه...">
            </div>

            <div>
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">
                    <?php _e('توضیحات کوتاه به زبان دوم (انگلیسی):', 'restaurant-menu-manager'); ?>
                </label>
                <textarea name="_rmm_desc_en" rows="2" style="width: 100%;" placeholder="Brief ingredients or English description..."><?php echo esc_textarea($desc_en); ?></textarea>
            </div>
        </div>
        <?php
    }

    public function save_food_meta($post_id) {
        if (!isset($_POST['rmm_food_meta_nonce']) || !wp_verify_nonce($_POST['rmm_food_meta_nonce'], 'rmm_save_food_meta_action')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_product', $post_id)) {
            return;
        }

        if (isset($_POST['_rmm_title_en'])) {
            update_post_meta($post_id, '_rmm_title_en', sanitize_text_field($_POST['_rmm_title_en']));
        }
        if (isset($_POST['_rmm_desc_en'])) {
            update_post_meta($post_id, '_rmm_desc_en', sanitize_textarea_field($_POST['_rmm_desc_en']));
        }
        if (isset($_POST['_rmm_calories'])) {
            update_post_meta($post_id, '_rmm_calories', sanitize_text_field($_POST['_rmm_calories']));
        }
        if (isset($_POST['_rmm_prep_time'])) {
            update_post_meta($post_id, '_rmm_prep_time', absint($_POST['_rmm_prep_time']));
        }
        if (isset($_POST['_rmm_ingredients'])) {
            update_post_meta($post_id, '_rmm_ingredients', sanitize_text_field($_POST['_rmm_ingredients']));
        }
        if (isset($_POST['_rmm_badge_icon'])) {
            update_post_meta($post_id, '_rmm_badge_icon', sanitize_text_field($_POST['_rmm_badge_icon']));
        }
        if (isset($_POST['_rmm_badge_text'])) {
            update_post_meta($post_id, '_rmm_badge_text', sanitize_text_field($_POST['_rmm_badge_text']));
        }
        if (isset($_POST['_rmm_badge_custom_icon'])) {
            update_post_meta($post_id, '_rmm_badge_custom_icon', sanitize_text_field($_POST['_rmm_badge_custom_icon']));
        }
        if (isset($_POST['_rmm_badge_image'])) {
            update_post_meta($post_id, '_rmm_badge_image', esc_url_raw($_POST['_rmm_badge_image']));
        }
    }

    /**
     * Helper to get food badge details (Multilingual & Custom)
     */
    public static function get_badge_info($product_id, $lang = null) {
        $badge              = get_post_meta($product_id, '_rmm_badge_icon', true) ?: 'none';
        $badge_text         = get_post_meta($product_id, '_rmm_badge_text', true);
        $badge_custom_icon  = get_post_meta($product_id, '_rmm_badge_custom_icon', true);
        $badge_image        = get_post_meta($product_id, '_rmm_badge_image', true);

        if (is_numeric($badge_image) && $badge_image > 0) {
            $badge_image = wp_get_attachment_image_url($badge_image, 'thumbnail') ?: '';
        }

        if ($badge === 'none' && empty($badge_text) && empty($badge_image)) {
            return null;
        }

        if (!$lang) {
            $lang = class_exists('RMM\\I18n\\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa';
        }

        $badge_labels = [
            'chef' => [
                'fa' => 'پیشنهاد سرآشپز',
                'en' => "Chef's Special",
                'ar' => 'اقتراح الشيف',
                'tr' => 'Şefin Önerisi',
                'fr' => 'Spécialité du Chef',
                'de' => 'Chef-Empfehlung',
                'ru' => 'От шеف-повара',
                'zh' => '主厨特选',
            ],
            'spicy' => [
                'fa' => 'تند و اسپایسی',
                'en' => 'Spicy',
                'ar' => 'حار',
                'tr' => 'Acılı',
                'fr' => 'Épicé',
                'de' => 'Scharf',
                'ru' => 'Острый',
                'zh' => '辣',
            ],
            'vegan' => [
                'fa' => 'گیاهی',
                'en' => 'Vegan',
                'ar' => 'نباتي',
                'tr' => 'Vejetaryen',
                'fr' => 'Végétarien',
                'de' => 'Vegetarisch',
                'ru' => 'Вегетарианское',
                'zh' => '素食',
            ],
            'popular' => [
                'fa' => 'پرفروش',
                'en' => 'Best Seller',
                'ar' => 'الأكثر طلباً',
                'tr' => 'Popüler',
                'fr' => 'Populaire',
                'de' => 'Beliebt',
                'ru' => 'Хит продаж',
                'zh' => '热销',
            ],
            'new' => [
                'fa' => 'جدید',
                'en' => 'New',
                'ar' => 'جديد',
                'tr' => 'Yeni',
                'fr' => 'Nouveau',
                'de' => 'Neu',
                'ru' => 'Новинка',
                'zh' => '新品',
            ],
        ];

        $icons = [
            'chef'    => '⭐',
            'spicy'   => '🌶️',
            'vegan'   => '🌱',
            'popular' => '🔥',
            'new'     => '✨',
        ];

        $classes = [
            'chef'    => 'rmm-badge-chef',
            'spicy'   => 'rmm-badge-spicy',
            'vegan'   => 'rmm-badge-vegan',
            'popular' => 'rmm-badge-popular',
            'new'     => 'rmm-badge-new',
            'custom'  => 'rmm-badge-custom',
        ];

        // Determine final label
        if (!empty($badge_text)) {
            $label = $badge_text;
        } elseif (isset($badge_labels[$badge])) {
            $label = $badge_labels[$badge][$lang] ?? ($badge_labels[$badge]['fa'] ?? $badge);
        } else {
            $label = ($badge !== 'none' && $badge !== 'custom') ? $badge : '';
        }

        // Determine final icon
        if (!empty($badge_custom_icon)) {
            $icon = $badge_custom_icon;
        } elseif (isset($icons[$badge])) {
            $icon = $icons[$badge];
        } else {
            $icon = !empty($badge_image) ? '' : '⭐';
        }

        $class = $classes[$badge] ?? 'rmm-badge-custom';

        return [
            'type'  => $badge,
            'label' => $label,
            'icon'  => $icon,
            'image' => $badge_image ?: '',
            'class' => $class,
        ];
    }
}
