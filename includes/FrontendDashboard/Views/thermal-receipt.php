<?php
if (!defined('ABSPATH')) {
    exit;
}

$order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : (isset($order) && is_a($order, 'WC_Order') ? $order->get_id() : 0);
$wc_order = $order_id ? wc_get_order($order_id) : null;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?php _e('چاپ فیش سفارش ۸۰ میلی‌متری', 'fondogh-online-menu'); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(RMM_PLUGIN_URL . 'assets/css/thermal-receipt.css?ver=' . RMM_VERSION); ?>">
    
</head>
<body>
    <div class="no-print rmm-receipt-inline-06ed9304">
        <button onclick="window.print()" class="rmm-receipt-inline-abdd0186">
            🖨️ <?php _e('چاپ فیش (Print)', 'fondogh-online-menu'); ?>
        </button>
    </div>

    <div id="receipt-content">
        <?php if ($wc_order) :
            $table_no      = $wc_order->get_meta('_rmm_table_number');
            $branch_id     = $wc_order->get_meta('_rmm_order_branch_id');
            $branch        = $branch_id ? get_term($branch_id, \RMM\Branches\BranchTaxonomy::TAXONOMY) : null;
            $branch_name   = ($branch && !is_wp_error($branch)) ? $branch->name : '';
            $branch_phone  = $branch_id ? get_term_meta($branch_id, '_rmm_phone', true) : '';
            $note          = $wc_order->get_customer_note();
            $address       = $wc_order->get_formatted_billing_address();
            $customer_name = $wc_order->get_formatted_billing_full_name() ?: __('مشتری حضوری', 'fondogh-online-menu');
            $currency      = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'تومان';
        ?>
            <div class="receipt-header">
                <h2 class="restaurant-title"><?php echo esc_html(get_bloginfo('name')); ?></h2>
                <?php if ($branch_name || $branch_phone) : ?>
                    <div class="branch-info"><?php echo esc_html($branch_name . ($branch_phone ? ' - ' . $branch_phone : '')); ?></div>
                <?php endif; ?>
            </div>

            <?php if ($table_no) : ?>
                <div class="table-banner"><?php printf(esc_html__('میز شماره %s', 'fondogh-online-menu'), esc_html($table_no)); ?></div>
            <?php endif; ?>

            <div class="meta-line">
                <span><?php printf(esc_html__('شماره فاکتور: #%s', 'fondogh-online-menu'), esc_html($wc_order->get_id())); ?></span>
                <span><?php echo esc_html($wc_order->get_date_created() ? $wc_order->get_date_created()->date_i18n('Y/m/d - H:i') : ''); ?></span>
            </div>

            <?php if ($customer_name) : ?>
                <div class="meta-line">
                    <span><?php esc_html_e('مشتری:', 'fondogh-online-menu'); ?> <?php echo esc_html($customer_name); ?></span>
                    <?php if ($wc_order->get_billing_phone()) : ?>
                        <span><?php echo esc_html($wc_order->get_billing_phone()); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($address) : ?>
                <div class="meta-line rmm-receipt-inline-58cc06eb">
                    <span><?php esc_html_e('آدرس:', 'fondogh-online-menu'); ?> <?php echo esc_html(wp_strip_all_tags($address)); ?></span>
                </div>
            <?php endif; ?>

            <table class="items-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('شرح', 'fondogh-online-menu'); ?></th>
                        <th class="rmm-receipt-inline-94edfc84"><?php esc_html_e('تعداد', 'fondogh-online-menu'); ?></th>
                        <th class="rmm-receipt-inline-1d960410"><?php esc_html_e('مبلغ', 'fondogh-online-menu'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wc_order->get_items() as $item) :
                        $item_name  = $item->get_name();
                        $item_qty   = $item->get_quantity();
                        $item_total = $item->get_total();
                    ?>
                        <tr>
                            <td>
                                <div><?php echo esc_html($item_name); ?></div>
                                <?php
                                $mods = [];
                                foreach ($item->get_formatted_meta_data() as $m) {
                                    $mods[] = strip_tags($m->display_key . ': ' . $m->display_value);
                                }
                                if (!empty($mods)) : ?>
                                    <div class="item-mods"><?php echo esc_html(implode(', ', $mods)); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="rmm-receipt-inline-61a6bc8e"><?php echo esc_html($item_qty); ?></td>
                            <td class="rmm-receipt-inline-1ec27ed6"><?php echo esc_html(number_format_i18n($item_total)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="receipt-total">
                <div class="total-row">
                    <span><?php esc_html_e('مبلغ کل:', 'fondogh-online-menu'); ?></span>
                    <span><?php echo esc_html(number_format_i18n($wc_order->get_total()) . ' ' . $currency); ?></span>
                </div>
            </div>

            <?php if (!empty($note)) : ?>
                <div class="notes-box">
                    <strong><?php esc_html_e('یادداشت مشتری:', 'fondogh-online-menu'); ?></strong><br>
                    <?php echo nl2br(esc_html($note)); ?>
                </div>
            <?php endif; ?>

            <div class="receipt-footer">
                <?php esc_html_e('با تشکر از انتخاب شما | بازگشت مجدد شما افتخار ماست', 'fondogh-online-menu'); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
