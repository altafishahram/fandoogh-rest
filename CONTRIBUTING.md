# راهنمای مشارکت و انتشار

این مخزن افزونهٔ «منو آنلاین فندق» است. هدف این راهنما این است که هر تغییر قابل ردیابی باشد و در صورت بروز مشکل، بازگشت به نسخهٔ سالم در کمتر از پنج دقیقه ممکن باشد.

## مدل شاخه‌ها (GitHub Flow)

| شاخه | نقش | قاعده |
|---|---|---|
| `main` | نسخهٔ پایدار و قابل انتشار | هر commit باید از بررسی خودکار عبور کند؛ انتشار فقط با برچسب نسخه |
| `fix/<topic>` | رفع باگ | از `main` ساخته می‌شود و با Pull Request برمی‌گردد |
| `feat/<topic>` | قابلیت جدید | از `main` ساخته می‌شود و با Pull Request برمی‌گردد |
| `chore/<topic>` | مستندات، ابزار، بسته‌بندی | از `main` ساخته می‌شود |

شاخهٔ `develop` نگه داشته نمی‌شود؛ یک خط پایدار و شاخه‌های کوتاه‌عمر، تاریخچه را ساده و بازگشت را مطمئن می‌کند.

## پیام commit

قالب: `<type>(<scope>): <subject>`

- `type`: `feat`، `fix`، `refactor`، `perf`، `docs`، `test`، `build`، `ci`، `chore`
- `scope`: نام ماژول، مثل `inventory`، `rest`، `elementor`، `portal`
- مثال: `fix(inventory): preserve branch price when toggling stock`

هر commit باید یک تغییر منطقی باشد. تغییرات نامرتبط را جدا کنید.

## چرخهٔ کار

```bash
git switch main
git pull --ff-only
git switch -c fix/short-topic

# ... تغییر کد ...

python tools/preflight.py      # بررسی نحوی + ساخت بسته + اعتبارسنجی محتوا
git add -A
git commit -m "fix(inventory): update only stock columns"

git push -u origin fix/inventory-partial-update
gh pr create --fill            # Pull Request
```

پس از سبز شدن بررسی خودکار، Pull Request ادغام می‌شود. ادغام فقط با بررسی سبز مجاز است.

## انتشار نسخه

۱. شمارهٔ نسخه را در دو جا هم‌زمان به‌روز کنید:

- `fondogh-online-menu.php` → هدر `Version`
- `fondogh-online-menu.php` → ثابت `RMM_VERSION`

۲. `CHANGELOG.md` را با بخش نسخهٔ جدید به‌روز کنید.

۳. بسته را بسازید و بررسی کنید:

```bash
python tools/preflight.py
```

۴. برچسب بزنید و منتشر کنید:

```bash
git add CHANGELOG.md fondogh-online-menu.php
git commit -m "chore(release): v1.1.0"
git tag -a v1.1.0 -m "Release v1.1.0"
git push origin main --follow-tags
```

با push برچسب، جریان انتشار به‌صورت خودکار بستهٔ ZIP و فایل SHA-256 را در GitHub Releases منتشر می‌کند. اگر شمارهٔ برچسب با نسخهٔ افزونه یکی نباشد، انتشار متوقف می‌شود.

## بازگشت سریع در صورت مشکل

**۱. برگرداندن یک تغییر مشخص روی `main` (روش پیش‌فرض):**

```bash
git switch main
git pull --ff-only
git revert <commit-sha>
git push origin main
```

سپس یک نسخهٔ اصلاحی (`v1.1.1`) منتشر کنید. تاریخچه حفظ می‌شود و هیچ داده‌ای از دست نمی‌رود.

**۲. بازگرداندن کل سایت به نسخهٔ قبلی افزونه (بدون تغییر مخزن):**

از صفحهٔ Releases، فایل ZIP نسخهٔ سالم قبلی را دانلود و روی افزونهٔ نصب‌شده جایگزین کنید. این روش مستقل از Git است و همیشه در دسترس باقی می‌ماند.

**۳. بازنویسی تاریخچه (فقط با تأیید مالک پروژه):**

```bash
git reset --hard v1.0.0
git push --force-with-lease origin main
```

این روش آخرین گزینه است، چون commitهای بعدی را حذف می‌کند. قبل از آن همیشه یک برچسب پشتیبان بسازید:

```bash
git tag backup/pre-reset-$(date +%Y%m%d-%H%M)
git push origin --tags
```

## قواعد کیفیت

- هر Pull Request باید بررسی خودکار (lint و PHPUnit روی PHP 8.2 و 8.3) را پاس کند.
- فایل `PROJECT_HANDOVER.md` مرجع معماری است؛ هر تغییر در قرارداد API هم‌زمان در آن به‌روز شود.
- بستهٔ نصب فقط با `tools/build-release.py` ساخته می‌شود؛ ساخت دستی ZIP ممنوع است.
- اطلاعات محرمانه، گزارش‌های داخلی و تنظیمات ابزارها هرگز وارد این مخزن نمی‌شوند.
