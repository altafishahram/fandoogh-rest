<?php
namespace RMM\OperationModes;

if (!defined('ABSPATH')) {
    exit;
}

class TableQRGenerator {

    /**
     * Build Dine-in URL with branch and table parameters
     *
     * @param int $branch_id
     * @param int $table_number
     * @return string
     */
    public static function get_table_url($branch_id, $table_number) {
        $base_url = home_url('/');
        return add_query_arg([
            'branch' => absint($branch_id),
            'table'  => absint($table_number)
        ], $base_url);
    }

    /**
     * Get QR Code Image URL (using reliable fast API with fallback)
     *
     * @param int $branch_id
     * @param int $table_number
     * @param int $size
     * @return string
     */
    public static function get_qr_image_url($branch_id, $table_number, $size = 250) {
        $target_url = self::get_table_url($branch_id, $table_number);
        // Using qrserver API with URL encoding
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . rawurlencode($target_url);
    }

    /**
     * Render a printable QR card for physical dining tables
     */
    public static function render_table_card($branch_id, $table_number) {
        $branch = get_term($branch_id, \RMM\Branches\BranchTaxonomy::TAXONOMY);
        $branch_name = ($branch && !is_wp_error($branch)) ? $branch->name : get_bloginfo('name');
        $qr_url = self::get_qr_image_url($branch_id, $table_number, 300);
        $menu_url = self::get_table_url($branch_id, $table_number);

        ob_start();
        ?>
        <div class="rmm-table-qr-card" style="border: 2px dashed #0284c7; border-radius: 16px; padding: 25px; text-align: center; max-width: 320px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.06); font-family: inherit;">
            <div style="font-size: 13px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">
                <?php echo esc_html($branch_name); ?>
            </div>
            <h3 style="margin: 0 0 15px; font-size: 22px; color: #0f172a;">
                <?php printf(esc_html__('میز شماره %d', 'restaurant-menu-manager'), $table_number); ?>
            </h3>
            
            <div style="background: #f8fafc; padding: 15px; border-radius: 12px; display: inline-block; margin-bottom: 15px; border: 1px solid #e2e8f0;">
                <img src="<?php echo esc_url($qr_url); ?>" alt="<?php printf(esc_attr__('QR کد میز %d', 'restaurant-menu-manager'), $table_number); ?>" style="width: 200px; height: 200px; display: block; margin: 0 auto;">
            </div>

            <p style="margin: 0 0 6px; font-size: 14px; font-weight: bold; color: #1e293b;">
                <?php _e('برای مشاهده منو و ثبت سفارش اسکن کنید', 'restaurant-menu-manager'); ?>
            </p>
            <p style="margin: 0; font-size: 12px; color: #94a3b8; word-break: break-all;">
                <?php echo esc_html($menu_url); ?>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
}
