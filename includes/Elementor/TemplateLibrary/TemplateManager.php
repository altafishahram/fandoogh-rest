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
                'title'       => __('فست‌فود و پیتزا مدرن (Modern Fast Food)', 'fondogh-online-menu'),
                'description' => __('قالب پیش‌فرض منوی آنلاین با طراحی مدرن، کارت‌های تصویری بزرگ و نوار دسته‌بندی چسبان.', 'fondogh-online-menu'),
                'layout'      => 'modern_grid',
                'preview_img' => RMM_PLUGIN_URL . 'assets/images/preview-fastfood.jpg',
            ],
        ];
    }

    public function add_template_menu() {
        add_submenu_page(
            'rmm-settings',
            __('قالب آماده منو', 'fondogh-online-menu'),
            __('🎨 قالب آماده منو', 'fondogh-online-menu'),
            'manage_options',
            'rmm-templates',
            [$this, 'render_templates_page']
        );
    }

    public function render_templates_page() {
        $catalog = self::get_templates_catalog();
        ?>
        <div class="wrap rmm-admin-inline-dd1107d1">
            <div class="rmm-admin-inline-ac0cae36">
                <div class="rmm-admin-inline-d1a4eb69">
                    <h1 class="rmm-admin-inline-62b2da7e">
                        🎨 <?php _e('کتابخانه قالب‌های آماده و لندینگ پیج منو', 'fondogh-online-menu'); ?>
                    </h1>
                    <p class="rmm-admin-inline-d6b5b805">
                        <?php _e('با یک کلیک، قالب دلخواه را به صورت یک برگه آماده المنتور بسازید و به راحتی با المنتور و المنتور پرو ویرایش کنید.', 'fondogh-online-menu'); ?>
                    </p>
                </div>

                <div class="rmm-admin-inline-5aaad52a">
                    <?php foreach ($catalog as $tpl) : ?>
                        <div class="rmm-admin-inline-345f0ba8 rmm-admin-flex-row">
                            <div class="rmm-admin-inline-b2639e22 rmm-admin-flex-row">
                                <?php if (!empty($tpl['preview_img'])) : ?>
                                    <img src="<?php echo esc_url($tpl['preview_img']); ?>" alt="<?php echo esc_attr($tpl['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else : ?>
                                    🍽️
                                <?php endif; ?>
                            </div>
                            <div class="rmm-admin-inline-1cd77c11 rmm-admin-flex-row">
                                <div>
                                    <h3 class="rmm-admin-inline-051bce49"><?php echo esc_html($tpl['title']); ?></h3>
                                    <p class="rmm-admin-inline-d26adac3"><?php echo esc_html($tpl['description']); ?></p>
                                </div>
                                <button class="button button-primary rmm-import-tpl-btn" data-template-id="<?php echo esc_attr($tpl['id']); ?>" style="width: 100%; border-radius: 6px; font-weight: bold; padding: 6px 0; height: auto;">
                                    🚀 <?php _e('ساخت برگه المنتور از این قالب', 'fondogh-online-menu'); ?>
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
                    this.innerHTML = '⏳ <?php _e('در حال ساخت برگه المنتور...', 'fondogh-online-menu'); ?>';
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
                                self.innerHTML = '✓ <?php _e('برگه ساخته شد! هدایت به المنتور...', 'fondogh-online-menu'); ?>';
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

        // Map template IDs to JSON kit files
        $json_map = [
            'fast_food' => 'fast-food-kit.json',
        ];

        $elementor_data = null;
        if (isset($json_map[$template_id])) {
            $json_path = RMM_PLUGIN_PATH . 'assets/templates-json/' . $json_map[$template_id];
            if (file_exists($json_path)) {
                $raw_json = file_get_contents($json_path);
                $decoded  = json_decode($raw_json, true);
                if (is_array($decoded) && !empty($decoded['content'])) {
                    $elementor_data = $decoded['content'];
                }
            }
        }

        // Fallback default structure if JSON is not present
        if (!$elementor_data) {
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
                                        'cart_btn_text' => __('مشاهده سفارش و پرداخت', 'fondogh-online-menu'),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

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
