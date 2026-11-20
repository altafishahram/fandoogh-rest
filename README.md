# منو آنلاین فندق

افزونهٔ وردپرس برای مدیریت منوی آنلاین، سفارش‌گیری و عملیات رستوران‌های چندشعبه‌ای، ساخته‌شده روی WooCommerce و Elementor.

## چه می‌کند

- **چندشعبه‌ای:** قیمت و موجودی مستقل هر شعبه در جدول اختصاصی، بدون سنگین‌کردن `wp_postmeta`.
- **سه حالت کاری:** کاتالوگ دیجیتال (`view_only`)، سفارش سر میز با QR (`dine_in`) و بیرون‌بر (`online_delivery`).
- **داشبورد PWA مستقل:** مسیر `/restaurant-portal` بدون قالب سایت؛ تغییر آنی موجودی و قیمت، چاپ فیش ۸۰ میلی‌متری و مانیتور سفارش آشپزخانه.
- **المنتور:** ویجت‌های منو، نوار دسته، سبد شناور، جستجو، سوئیچر زبان و تم، و Dynamic Tagها.
- **سفارشی‌سازی غذا:** سایزبندی، تاپینگ، نشان‌های ویژه، کالری، ترکیبات و ترجمهٔ درونی.

## پیش‌نیازها

| مورد | حداقل |
|---|---|
| WordPress | ۵.۸ |
| PHP | ۷.۴ |
| WooCommerce | ۵.۰ |
| Elementor | ۳.x (Pro برای Dynamic Tagها) |

## نصب

۱. فایل `fondogh-online-menu.zip` را از بخش [Releases](../../releases/latest) دانلود کنید.
۲. در پیشخوان وردپرس: **افزونه‌ها → افزودن → بارگذاری افزونه** و انتخاب فایل ZIP.
۳. فعال‌سازی، سپس تنظیم شعبه‌ها و حالت کاری منو از صفحهٔ تنظیمات افزونه.

مجموع SHA-256 هر نسخه کنار همان فایل در Releases منتشر می‌شود.

## توسعه

```bash
git switch -c fix/short-topic
# تغییر کد
python tools/preflight.py     # بررسی نحوی PHP + ساخت و اعتبارسنجی بسته
```

- چرخهٔ کار، قاعدهٔ commit، انتشار و **روش بازگشت** در [CONTRIBUTING.md](CONTRIBUTING.md).
- معماری، قرارداد API و نکات حساس در [PROJECT_HANDOVER.md](PROJECT_HANDOVER.md).
- تاریخچهٔ نسخه‌ها در [CHANGELOG.md](CHANGELOG.md).

## ساختار

```text
fondogh-online-menu.php      فایل اصلی و ثابت‌ها
includes/                    هسته، شعب، منو، I18n، داشبورد و المنتور
assets/css|js                دارایی‌های فرانت و پنل
assets/templates-json        کیت‌های قالب المنتور
uninstall.php                چرخهٔ حذف داده
tools/                       ساخت بسته و بررسی پیش از انتشار
```

## پشتیبانی و امنیت

- گزارش خطا: Issues با قالب Bug report.
- گزارش آسیب‌پذیری: از مسیر خصوصی **Security → Report a vulnerability**؛ جزئیات در [SECURITY.md](SECURITY.md).

## پروانه

GNU General Public License v2.0 یا بالاتر — [LICENSE](LICENSE).
