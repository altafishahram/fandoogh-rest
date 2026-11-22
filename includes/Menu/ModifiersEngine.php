<?php
namespace RMM\Menu;

if (!defined('ABSPATH')) {
    exit;
}

class ModifiersEngine {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_modifiers_metabox']);
        add_action('woocommerce_process_product_meta', [$this, 'save_modifiers_meta']);

        // WooCommerce Cart Integration
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_modifiers_to_cart_item'], 10, 3);
        add_action('woocommerce_before_calculate_totals', [$this, 'calculate_modifier_totals'], 25, 1);
        add_filter('woocommerce_get_item_data', [$this, 'render_modifiers_in_cart'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_modifiers_to_order_line'], 10, 4);

        // AJAX for Front-end Modifier Modal
        add_action('wp_ajax_rmm_get_product_modifiers', [$this, 'ajax_get_product_modifiers']);
        add_action('wp_ajax_nopriv_rmm_get_product_modifiers', [$this, 'ajax_get_product_modifiers']);

        // AJAX for Custom Elementor Template Food Modal
        add_action('wp_ajax_rmm_render_food_elementor_template', [$this, 'ajax_render_food_elementor_template']);
        add_action('wp_ajax_nopriv_rmm_render_food_elementor_template', [$this, 'ajax_render_food_elementor_template']);

        // Transient cache invalidation
        add_action('save_post_product', [$this, 'clear_product_modal_cache']);
        add_action('woocommerce_update_product', [$this, 'clear_product_modal_cache']);
    }

    public function add_modifiers_metabox() {
        add_meta_box(
            'rmm_product_modifiers_box',
            __('🍕 سایزبندی و مخلفات انتخابی (Modifiers & Portions)', 'fondogh-online-menu'),
            [$this, 'render_metabox'],
            'product',
            'normal',
            'high'
        );
    }

    public function render_metabox($post) {
        wp_nonce_field('rmm_modifiers_save_action', 'rmm_modifiers_nonce');

        $portions = get_post_meta($post->ID, '_rmm_portions', true) ?: [];
        $modifier_groups = get_post_meta($post->ID, '_rmm_modifier_groups', true) ?: [];
        ?>
        <div class="rmm-modifiers-admin-wrap" style="padding: 10px 0;">
            <!-- Portions / Sizes Section -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 8px; color: #0f172a; font-size: 15px;">
                    📏 <?php _e('سایزبندی / پرس غذا (اختیاری)', 'fondogh-online-menu'); ?>
                </h4>
                <p class="description" style="margin-bottom: 12px;">
                    <?php _e('در صورت تعریف سایزها (مثلاً: یک‌نفره، دونفره، خانواده)، مشتری ابتدا سایز را انتخاب می‌کند و قیمت آن جایگزین قیمت پایه می‌شود.', 'fondogh-online-menu'); ?>
                </p>

                <div id="rmm-portions-list">
                    <?php if (!empty($portions)) : ?>
                        <?php foreach ($portions as $i => $portion) : ?>
                            <div class="rmm-portion-row" style="display: flex; gap: 10px; margin-bottom: 8px; align-items: center;">
                                <input type="text" name="_rmm_portions[<?php echo $i; ?>][name]" value="<?php echo esc_attr($portion['name'] ?? ''); ?>" placeholder="<?php _e('نام سایز (مثلاً: متوسط)', 'fondogh-online-menu'); ?>" style="flex: 2;">
                                <input type="number" step="any" name="_rmm_portions[<?php echo $i; ?>][price]" value="<?php echo esc_attr($portion['price'] ?? ''); ?>" placeholder="<?php _e('قیمت این سایز (تومان)', 'fondogh-online-menu'); ?>" style="flex: 1;">
                                <button type="button" class="button rmm-remove-portion-btn" style="color: #ef4444;">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="button" id="rmm-add-portion-btn" class="button button-secondary" style="margin-top: 5px;">
                    + <?php _e('افزودن سایز جدید', 'fondogh-online-menu'); ?>
                </button>
            </div>

            <!-- Addon Groups Section -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px;">
                <h4 style="margin: 0 0 8px; color: #0f172a; font-size: 15px;">
                    🥗 <?php _e('گروه‌های مخلفات، تاپینگ‌ها و گزینه‌های انتخابی', 'fondogh-online-menu'); ?>
                </h4>
                <p class="description" style="margin-bottom: 12px;">
                    <?php _e('مثلاً: گروه «انتخاب سس» (تک‌انتخابی)، یا «مخلفات اضافه» (چندانتخابی با قیمت مازاد).', 'fondogh-online-menu'); ?>
                </p>

                <div id="rmm-modifier-groups-list">
                    <?php if (!empty($modifier_groups)) : ?>
                        <?php foreach ($modifier_groups as $g_idx => $group) : ?>
                            <div class="rmm-group-card" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                    <input type="text" name="_rmm_modifier_groups[<?php echo $g_idx; ?>][title]" value="<?php echo esc_attr($group['title'] ?? ''); ?>" placeholder="<?php _e('عنوان گروه (مثلاً: سس اضافه، نان مخصوص)', 'fondogh-online-menu'); ?>" style="flex: 2; font-weight: bold;">
                                    <select name="_rmm_modifier_groups[<?php echo $g_idx; ?>][type]" style="flex: 1;">
                                        <option value="checkbox" <?php selected($group['type'] ?? 'checkbox', 'checkbox'); ?>><?php _e('چند انتخابی (Checkbox)', 'fondogh-online-menu'); ?></option>
                                        <option value="radio" <?php selected($group['type'] ?? '', 'radio'); ?>><?php _e('تک انتخابی اجباری (Radio)', 'fondogh-online-menu'); ?></option>
                                    </select>
                                    <button type="button" class="button rmm-remove-group-btn" style="color: #ef4444; border-color: #fca5a5;">&times; <?php _e('حذف گروه', 'fondogh-online-menu'); ?></button>
                                </div>

                                <div class="rmm-group-options" style="padding-right: 15px; border-right: 2px solid #e2e8f0;">
                                    <?php if (!empty($group['options'])) : ?>
                                        <?php foreach ($group['options'] as $o_idx => $opt) : ?>
                                            <div class="rmm-opt-row" style="display: flex; gap: 10px; margin-bottom: 6px; align-items: center;">
                                                <input type="text" name="_rmm_modifier_groups[<?php echo $g_idx; ?>][options][<?php echo $o_idx; ?>][name]" value="<?php echo esc_attr($opt['name'] ?? ''); ?>" placeholder="<?php _e('نام گزینه (مثلاً: سس سیر)', 'fondogh-online-menu'); ?>" style="flex: 2;">
                                                <input type="number" step="any" name="_rmm_modifier_groups[<?php echo $g_idx; ?>][options][<?php echo $o_idx; ?>][price]" value="<?php echo esc_attr($opt['price'] ?? '0'); ?>" placeholder="<?php _e('قیمت مازاد (۰ برای رایگان)', 'fondogh-online-menu'); ?>" style="flex: 1;">
                                                <button type="button" class="button rmm-remove-opt-btn" style="color: #ef4444;">&times;</button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button rmm-add-opt-btn" data-group="<?php echo $g_idx; ?>" style="margin-top: 6px; font-size: 12px;">+ <?php _e('افزودن گزینه به این گروه', 'fondogh-online-menu'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="button" id="rmm-add-group-btn" class="button button-secondary" style="margin-top: 5px;">
                    + <?php _e('ایجاد گروه مخلفات جدید', 'fondogh-online-menu'); ?>
                </button>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Portions handling
            var portionsList = document.getElementById('rmm-portions-list');
            var addPortionBtn = document.getElementById('rmm-add-portion-btn');
            if (addPortionBtn && portionsList) {
                addPortionBtn.addEventListener('click', function() {
                    var idx = Date.now();
                    var row = document.createElement('div');
                    row.className = 'rmm-portion-row';
                    row.style.cssText = 'display: flex; gap: 10px; margin-bottom: 8px; align-items: center;';
                    row.innerHTML = '<input type="text" name="_rmm_portions[' + idx + '][name]" placeholder="<?php _e('نام سایز', 'fondogh-online-menu'); ?>" style="flex: 2;">' +
                                    '<input type="number" step="any" name="_rmm_portions[' + idx + '][price]" placeholder="<?php _e('قیمت', 'fondogh-online-menu'); ?>" style="flex: 1;">' +
                                    '<button type="button" class="button rmm-remove-portion-btn" style="color: #ef4444;">&times;</button>';
                    portionsList.appendChild(row);
                });
            }

            // Modifier Groups handling
            var groupsList = document.getElementById('rmm-modifier-groups-list');
            var addGroupBtn = document.getElementById('rmm-add-group-btn');
            if (addGroupBtn && groupsList) {
                addGroupBtn.addEventListener('click', function() {
                    var gIdx = Date.now();
                    var card = document.createElement('div');
                    card.className = 'rmm-group-card';
                    card.style.cssText = 'background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; margin-bottom: 12px;';
                    card.innerHTML = '<div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">' +
                                     '<input type="text" name="_rmm_modifier_groups[' + gIdx + '][title]" placeholder="<?php _e('عنوان گروه', 'fondogh-online-menu'); ?>" style="flex: 2; font-weight: bold;">' +
                                     '<select name="_rmm_modifier_groups[' + gIdx + '][type]" style="flex: 1;"><option value="checkbox"><?php _e('چند انتخابی', 'fondogh-online-menu'); ?></option><option value="radio"><?php _e('تک انتخابی اجباری', 'fondogh-online-menu'); ?></option></select>' +
                                     '<button type="button" class="button rmm-remove-group-btn" style="color: #ef4444;">&times; <?php _e('حذف', 'fondogh-online-menu'); ?></button>' +
                                     '</div><div class="rmm-group-options" style="padding-right: 15px; border-right: 2px solid #e2e8f0;"></div>' +
                                     '<button type="button" class="button rmm-add-opt-btn" data-group="' + gIdx + '" style="margin-top: 6px; font-size: 12px;">+ <?php _e('افزودن گزینه', 'fondogh-online-menu'); ?></button>';
                    groupsList.appendChild(card);
                });
            }

            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('rmm-remove-portion-btn')) {
                    e.target.closest('.rmm-portion-row').remove();
                }
                if (e.target && e.target.classList.contains('rmm-remove-group-btn')) {
                    e.target.closest('.rmm-group-card').remove();
                }
                if (e.target && e.target.classList.contains('rmm-remove-opt-btn')) {
                    e.target.closest('.rmm-opt-row').remove();
                }
                if (e.target && e.target.classList.contains('rmm-add-opt-btn')) {
                    var gIdx = e.target.getAttribute('data-group');
                    var container = e.target.previousElementSibling;
                    var oIdx = Date.now();
                    var optRow = document.createElement('div');
                    optRow.className = 'rmm-opt-row';
                    optRow.style.cssText = 'display: flex; gap: 10px; margin-bottom: 6px; align-items: center;';
                    optRow.innerHTML = '<input type="text" name="_rmm_modifier_groups[' + gIdx + '][options][' + oIdx + '][name]" placeholder="<?php _e('نام گزینه', 'fondogh-online-menu'); ?>" style="flex: 2;">' +
                                       '<input type="number" step="any" name="_rmm_modifier_groups[' + gIdx + '][options][' + oIdx + '][price]" value="0" placeholder="<?php _e('قیمت مازاد', 'fondogh-online-menu'); ?>" style="flex: 1;">' +
                                       '<button type="button" class="button rmm-remove-opt-btn" style="color: #ef4444;">&times;</button>';
                    container.appendChild(optRow);
                }
            });
        });
        </script>
        <?php
    }

    public function save_modifiers_meta($post_id) {
        if (!isset($_POST['rmm_modifiers_nonce']) || !wp_verify_nonce($_POST['rmm_modifiers_nonce'], 'rmm_modifiers_save_action')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_product', $post_id)) {
            return;
        }

        // Clean portions
        $clean_portions = [];
        if (!empty($_POST['_rmm_portions']) && is_array($_POST['_rmm_portions'])) {
            foreach ($_POST['_rmm_portions'] as $portion) {
                if (!empty($portion['name'])) {
                    $clean_portions[] = [
                        'name'  => sanitize_text_field($portion['name']),
                        'price' => floatval($portion['price'] ?? 0),
                    ];
                }
            }
        }
        update_post_meta($post_id, '_rmm_portions', $clean_portions);

        // Clean modifier groups
        $clean_groups = [];
        if (!empty($_POST['_rmm_modifier_groups']) && is_array($_POST['_rmm_modifier_groups'])) {
            foreach ($_POST['_rmm_modifier_groups'] as $group) {
                if (!empty($group['title'])) {
                    $options = [];
                    if (!empty($group['options']) && is_array($group['options'])) {
                        foreach ($group['options'] as $opt) {
                            if (!empty($opt['name'])) {
                                $options[] = [
                                    'name'  => sanitize_text_field($opt['name']),
                                    'price' => floatval($opt['price'] ?? 0),
                                ];
                            }
                        }
                    }
                    $clean_groups[] = [
                        'title'   => sanitize_text_field($group['title']),
                        'type'    => in_array($group['type'] ?? '', ['radio', 'checkbox']) ? $group['type'] : 'checkbox',
                        'options' => $options,
                    ];
                }
            }
        }
        update_post_meta($post_id, '_rmm_modifier_groups', $clean_groups);
    }

    /**
     * Add selected modifiers to WooCommerce cart item data
     */
    public function add_modifiers_to_cart_item($cart_item_data, $product_id, $variation_id) {
        if (!empty($_POST['rmm_portion'])) {
            $portion_index = absint($_POST['rmm_portion']);
            $portions = get_post_meta($product_id, '_rmm_portions', true) ?: [];
            if (isset($portions[$portion_index])) {
                $cart_item_data['_rmm_chosen_portion'] = $portions[$portion_index];
            }
        }

        if (!empty($_POST['rmm_addons']) && is_array($_POST['rmm_addons'])) {
            $chosen_addons = [];
            $extra_addons_price = 0;

            foreach ($_POST['rmm_addons'] as $addon_json) {
                $decoded = json_decode(stripslashes($addon_json), true);
                if ($decoded && !empty($decoded['name'])) {
                    $price = floatval($decoded['price'] ?? 0);
                    $chosen_addons[] = [
                        'name'  => sanitize_text_field($decoded['name']),
                        'price' => $price,
                        'group' => sanitize_text_field($decoded['group'] ?? '')
                    ];
                    $extra_addons_price += $price;
                }
            }

            if (!empty($chosen_addons)) {
                $cart_item_data['_rmm_chosen_addons'] = $chosen_addons;
                $cart_item_data['_rmm_addons_extra_price'] = $extra_addons_price;
            }
        }

        // If food has special order instructions / notes
        if (!empty($_POST['rmm_order_notes'])) {
            $cart_item_data['_rmm_order_notes'] = sanitize_text_field($_POST['rmm_order_notes']);
        }

        // Unique ID so distinct modifiers create separate cart rows
        if (!empty($cart_item_data['_rmm_chosen_portion']) || !empty($cart_item_data['_rmm_chosen_addons']) || !empty($cart_item_data['_rmm_order_notes'])) {
            $cart_item_data['unique_key'] = md5(microtime() . rand());
        }

        return $cart_item_data;
    }

    /**
     * Calculate item price in cart based on Portion + Addons
     */
    public function calculate_modifier_totals($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            $base_price = floatval($cart_item['data']->get_price());

            // Check if portion overrides base price
            if (!empty($cart_item['_rmm_chosen_portion']) && isset($cart_item['_rmm_chosen_portion']['price'])) {
                $base_price = floatval($cart_item['_rmm_chosen_portion']['price']);
            }

            // Add extra addons price
            if (!empty($cart_item['_rmm_addons_extra_price'])) {
                $base_price += floatval($cart_item['_rmm_addons_extra_price']);
            }

            $cart_item['data']->set_price($base_price);
        }
    }

    /**
     * Render modifiers in cart and checkout item meta display
     */
    public function render_modifiers_in_cart($item_data, $cart_item) {
        if (!empty($cart_item['_rmm_chosen_portion'])) {
            $item_data[] = [
                'key'   => __('سایز / پرس', 'fondogh-online-menu'),
                'value' => esc_html($cart_item['_rmm_chosen_portion']['name']),
            ];
        }

        if (!empty($cart_item['_rmm_chosen_addons'])) {
            $addons_list = [];
            foreach ($cart_item['_rmm_chosen_addons'] as $addon) {
                $price_label = ($addon['price'] > 0) ? ' (+' . wc_price($addon['price']) . ')' : '';
                $addons_list[] = esc_html($addon['name']) . $price_label;
            }
            $item_data[] = [
                'key'   => __('مخلفات و افزودنی‌ها', 'fondogh-online-menu'),
                'value' => implode('، ', $addons_list),
            ];
        }

        if (!empty($cart_item['_rmm_order_notes'])) {
            $item_data[] = [
                'key'   => __('یادداشت برای آشپزخانه', 'fondogh-online-menu'),
                'value' => esc_html($cart_item['_rmm_order_notes']),
            ];
        }

        return $item_data;
    }

    /**
     * Save modifier details to order line item
     */
    public function save_modifiers_to_order_line($item, $cart_item_key, $values, $order) {
        if (!empty($values['_rmm_chosen_portion'])) {
            $item->add_meta_data(__('سایز / پرس', 'fondogh-online-menu'), $values['_rmm_chosen_portion']['name'], true);
        }

        if (!empty($values['_rmm_chosen_addons'])) {
            $addons_str = [];
            foreach ($values['_rmm_chosen_addons'] as $addon) {
                $p = ($addon['price'] > 0) ? ' (+' . wc_price($addon['price']) . ')' : '';
                $addons_str[] = $addon['name'] . $p;
            }
            $item->add_meta_data(__('مخلفات', 'fondogh-online-menu'), implode('، ', $addons_str), true);
        }

        if (!empty($values['_rmm_order_notes'])) {
            $item->add_meta_data(__('یادداشت آشپزخانه', 'fondogh-online-menu'), $values['_rmm_order_notes'], true);
        }
    }

    /**
     * AJAX endpoint to return JSON for the Front-end Food Modal & Modifiers
     */
    public function ajax_get_product_modifiers() {
        if (!empty($_REQUEST['security'])) {
            check_ajax_referer('rmm_frontend_nonce', 'security', false);
        }

        $product_id = absint($_GET['product_id'] ?? 0);
        $active_branch = \RMM\Branches\BranchContext::get_active_branch_id();
        $active_lang = !empty($_GET['lang']) ? sanitize_key($_GET['lang']) : (class_exists('RMM\\I18n\\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa');

        $cache_key = 'rmm_modal_' . $product_id . '_' . $active_branch . '_' . $active_lang;
        $cached_data = get_transient($cache_key);
        if ($cached_data && is_array($cached_data)) {
            wp_send_json_success($cached_data);
            return;
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(['message' => __('غذا یافت نشد.', 'fondogh-online-menu')]);
        }

        $active_branch = \RMM\Branches\BranchContext::get_active_branch_id();

        // Check if food is in stock
        $in_stock = $product->is_in_stock();
        if ($active_branch && class_exists('RMM\\Branches\\InventoryRepository')) {
            $inv_item = \RMM\Branches\InventoryRepository::get_item($product_id, $active_branch);
            if ($inv_item && (!$inv_item->is_available || $inv_item->stock_status === 'outofstock')) {
                $in_stock = false;
            }
        }
        if (!$in_stock) {
            wp_send_json_error(['message' => __('این غذا در حال حاضر ناموجود است.', 'fondogh-online-menu')]);
        }

        $portions        = get_post_meta($product_id, '_rmm_portions', true) ?: [];
        $modifier_groups = get_post_meta($product_id, '_rmm_modifier_groups', true) ?: [];
        $base_price      = $product->get_price();
        $regular_price   = $product->get_regular_price();
        $sale_price      = $product->get_sale_price();
        $is_on_sale      = $product->is_on_sale() && !empty($regular_price) && floatval($regular_price) > floatval($base_price);

        $calories    = get_post_meta($product_id, '_rmm_calories', true);
        $prep_time   = absint(get_post_meta($product_id, '_rmm_prep_time', true));
        $ingredients = get_post_meta($product_id, '_rmm_ingredients', true);
        $desc        = $product->get_short_description() ?: $product->get_description();

        // Resolve active language
        $active_lang = !empty($_GET['lang']) ? sanitize_key($_GET['lang']) : (class_exists('RMM\\I18n\\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_current_language() : 'fa');

        $food_name = $product->get_name();
        // Multilingual fallback
        if (class_exists('RMM\\I18n\\MultiLangManager') && $active_lang !== 'fa') {
            $trans_name = \RMM\I18n\MultiLangManager::get_product_translation($product_id, 'name', $active_lang);
            if (!empty($trans_name)) {
                $food_name = $trans_name;
            }
            $trans_ing = \RMM\I18n\MultiLangManager::get_product_translation($product_id, 'ingredients', $active_lang);
            if (!empty($trans_ing)) {
                $ingredients = $trans_ing;
            }
            $trans_desc = \RMM\I18n\MultiLangManager::get_product_translation($product_id, 'desc', $active_lang);
            if (!empty($trans_desc)) {
                $desc = $trans_desc;
            }
        }

        // Collect all images (Main + Gallery Album) with optimized responsive sizing
        $images = [];
        $main_img_id = $product->get_image_id();
        if ($main_img_id) {
            $main_url = wp_get_attachment_image_url($main_img_id, 'medium_large')
                ?: (wp_get_attachment_image_url($main_img_id, 'large') ?: wp_get_attachment_image_url($main_img_id, 'full'));
            if ($main_url) {
                $images[] = $main_url;
            }
        }

        $gallery_ids = $product->get_gallery_image_ids();
        if (!empty($gallery_ids) && is_array($gallery_ids)) {
            foreach ($gallery_ids as $gid) {
                $gurl = wp_get_attachment_image_url($gid, 'medium_large')
                    ?: (wp_get_attachment_image_url($gid, 'large') ?: wp_get_attachment_image_url($gid, 'full'));
                if ($gurl && !in_array($gurl, $images)) {
                    $images[] = $gurl;
                }
            }
        }

        if (empty($images)) {
            $images[] = wc_placeholder_img_src();
        }

        $badge = class_exists('RMM\\Menu\\FoodMeta') ? \RMM\Menu\FoodMeta::get_badge_info($product_id, $active_lang) : null;
        
        // Translated categories
        $cat_terms = wp_get_post_terms($product_id, 'product_cat');
        $cats = [];
        if (!empty($cat_terms) && !is_wp_error($cat_terms)) {
            foreach ($cat_terms as $ct) {
                $t_name = (class_exists('RMM\\I18n\\MultiLangManager') && $active_lang !== 'fa') 
                    ? \RMM\I18n\MultiLangManager::get_term_translation($ct->term_id, $active_lang) 
                    : '';
                $cats[] = !empty($t_name) ? $t_name : html_entity_decode($ct->name, ENT_QUOTES, 'UTF-8');
            }
        }

        $branch_mode   = \RMM\Branches\BranchContext::get_branch_mode($active_branch);
        $is_view_only  = ($branch_mode === 'view_only');
        $catalog_hide  = (\RMM\Core\Settings::get('catalog_hide_price', 'no') === 'yes');
        $action_btn    = \RMM\Core\Settings::get('catalog_action_btn', 'whatsapp');
        $whatsapp      = \RMM\Core\Settings::get('whatsapp_number', '');
        $phone         = \RMM\Core\Settings::get('phone_number', '');

        $whatsapp_url = '';
        if ($whatsapp) {
            $msg = sprintf(__('سلام، مایل به استعلام و سفارش غذای %s هستم.', 'fondogh-online-menu'), $food_name);
            $whatsapp_url = 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode($msg);
        }

        $curr_symbol = class_exists('RMM\\I18n\\MultiLangManager') ? \RMM\I18n\MultiLangManager::get_currency_symbol_for_lang($active_lang) : (function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'تومان');

        $response_data = [
            'id'                  => $product_id,
            'name'                => $food_name,
            'image'               => $images[0],
            'images'              => $images,
            'gallery_count'       => count($images),
            'base_price'          => floatval($base_price),
            'regular_price'       => floatval($regular_price),
            'sale_price'          => floatval($sale_price),
            'is_on_sale'          => (bool) $is_on_sale,
            'currency_symbol'     => $curr_symbol,
            'formatted_price'     => wc_price($base_price),
            'formatted_regular'   => $regular_price ? wc_price($regular_price) : '',
            'in_stock'            => $product->is_in_stock(),
            'portions'            => $portions,
            'modifier_groups'     => $modifier_groups,
            'description'         => wp_strip_all_tags($desc),
            'ingredients'         => $ingredients,
            'calories'            => $calories,
            'prep_time'           => $prep_time,
            'badge'               => $badge,
            'categories'          => $cats,
            'is_view_only'        => $is_view_only,
            'catalog_hide_price'  => $catalog_hide,
            'action_btn'          => $action_btn,
            'whatsapp_url'        => $whatsapp_url,
            'phone_number'        => $phone,
        ];

        set_transient($cache_key, $response_data, 12 * HOUR_IN_SECONDS);

        wp_send_json_success($response_data);
    }

    /**
     * AJAX endpoint to render an Elementor template populated with a specific product
     */
    public function ajax_render_food_elementor_template() {
        if (!empty($_REQUEST['security'])) {
            check_ajax_referer('rmm_frontend_nonce', 'security', false);
        }

        $template_id = absint($_GET['template_id'] ?? 0);
        $product_id  = absint($_GET['product_id'] ?? 0);

        if (!$template_id || !$product_id) {
            wp_send_json_error(['message' => __('شناسه قالب یا محصول نامعتبر است.', 'fondogh-online-menu')]);
        }

        $post_obj = get_post($product_id);
        if (!$post_obj || $post_obj->post_type !== 'product') {
            wp_send_json_error(['message' => __('محصول یافت نشد.', 'fondogh-online-menu')]);
        }

        // Setup global post context so Elementor dynamic tags and WooCommerce widgets resolve to this product
        global $post, $product;
        $prev_post = $post;
        $post = $post_obj;
        setup_postdata($post);
        $product = wc_get_product($product_id);

        $html = '';
        if (class_exists('\\Elementor\\Plugin')) {
            $html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($template_id, true);
        }

        wp_reset_postdata();
        $post = $prev_post;

        if (empty($html)) {
            wp_send_json_error(['message' => __('محتوای قالب خالی است یا یافت نشد.', 'fondogh-online-menu')]);
        }

        wp_send_json_success([
            'html'       => $html,
            'product_id' => $product_id,
        ]);
    }

    public function clear_product_modal_cache($post_id) {
        global $wpdb;
        if (!empty($post_id)) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    '_transient_rmm_modal_' . absint($post_id) . '_%',
                    '_transient_timeout_rmm_modal_' . absint($post_id) . '_%'
                )
            );
        }
    }
}
