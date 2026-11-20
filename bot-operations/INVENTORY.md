# فهرست بات‌ها، نقش‌ها و منابع پروژه

| بات | نقش عملیاتی | پوشهٔ کار | گزارش فعالیت |
|---|---|---|---|
| خان | مدیر پروژه و هماهنگ‌کننده | `E:\رستوران فندوق` | `logs/khan.md` |
| مطهری | توسعه‌دهنده و مدرس افزونه‌های وردپرس و UI/UX | `E:\رستوران فندوق` | `logs/motahari.md` |
| شنبه | بازرس کیفیت، عملکرد و باگ پروژهٔ وردپرس | `E:\رستوران فندوق` | `logs/shanbeh.md` |

تاریخ ایجاد: ۲۰ نوامبر ۲۰۲۶ — آخرین به‌روزرسانی: ۲۰ نوامبر ۲۰۲۶

## منابع واحد (Single Source of Truth)

| موضوع | مسیر رسمی | توضیح |
|---|---|---|
| مهارت‌های تیم | `.agents/skills/<name>/SKILL.md` | منبع اصلی؛ `.claude/skills` فقط آینه است و دستی ویرایش نشود |
| سند فنی پروژه | `PROJECT_HANDOVER.md` | مرجع معماری، API و تله‌های فنی |
| گزارش ممیزی کیفیت | `bot-operations/reports/quality-audit.md` | ردیف‌های باگ با شدت و شواهد |
| بازبینی معماری | `bot-operations/reports/architecture-review.md` | انطباق با سند و مسیر ماژولار شدن |
| جمع‌بندی مدیریتی | `bot-operations/reports/project-review.md` | وضعیت تأییدشده، اقدام‌ها و اولویت بعدی |
| سند اولیهٔ طراحی | `bot-operations/docs/code-artifact.md` | Master Prompt اولیه، فقط مرجع تاریخی |
| بستهٔ اجرا | `tools/build-release.py` → `fondogh-online-menu.zip` | ساخت بسته فقط با فهرست مجاز |
| دارایی‌های طراحی | `assets/design-source/` | SVGهای Iconsax و اسکریپت تولیدشان؛ وارد بستهٔ نصب نمی‌شوند |

## مهارت‌های فعال

| مهارت | کاربرد | برای |
|---|---|---|
| `wordpress-plugin-uiux` | توسعهٔ امن افزونه با UI/UX و سازگاری المنتور | مطهری |
| `wordpress-rest-security` | مجوز هر مسیر، محدودسازی شعبه، تغییر جزئی داده | مطهری، شنبه |
| `wordpress-quality-audit` | ممیزی و گزارش باگ قابل‌اقدام | شنبه |
| `elementor-widget-development` | ویجت، Dynamic Tag و کیت قالب المنتور | مطهری |
| `ui-ux-pro-max` | مرجع طراحی رابط و الگوها | مطهری، خان |
