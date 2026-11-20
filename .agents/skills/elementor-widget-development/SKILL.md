---
name: elementor-widget-development
description: ساخت و بازبینی ویجت، Dynamic Tag و کیت قالب المنتور در افزونهٔ وردپرس؛ ثبت کنترل‌ها، بارگذاری دارایی، RTL، تم شب و سازگاری Elementor Pro.
aliases: [elementor widget, ویجت المنتور, dynamic tag, template library, کیت قالب]
---

# توسعهٔ ویجت المنتور

مرجع اجرایی برای فایل‌های `includes/Elementor/` در این پروژه.

## ۱. ساختار پایه

- ثبت دسته در `ElementorIntegration.php` با `elementor/elements/categories_registered`.
- ثبت ویجت با `elementor/widgets/register` (نسخه‌های جدید) و کلاس مشتق از `\Elementor\Widget_Base`.
- هر ویجت سه متد حداقلی دارد: `get_name()`, `get_title()`, `get_icon()`, و سپس `register_controls()` و `render()`.
- `get_style_depends()` و `get_script_depends()` را پر کن و دارایی را با `wp_register_style/script` در `Assets.php` ثبت کن؛ CSS/JS تکراری داخل `render()` ننویس.

## ۲. کنترل‌ها و خروجی

- برای هر کنترل گرافیکی `esc_html`، `esc_attr`، `esc_url` و برای HTML مجاز `wp_kses_post` به کار ببر.
- در `render()` هرگز به `$_GET` خام تکیه نکن؛ از `get_settings_for_display()` و helperهای خود پروژه استفاده کن.
- برای قیمت و عدد، قالب نمایش را از تنظیمات بخوان، نه hard-code.

## ۳. Dynamic Tag و Loop

- هر tag از `\Elementor\Core\DynamicTags\Tag` مشتق شود و `get_categories()` و `get_group()` را دقیق برگرداند.
- در Loop Builder، شناسهٔ محصول/شعبه از `$settings['__dynamic__']` یا context حل شود؛ نتیجه را کش کن تا N+1 query نسازی.

## ۴. کیت قالب

- یک منبع حقیقت انتخاب کن: JSON یا ساختار PHP. نگه‌داشتن هر دو، دو نسخهٔ واگرا می‌سازد.
- اگر JSON منبع است، همان فایل‌های `assets/templates-json/` را validate و import کن؛ کاتالوگ و نام‌ها باید با سند یکی باشد.
- پیش‌نمایش قالب باید به فایل موجود اشاره کند؛ مسیر ۴۰۴ در کتابخانه یعنی کیت ناقص.

## ۵. تله‌های همین پروژه

- کلاس تم شرقی روی رپر (`rmm-theme-oriental`) و گرید (`rmm-style-oriental`) جدا اعمال می‌شود؛ سلکتور باید هر دو را پوشش دهد.
- در کنترل‌های رنگ، `!important` نگذار؛ سوئیچ شب/روز از CSS فرانت رنگ را عوض می‌کند.
- `.rmm-modal-backdrop` نباید `display: flex !important` عمومی بگیرد؛ قانون شرطی `[style*="display: none"]` حفظ شود.
- RTL: از logical properties (`margin-inline`, `inset-inline`) استفاده کن و چیدمان را در هر دو جهت بررسی کن.
- ویجت باید با دادهٔ خالی، تصویر نداشته و محصول ناموجود هم بدون خطا رندر شود.

## ۶. چک‌لیست پیش از تحویل

- [ ] دارایی‌ها register/enqueue شده و در ادیتور و فرانت لود می‌شوند.
- [ ] همهٔ خروجی‌ها escape شده‌اند.
- [ ] ویجت در حالت RTL، موبایل و تم شب درست است.
- [ ] با Elementor و Elementor Pro فعال تست شده و بدون آن خطا نمی‌دهد.
- [ ] هیچ preview/icon ارجاع‌داده‌شده‌ای ۴۰۴ نمی‌شود.
