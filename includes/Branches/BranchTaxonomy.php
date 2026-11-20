<?php
namespace RMM\Branches;

if (!defined('ABSPATH')) {
    exit;
}

class BranchTaxonomy {

    const TAXONOMY = 'rmm_branch';

    public function __construct() {
        add_action('init', [$this, 'register_taxonomy']);
        add_action(self::TAXONOMY . '_add_form_fields', [$this, 'add_term_fields']);
        add_action(self::TAXONOMY . '_edit_form_fields', [$this, 'edit_term_fields'], 10, 2);
        add_action('created_' . self::TAXONOMY, [$this, 'save_term_fields']);
        add_action('edited_' . self::TAXONOMY, [$this, 'save_term_fields']);
        add_filter('manage_edit-' . self::TAXONOMY . '_columns', [$this, 'manage_columns']);
        add_filter('manage_' . self::TAXONOMY . '_custom_column', [$this, 'render_columns'], 10, 3);
    }

    public function register_taxonomy() {
        $labels = [
            'name'                       => __('شعب رستوران', 'restaurant-menu-manager'),
            'singular_name'              => __('شعبه', 'restaurant-menu-manager'),
            'search_items'               => __('جستجوی شعب', 'restaurant-menu-manager'),
            'all_items'                  => __('همه شعب', 'restaurant-menu-manager'),
            'edit_item'                  => __('ویرایش شعبه', 'restaurant-menu-manager'),
            'update_item'                => __('به‌روزرسانی شعبه', 'restaurant-menu-manager'),
            'add_new_item'               => __('افزودن شعبه جدید', 'restaurant-menu-manager'),
            'new_item_name'              => __('نام شعبه جدید', 'restaurant-menu-manager'),
            'menu_name'                  => __('شعب رستوران', 'restaurant-menu-manager'),
        ];

        register_taxonomy(self::TAXONOMY, ['product'], [
            'hierarchical'          => true,
            'labels'                => $labels,
            'show_ui'               => true,
            'show_in_menu'          => 'rmm-settings',
            'show_admin_column'     => true,
            'query_var'             => true,
            'rewrite'               => ['slug' => 'branch'],
            'show_in_rest'          => true,
            'public'                => true,
        ]);
    }

    public function add_term_fields() {
        wp_nonce_field('rmm_branch_term_action', 'rmm_branch_term_nonce');
        $users = get_users(['role__in' => ['administrator', 'restaurant_manager', 'shop_manager']]);
        ?>
        <div class="form-field">
            <label for="rmm_phone"><?php _e('شماره تماس شعبه', 'restaurant-menu-manager'); ?></label>
            <input type="text" name="rmm_phone" id="rmm_phone" placeholder="021...">
        </div>
        <div class="form-field">
            <label for="rmm_address"><?php _e('آدرس پستی شعبه', 'restaurant-menu-manager'); ?></label>
            <textarea name="rmm_address" id="rmm_address" rows="3"></textarea>
        </div>
        <div class="form-field">
            <label for="rmm_hours"><?php _e('ساعات کاری (مثلاً: ۱۲:۰۰ الی ۲۴:۰۰)', 'restaurant-menu-manager'); ?></label>
            <input type="text" name="rmm_hours" id="rmm_hours" placeholder="۱۲:۰۰ الی ۲۴:۰۰">
        </div>
        <div class="form-field">
            <label for="rmm_mode"><?php _e('حالت کاری منوی این شعبه', 'restaurant-menu-manager'); ?></label>
            <select name="rmm_mode" id="rmm_mode">
                <option value="global"><?php _e('پیروی از تنظیمات سراسری افزونه', 'restaurant-menu-manager'); ?></option>
                <option value="online_delivery"><?php _e('سفارش آنلاین و بیرون‌بر', 'restaurant-menu-manager'); ?></option>
                <option value="dine_in"><?php _e('سفارش سر میز با QR Code', 'restaurant-menu-manager'); ?></option>
                <option value="view_only"><?php _e('کاتالوگ و فقط مشاهده منو (بدون خرید)', 'restaurant-menu-manager'); ?></option>
            </select>
        </div>
        <div class="form-field">
            <label for="rmm_manager_id"><?php _e('مدیر اختصاصی این شعبه', 'restaurant-menu-manager'); ?></label>
            <select name="rmm_manager_id" id="rmm_manager_id">
                <option value=""><?php _e('-- انتخاب مدیر شعبه --', 'restaurant-menu-manager'); ?></option>
                <?php foreach ($users as $u) : ?>
                    <option value="<?php echo esc_attr($u->ID); ?>"><?php echo esc_html($u->display_name . ' (' . $u->user_login . ')'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="rmm_min_order"><?php _e('حداقل مبلغ سفارش (تومان)', 'restaurant-menu-manager'); ?></label>
            <input type="number" name="rmm_min_order" id="rmm_min_order" value="0">
        </div>
        <div class="form-field">
            <label for="rmm_tables_count"><?php _e('تعداد میزهای فعال (جهت صدور QR کد)', 'restaurant-menu-manager'); ?></label>
            <input type="number" name="rmm_tables_count" id="rmm_tables_count" value="10">
        </div>
        <?php
    }

    public function edit_term_fields($term, $taxonomy) {
        wp_nonce_field('rmm_branch_term_action', 'rmm_branch_term_nonce');
        $phone       = get_term_meta($term->term_id, '_rmm_phone', true);
        $address     = get_term_meta($term->term_id, '_rmm_address', true);
        $hours       = get_term_meta($term->term_id, '_rmm_hours', true);
        $mode        = get_term_meta($term->term_id, '_rmm_mode', true) ?: 'global';
        $manager_id  = get_term_meta($term->term_id, '_rmm_manager_id', true);
        $min_order   = get_term_meta($term->term_id, '_rmm_min_order', true) ?: '0';
        $tables_cnt  = get_term_meta($term->term_id, '_rmm_tables_count', true) ?: '10';
        $is_open     = get_term_meta($term->term_id, '_rmm_is_open', true);
        if ($is_open === '') $is_open = '1';

        $users = get_users(['role__in' => ['administrator', 'restaurant_manager', 'shop_manager']]);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="rmm_is_open"><?php _e('وضعیت فعالیت لحظه‌ای شعبه', 'restaurant-menu-manager'); ?></label></th>
            <td>
                <select name="rmm_is_open" id="rmm_is_open">
                    <option value="1" <?php selected($is_open, '1'); ?>><?php _e('🟢 شعبه باز و فعال است', 'restaurant-menu-manager'); ?></option>
                    <option value="0" <?php selected($is_open, '0'); ?>><?php _e('🔴 شعبه موقتاً تعطیل / بسته است', 'restaurant-menu-manager'); ?></option>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="rmm_phone"><?php _e('شماره تماس شعبه', 'restaurant-menu-manager'); ?></label></th>
            <td><input type="text" name="rmm_phone" id="rmm_phone" value="<?php echo esc_attr($phone); ?>"></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="rmm_address"><?php _e('آدرس پستی شعبه', 'restaurant-menu-manager'); ?></label></th>
            <td><textarea name="rmm_address" id="rmm_address" rows="3"><?php echo esc_textarea($address); ?></textarea></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="rmm_hours"><?php _e('ساعات کاری', 'restaurant-menu-manager'); ?></label></th>
            <td><input type="text" name="rmm_hours" id="rmm_hours" value="<?php echo esc_attr($hours); ?>"></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="rmm_mode"><?php _e('حالت کاری منوی این شعبه', 'restaurant-menu-manager'); ?></label></th>
            <td>
                <select name="rmm_mode" id="rmm_mode">
                    <option value="global" <?php selected($mode, 'global'); ?>><?php _e('پیروی از تنظیمات سراسری افزونه', 'restaurant-menu-manager'); ?></option>
                    <option value="online_delivery" <?php selected($mode, 'online_delivery'); ?>><?php _e('سفارش آنلاین و بیرون‌بر', 'restaurant-menu-manager'); ?></option>
                    <option value="dine_in" <?php selected($mode, 'dine_in'); ?>><?php _e('سفارش سر میز با QR Code', 'restaurant-menu-manager'); ?></option>
                    <option value="view_only" <?php selected($mode, 'view_only'); ?>><?php _e('کاتالوگ و فقط مشاهده منو (بدون خرید)', 'restaurant-menu-manager'); ?></option>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="rmm_manager_id"><?php _e('مدیر اختصاصی این شعبه', 'restaurant-menu-manager'); ?></label></th>
            <td>
                <select name="rmm_manager_id" id="rmm_manager_id">
                    <option value=""><?php _e('-- انتخاب مدیر شعبه --', 'restaurant-menu-manager'); ?></option>
                    <?php foreach ($users as $u) : ?>
                        <option value="<?php echo esc_attr($u->ID); ?>" <?php selected($manager_id, $u->ID); ?>><?php echo esc_html($u->display_name . ' (' . $u->user_login . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="rmm_min_order"><?php _e('حداقل مبلغ سفارش (تومان)', 'restaurant-menu-manager'); ?></label></th>
            <td><input type="number" name="rmm_min_order" id="rmm_min_order" value="<?php echo esc_attr($min_order); ?>"></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="rmm_tables_count"><?php _e('تعداد میزهای فعال (جهت صدور QR کد)', 'restaurant-menu-manager'); ?></label></th>
            <td><input type="number" name="rmm_tables_count" id="rmm_tables_count" value="<?php echo esc_attr($tables_cnt); ?>"></td>
        </tr>
        <?php
    }

    public function save_term_fields($term_id) {
        if (!isset($_POST['rmm_branch_term_nonce']) || !wp_verify_nonce($_POST['rmm_branch_term_nonce'], 'rmm_branch_term_action')) {
            return;
        }

        if (!current_user_can('manage_categories')) {
            return;
        }

        if (isset($_POST['rmm_phone'])) {
            update_term_meta($term_id, '_rmm_phone', sanitize_text_field($_POST['rmm_phone']));
        }
        if (isset($_POST['rmm_address'])) {
            update_term_meta($term_id, '_rmm_address', sanitize_textarea_field($_POST['rmm_address']));
        }
        if (isset($_POST['rmm_hours'])) {
            update_term_meta($term_id, '_rmm_hours', sanitize_text_field($_POST['rmm_hours']));
        }
        if (isset($_POST['rmm_mode'])) {
            update_term_meta($term_id, '_rmm_mode', sanitize_text_field($_POST['rmm_mode']));
        }
        if (isset($_POST['rmm_manager_id'])) {
            update_term_meta($term_id, '_rmm_manager_id', absint($_POST['rmm_manager_id']));
        }
        if (isset($_POST['rmm_min_order'])) {
            update_term_meta($term_id, '_rmm_min_order', floatval($_POST['rmm_min_order']));
        }
        if (isset($_POST['rmm_tables_count'])) {
            update_term_meta($term_id, '_rmm_tables_count', absint($_POST['rmm_tables_count']));
        }
        if (isset($_POST['rmm_is_open'])) {
            update_term_meta($term_id, '_rmm_is_open', sanitize_text_field($_POST['rmm_is_open']));
        }
    }

    public function manage_columns($columns) {
        $new_columns = [];
        foreach ($columns as $k => $v) {
            $new_columns[$k] = $v;
            if ($k === 'name') {
                $new_columns['rmm_mode_col']   = __('حالت منو', 'restaurant-menu-manager');
                $new_columns['rmm_status_col'] = __('وضعیت فعالیت', 'restaurant-menu-manager');
            }
        }
        return $new_columns;
    }

    public function render_columns($content, $column_name, $term_id) {
        if ($column_name === 'rmm_mode_col') {
            $mode = get_term_meta($term_id, '_rmm_mode', true) ?: 'global';
            $map = [
                'global'          => __('سراسری', 'restaurant-menu-manager'),
                'online_delivery' => __('سفارش آنلاین', 'restaurant-menu-manager'),
                'dine_in'         => __('سر میز (Dine-in)', 'restaurant-menu-manager'),
                'view_only'       => __('کاتالوگ (فقط مشاهده)', 'restaurant-menu-manager'),
            ];
            return esc_html($map[$mode] ?? $mode);
        }

        if ($column_name === 'rmm_status_col') {
            $open = get_term_meta($term_id, '_rmm_is_open', true);
            if ($open === '0') {
                return '<span style="color:#ef4444; font-weight:bold;">' . __('🔴 تعطیل', 'restaurant-menu-manager') . '</span>';
            }
            return '<span style="color:#10b981; font-weight:bold;">' . __('🟢 باز', 'restaurant-menu-manager') . '</span>';
        }

        return $content;
    }
}
