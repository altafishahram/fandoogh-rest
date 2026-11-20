<?php
namespace RMM\Elementor\TemplateLibrary;

if (!defined('ABSPATH')) {
    exit;
}

class TemplateManager {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_template_menu']);
        add_action('wp_ajax_rmm_import_starter_template', [$this, 'ajax_import_template']);
    }

    public static function get_templates_catalog() {
        return [
            'fast_food' => [
                'id'          => 'fast_food',
                'title'       => __('فست‌فود و پیتزا مدرن (Modern Fast Food)', 'restaurant-menu-manager'),
                'description' => __('طراحی پرانرژی و جذاب با کارت‌های تصویری بزرگ، برچسب‌های تند و پرفروش، و نوار دسته‌بندی چسبان.', 'restaurant-menu-manager'),
                'layout'      => 'modern_grid',
                'preview_img' => RMM_PLUGIN_URL . 'assets/images/preview-fastfood.jpg',
            ],
            'fine_dining' => [
                'id'          => 'fine_dining',
                'title'       => __('کافه و رستوران لوکس (Fine Dining / Luxury)', 'restaurant-menu-manager'),
                'description' => __('طراحی مینیمال، تایپوگرافی شیک و خطوط ظریف قیمت‌گذاری، ایده‌آل برای کافه‌ها و رستوران‌های مجلل.', 'restaurant-menu-manager'),
                'layout'      => 'fine_dining',
                'preview_img' => RMM_PLUGIN_URL . 'assets/images/preview-finedining.jpg',
            ],
            'app_style' => [
                'id'          => 'app_style',
                'title'       => __('طرح اپلیکیشنی (مشابه اسنپ‌فود / UberEats)', 'restaurant-menu-manager'),
                'description' => __('چیدمان لیستی بهینه برای موبایل با دکمه‌های سریع انتخاب مخلفات و سبد خرید شناور چسبان.', 'restaurant-menu-manager'),
                'layout'      => 'app_list',
                'preview_img' => RMM_PLUGIN_URL . 'assets/images/preview-appstyle.jpg',
            ],
            'traditional' => [
                'id'          => 'traditional',
                'title'       => __('رستوران سنتی و کلاسیک (Oriental Classic)', 'restaurant-menu-manager'),
                'description' => __('مناسب سفره‌خانه‌ها و رستوران‌های سنتی ایرانی با کادرهای گرم و المان‌های اصیل.', 'restaurant-menu-manager'),
                'layout'      => 'compact_grid',
                'preview_img' => RMM_PLUGIN_URL . 'assets/images/preview-traditional.jpg',
            ],
        ];
    }

    public function add_template_menu() {
        add_submenu_page(
            'rmm-settings',
            __('قالب‌های آماده منو', 'restaurant-menu-manager'),
            __('🎨 قالب‌های آماده منو', 'restaurant-menu-manager'),
            'manage_options',
            'rmm-templates',
            [$this, 'render_templates_page']
        );
    }

    public function render_templates_page() {
        $catalog = self::get_templates_catalog();
        ?>
        <div class="wrap" style="max-width: 1000px; margin-top: 20px;">
            <div style="background: #fff; padding: 25px 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
                <div style="margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
                    <h1 style="font-size: 22px; color: #0f172a; margin: 0 0 6px;">
                        🎨 <?php _e('کتابخانه قالب‌های آماده و لندینگ پیج منو', 'restaurant-menu-manager'); ?>
                    </h1>
                    <p style="margin: 0; color: #64748b; font-size: 14px;">
                        <?php _e('با یک کلیک، قالب دلخواه را به صورت یک برگه آماده المنتور بسازید و به راحتی با المنتور و المنتور پرو ویرایش کنید.', 'restaurant-menu-manager'); ?>
                    </p>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                    <?php foreach ($catalog as $tpl) : ?>
                        <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; background: #fafafa;">
                            <div style="background: #0f172a; height: 140px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 38px;">
                                🍽️
                            </div>
                            <div style="padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <h3 style="margin: 0 0 6px; font-size: 16px; color: #0f172a;"><?php echo esc_html($tpl['title']); ?></h3>
                                    <p style="font-size: 13px; color: #64748b; margin-bottom: 14px;"><?php echo esc_html($tpl['description']); ?></p>
                                </div>
                                <button class="button button-primary rmm-import-tpl-btn" data-template-id="<?php echo esc_attr($tpl['id']); ?>" style="width: 100%; border-radius: 6px; font-weight: bold; padding: 6px 0; height: auto;">
                                    🚀 <?php _e('ساخت برگه المنتور از این قالب', 'restaurant-menu-manager'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var buttons = document.querySelectorAll('.rmm-import-tpl-btn');
            buttons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var tplId = this.getAttribute('data-template-id');
                    var origText = this.innerHTML;
                    this.innerHTML = '⏳ <?php _e('در حال ساخت برگه المنتور...', 'restaurant-menu-manager'); ?>';
                    this.disabled = true;

                    var self = this;
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'rmm_import_starter_template',
                            template_id: tplId,
                            security: '<?php echo wp_create_nonce('rmm_import_tpl_nonce'); ?>'
                        },
                        success: function(res) {
                            if (res && res.success && res.data.edit_url) {
                                self.innerHTML = '✓ <?php _e('برگه ساخته شد! هدایت به المنتور...', 'restaurant-menu-manager'); ?>';
                                window.location.href = res.data.edit_url;
                            } else {
                                alert('خطا در ایجاد برگه!');
                                self.innerHTML = origText;
                                self.disabled = false;
                            }
                        },
                        error: function() {
                            alert('خطا در ارتباط با سرور.');
                            self.innerHTML = origText;
                            self.disabled = false;
                        }
                    });
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler to build an Elementor Page pre-configured with TastyMenu widgets
     */
    public function ajax_import_template() {
        check_ajax_referer('rmm_import_tpl_nonce', 'security');

        if (!current_user_can('edit_pages')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $template_id = sanitize_key($_POST['template_id'] ?? 'fast_food');
        $catalog = self::get_templates_catalog();
        $tpl = $catalog[$template_id] ?? $catalog['fast_food'];

        // Build Elementor structure
        $elementor_data = [
            [
                'id'       => wp_generate_uuid4(),
                'elType'   => 'section',
                'elements' => [
                    [
                        'id'       => wp_generate_uuid4(),
                        'elType'   => 'column',
                        'elements' => [
                            // 1. Header & Branch Widget
                            [
                                'id'         => wp_generate_uuid4(),
                                'elType'     => 'widget',
                                'widgetType' => 'rmm_branch_selector',
                                'settings'   => [
                                    'show_status' => 'yes',
                                    'show_table'  => 'yes',
                                    'show_lang'   => 'yes',
                                ],
                            ],
                            // 2. Category Nav Bar
                            [
                                'id'         => wp_generate_uuid4(),
                                'elType'     => 'widget',
                                'widgetType' => 'rmm_category_bar',
                                'settings'   => [
                                    'is_sticky' => 'yes',
                                ],
                            ],
                            // 3. Food Menu Grid
                            [
                                'id'         => wp_generate_uuid4(),
                                'elType'     => 'widget',
                                'widgetType' => 'rmm_food_menu_grid',
                                'settings'   => [
                                    'layout_style'  => $tpl['layout'],
                                    'columns'       => '3',
                                    'show_search'   => 'yes',
                                    'show_badges'   => 'yes',
                                    'show_calories' => 'yes',
                                ],
                            ],
                            // 4. Floating Mini Cart
                            [
                                'id'         => wp_generate_uuid4(),
                                'elType'     => 'widget',
                                'widgetType' => 'rmm_floating_cart',
                                'settings'   => [
                                    'cart_btn_text' => __('مشاهده سفارش و پرداخت', 'restaurant-menu-manager'),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Create page
        $page_id = wp_insert_post([
            'post_title'   => $tpl['title'],
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'meta_input'   => [
                '_elementor_edit_mode'     => 'builder',
                '_elementor_template_type' => 'wp-page',
                '_elementor_version'       => '3.0.0',
                '_elementor_data'          => wp_slash(json_encode($elementor_data)),
                '_wp_page_template'        => 'elementor_header_footer',
            ],
        ]);

        if (is_wp_error($page_id)) {
            wp_send_json_error(['message' => $page_id->get_error_message()]);
        }

        $edit_url = admin_url('post.php?post=' . $page_id . '&action=elementor');

        wp_send_json_success([
            'page_id'  => $page_id,
            'edit_url' => $edit_url,
        ]);
    }
}
