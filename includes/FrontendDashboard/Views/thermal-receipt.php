<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?php _e('چاپ فیش سفارش ۸۰ میلی‌متری', 'restaurant-menu-manager'); ?></title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            width: 78mm;
            margin: 0 auto;
            padding: 8px;
            font-family: Tahoma, 'B Nazanin', Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
            direction: rtl;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .restaurant-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 4px;
        }
        .branch-info {
            font-size: 11px;
            margin: 2px 0;
        }
        .table-banner {
            background: #000;
            color: #fff;
            font-size: 15px;
            font-weight: bold;
            padding: 4px;
            margin: 6px 0;
            text-align: center;
            border-radius: 4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .meta-line {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 4px 0;
        }
        .items-table th {
            text-align: right;
            font-size: 11px;
            padding: 4px 0;
            border-bottom: 1px solid #000;
        }
        .items-table td {
            padding: 4px 0;
            font-size: 11px;
            vertical-align: top;
        }
        .item-mods {
            font-size: 10px;
            color: #333;
            padding-right: 8px;
            margin-top: 2px;
        }
        .receipt-total {
            margin-top: 8px;
            border-top: 1px solid #000;
            padding-top: 6px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
            margin-top: 4px;
        }
        .notes-box {
            border: 1px solid #000;
            padding: 4px;
            margin-top: 8px;
            font-size: 11px;
        }
        .receipt-footer {
            text-align: center;
            font-size: 10px;
            margin-top: 12px;
            border-top: 1px dashed #000;
            padding-top: 6px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 15px; padding: 10px; background: #e0f2fe; border-radius: 8px;">
        <button onclick="window.print()" style="background: #0284c7; color: #fff; border: none; padding: 8px 20px; font-size: 14px; font-weight: bold; border-radius: 6px; cursor: pointer;">
            🖨️ <?php _e('چاپ فیش (Print)', 'restaurant-menu-manager'); ?>
        </button>
    </div>

    <div id="receipt-content">
        <!-- Rendered dynamically via JavaScript or server data -->
    </div>
</body>
</html>
