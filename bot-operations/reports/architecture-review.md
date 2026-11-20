# بازبینی معماری و قابلیت نگهداری — منو آنلاین فندق

تاریخ بازبینی: ۲۰ نوامبر ۲۰۲۶  
دامنه: فقط ساختار، انطباق با `PROJECT_HANDOVER.md` و قابلیت نگهداری. کد و فایل‌های موجود تغییر یا حذف نشده‌اند.

## جمع‌بندی اجرایی

هستهٔ افزونه از نظر تفکیک پوشه‌ها و فضای نام `RMM\` با سند مرجع هم‌راستاست و همهٔ مسیرهای اصلیِ معرفی‌شده در سند وجود دارند. با این حال، مرز ماژول‌ها در لایهٔ داشبورد، موجودی شعب و کتابخانهٔ قالب‌ها هنوز به اندازه‌ای محکم نیست که توسعهٔ امن چندشعبه‌ای را تضمین کند. دو ایراد داده‌ای در Repository می‌توانند با یک عملیات ظاهراً محدود، قیمت یا موجودی شعبه را ناخواسته بازنویسی کنند؛ این‌ها باید پیش از توسعهٔ قابلیت‌های جدید رفع شوند.

## روش و شواهد

- سند مرجع: `PROJECT_HANDOVER.md`.
- ساختار واقعی: ۳۷ فایل PHP زیر ریشه و `includes/`، فایل‌های CSS/JS و قالب‌های Elementor بازبینی شدند.
- آزمون ایستا: `php -l` روی هر ۳۷ فایل PHP با موفقیت گذشت. این آزمون فقط نحو را تأیید می‌کند، نه رفتار در وردپرس/ووکامرس/المنتور.
- هیچ پیکربندی آزمون یا ساخت قابل ردیابی پیدا نشد: `composer.json`، `phpunit.xml*`، `package.json`، lockfile و پیکربندی bundler وجود ندارند.

## ماژول‌های موجود و مرز فعلی آن‌ها

| حوزه | پیاده‌سازی واقعی | ارزیابی معماری |
| --- | --- | --- |
| راه‌اندازی | `fondogh-online-menu.php:22-104`، `includes/Autoloader.php:8-29`، `includes/Plugin.php:8-102` | Autoloader با قرارداد PSR-4 برای `RMM\` درست و ساده است. `Plugin` Composition Root است، اما راه‌اندازی مرحله‌ای با `class_exists()` برای فایل‌های محلی لازم نیست و وضعیت «فازهای ناقص» را پنهان می‌کند. |
| چرخهٔ حیات و تنظیمات | `includes/Core/Installer.php`، `includes/Core/Settings.php`، `includes/Core/Assets.php` | جدول اختصاصی و نقش‌ها تعریف شده‌اند؛ تنظیمات، HTML پنل و منطق ثبت در یک کلاس ۴۰۶ خطی جمع شده‌اند. |
| شعب و موجودی | `includes/Branches/BranchTaxonomy.php`، `InventoryRepository.php`، `BranchContext.php` | تفکیک Taxonomy/Repository/Context شروع خوبی است. Context هم‌زمان کوکی، قیمت، موجودی، سبد و سفارش را کنترل می‌کند و در نتیجه مرز دامنه گسترده شده است. |
| حالت‌های سفارش | `includes/OperationModes/ModeManager.php` و `TableQRGenerator.php` | سه حالت هدف در سند پیاده‌سازی شده‌اند و منطق WooCommerce در یک نقطه متمرکز است. |
| منو | `includes/Menu/FoodMeta.php`، `ModifiersEngine.php`، `CategoryOrder.php`، `HappyHour.php` | متای غذا، افزونه‌ها و اولویت دسته‌ها تفکیک فایل دارند، اما قرارداد مشترکِ DTO/validator ندارند. |
| چندزبانه | `includes/I18n/MultiLangManager.php` و `AiTranslator.php` | ماژول مستقل وجود دارد، ولی `MultiLangManager.php` با ۶۶۶ خط و مجموعهٔ بزرگ رشته‌های UI یک نقطهٔ تمرکز نگهداری است. |
| پنل PWA و API | `includes/FrontendDashboard/PortalRouter.php`، `AuthController.php`، `RestApiController.php`، `Views/portal-app.php` و `assets/js/portal-pwa.js` | Router، احراز هویت و API جدا هستند، اما Controller و View/JS بسیار بزرگ‌اند؛ منطق دسترسی شعبه در endpointها اعمال نشده است. |
| Elementor | `includes/Elementor/ElementorIntegration.php`، ۵ Dynamic Tag، ۶ Widget و `TemplateLibrary/TemplateManager.php` | ویجت‌های سند وجود دارند. Template manager ساختار Elementor را در PHP تولید می‌کند و فایل‌های JSONِ موجود را مصرف نمی‌کند. |

## انطباق با سند تحویل و فاصله‌های مهم

### هم‌راستایی‌های تأییدشده

- تمام فایل‌های هسته‌ای، شعب، حالت‌های کاری، منو، I18n، پنل، Widgetها و Dynamic Tagهای نام‌برده در بخش ساختار سند وجود دارند.
- جدول `{prefix}rmm_branch_inventory` با کلید یکتای `(product_id, branch_id)` در `includes/Core/Installer.php:57-89` و Repository آن در `includes/Branches/InventoryRepository.php` وجود دارد.
- namespace API واقعی `rmm/v1` در `RestApiController.php:10` ثبت شده و مسیر جداگانهٔ پنل توسط `PortalRouter` پیاده‌سازی شده است.
- منطق تم، مودال، فیلتر دسته، زبان و کارت‌های غذا در Widgetها و دارایی‌های فرانت موجود است؛ این بازبینی رفتار بصری مرورگر را اجرا نکرده است.

### فاصله‌ها و ناسازگاری‌ها

1. **قرارداد REST سند با کد یکی نیست.** سند `/portal/category-order` را برای GET و POST معرفی می‌کند، اما کد `/portal/categories` و `/portal/reorder-categories` را ثبت می‌کند (`RestApiController.php:60-71`). افزون بر آن، endpointهای عملیِ `stats`، مدیریت دسته و شعبه، رسید سفارش و ترجمهٔ AI در سند API فهرست نشده‌اند (`:18-141`). باید یک قرارداد نسخه‌دار OpenAPI یا جدول مرجع واحد ساخته و مصرف‌کنندهٔ PWA بر همان اساس تثبیت شود.
2. **کتابخانهٔ قالب‌ها با نقشهٔ فایل‌ها همسو نیست.** سند و ساختار مخزن `assets/templates-json/` شامل `oriental-kit.json`، `fast-food-kit.json`، `fine-dining-kit.json` و `app-style-kit.json` را نشان می‌دهند، اما هیچ ارجاعی از PHP/JS به این مسیر یافت نشد. `TemplateManager.php:15-45` کاتالوگ دیگری (`fast_food`، `fine_dining`، `app_style`، `traditional`) دارد و در `:150-220` دادهٔ Elementor را hard-code می‌کند. «Oriental» در کاتالوگ نیست.
3. **دارایی‌های اعلام‌شده اما مفقودند.** `TemplateManager.php:22,29,36,43` چهار preview را در `assets/images/` معرفی می‌کند و `PortalRouter.php:119,124` دو آیکون PWA را؛ پوشه/فایل‌های یادشده در بستهٔ فعلی وجود ندارند. نتیجه: previewها و آیکون‌های manifest در نصب واقعی 404 می‌شوند.
4. **پاکسازی uninstall با وعدهٔ «پاکسازی دیتابیس» کامل نیست.** `uninstall.php:14-33` فقط جدول موجودی و دو option را در صورت فعال‌بودن تنظیم پاک می‌کند. گزینهٔ ترتیب دسته (`rmm_category_order`)، متاهای `_rmm_*`، taxonomy/term meta، نقش‌های ایجادشده و داده‌های احتمالی دیگر باقی می‌مانند. قرارداد حذف داده باید صریح شود: نگهداری داده برای نصب مجدد یا پاکسازی کامل و قابل پیش‌بینی.
5. **هویت محصول و text domain یکدست نیست.** هدر افزونه `fondogh-online-menu.php:3-10` از `fondogh-online-menu` استفاده می‌کند، ولی `Plugin.php:26-31` و اکثر ترجمه‌ها `restaurant-menu-manager` را. این تفاوت بارگذاری ترجمه‌ها و نگهداری برند/بسته را شکننده می‌کند.

## وابستگی‌ها و نقاط پرخطر

| اولویت | شواهد | خطر و اثر |
| --- | --- | --- |
| P0 | `InventoryRepository.php:129-140` → `upsert()` در `:88-123` | `toggle_stock()` فقط وضعیت را می‌فرستد؛ `upsert()` برای قیمت‌های ارسال‌نشده `null` می‌گذارد. بنابراین تغییر موجودی می‌تواند قیمت اختصاصی شعبه را پاک کند. |
| P0 | `InventoryRepository.php:146-152` → `upsert()` در `:94-97` | `update_price()` فقط قیمت را می‌فرستد؛ `upsert()` به طور پیش‌فرض `stock_status=instock` و `is_available=1` می‌سازد. بنابراین ویرایش قیمت می‌تواند غذای ناموجود شعبه را موجود کند. |
| P0 | `RestApiController.php:144-145` + تمام routeهای تغییردهنده در `:32-140`؛ `AuthController.php:41-69` | `get_user_branches()` برای تخصیص شعبه وجود دارد اما در Controller استفاده نمی‌شود. کاربر مجاز پنل می‌تواند `branch_id` دلخواه را به endpointهای تغییر قیمت/موجودی/شعبه/سفارش بفرستد؛ جداسازی شعبه در سطح درخواست enforce نشده است. |
| P1 | `RestApiController.php:286-307` و `:322-345` | endpoint مخصوص شعبه، هم‌زمان موجودی یا قیمت سراسری WooCommerce محصول را هم تغییر می‌دهد. این تصمیم باعث نشت اثر یک شعبه به همهٔ شعب و شکستن وعدهٔ «کنترل مستقل» سند می‌شود. |
| P1 | `BranchContext.php:121-162` و `:107-110` | route عمومی GET با `permission_callback => __return_true` کوکی انتخاب شعبه را تغییر می‌دهد. GET نباید اثر جانبی داشته باشد؛ کوکی نیز `HttpOnly=false` است. انتخاب شعبه باید یا در کلاینت نگهداری شود یا endpointِ محافظت‌شده/CSRF-safe داشته باشد. |
| P1 | `AiTranslator.php:35-114` و تنظیم `gemini_api_key` در `Installer.php:164-165` | اتصال خارجی و کلید در optionهای عمومی افزونه به دامنهٔ تنظیمات تزریق شده‌اند. policy روشن برای نگهداری کلید، timeout/retry، سقف مصرف و ثبت خطای بدون افشای پاسخ سرویس لازم است. |
| P2 | `PortalRouter.php`، `Views/portal-app.php` (۸۲۹ خط)، `assets/js/portal-pwa.js` (۱۰۴KB)، `RestApiController.php` (بیش از ۹۰۰ خط) | لایهٔ PWA در سه فایل بزرگ با قرارداد ضمنی شکل گرفته است؛ تغییر یک Tab/endpoint احتمالاً به تغییر هم‌زمان PHP markup و JS و Controller نیاز دارد. |
| P2 | `MultiLangManager.php` (۶۶۶ خط)، `FoodMenuWidget.php` (۱۳۴۲ خط) | Widget و مدیر زبان هم API، هم rendering و هم policy را ترکیب می‌کنند؛ تست واحد و تغییر مستقل دشوار است. |
| P2 | `TemplateManager.php:94-132, 139-232` | JavaScript inline، HTML/CSS inline و ساخت template در یک کلاس قرار گرفته؛ همچنین `preview_img` تعریف شده اما در rendering استفاده نمی‌شود. |

## تکرارها و کدهای غیرماژولار

- عملیات CRUD مربوط به محصول، دسته، شعبه، سفارش و رسانه همگی در `RestApiController` هستند. هر resource باید Service/Policy/Request Validator خودش را داشته باشد و Controller صرفاً map کردن درخواست/پاسخ را انجام دهد.
- منطق مجوز عمومی فقط یک Boolean برمی‌گرداند (`check_permission()`)، در حالی که scope شعبه و قابلیت هر عملیات متفاوت است. Policyهای `can_manage_branch`، `can_edit_product`، `can_view_orders` و `can_upload_media` لازم‌اند.
- `BranchContext` هم وضعیت request، کوکی، فیلترهای WooCommerce، اعتبارسنجی سبد، محاسبهٔ قیمت و ذخیرهٔ order meta را دارد. جداکردن `BranchResolver`، `BranchPricing`، `BranchAvailability` و `BranchOrderMetadata` وابستگی‌های متقاطع را کاهش می‌دهد.
- منبع حقیقت قالب‌های Elementor دوگانه است: JSONهای بسته و ساختار PHP. یک منبع را انتخاب و دیگری را حذف/تولیدشونده کنید؛ در وضعیت فعلی هر دو نگهداری می‌خواهند.
- `scratch_icons.py`، `__pycache__/scratch_icons.cpython-312.pyc` و `code_artifact.md` فایل‌های توسعه/موقت در ریشه‌اند؛ چون این بازبینی مجاز به حذف نبود، فقط برای خروج از بستهٔ release علامت‌گذاری شده‌اند. `.agents/` و `.claude/` نیز نسخه‌های پرحجم و تکراری مهارت دارند و نباید وارد ZIP افزونه شوند.

## برنامهٔ اولویت‌بندی‌شدهٔ اصلاح

### P0 — قبل از هر توسعه یا انتشار

1. Repository موجودی را به updateهای جزئیِ پایدار تبدیل کنید: برای toggle فقط ستون‌های موجودی و برای price فقط ستون‌های قیمت را update کنید؛ یا قبل از upsert رکورد فعلی را merge کنید. برای هر دو سناریو تست رگرسیون بنویسید.
2. یک `BranchAccessPolicy` بسازید و در تک‌تک endpointها، branch ورودی را نسبت به `AuthController::get_user_branches()` و capability مخصوص عملیات کنترل کنید. branch assignment را در تمام queryهای محصول/سفارش هم اعمال کنید.
3. تصمیم دامنه‌ای را روشن و encode کنید: ویرایش قیمت/موجودی شعبه نباید در همان endpoint مقدار native و سراسری WooCommerce را تغییر دهد، مگر عملیات صریح «همگام‌سازی سراسری» با دسترسی بالاتر.
4. endpoint عمومیِ تغییر‌دهندهٔ کوکی را حذف اثر جانبی یا محافظت کنید و flagهای `Secure`، `HttpOnly` و `SameSite` کوکی را مطابق سناریوی واقعی تنظیم کنید.

### P1 — پیش از نسخهٔ بعدی

1. قرارداد REST نسخه‌دار بسازید؛ نام routeها، ورودی، خروجی، خطا و مجوز هر endpoint را در سند و کد هم‌زمان نگه دارید.
2. یک منبع رسمی برای starter templateها تعیین کنید. اگر JSON منبع است، importer آن را validate و مصرف کند؛ اگر PHP منبع است، JSONهای بلااستفاده را از بستهٔ release کنار بگذارید. Oriental را با همان نام سند وارد کاتالوگ کنید یا سند را اصلاح کنید.
3. `assets/images/` لازم را اضافه کنید یا ارجاعات preview/PWA icon را حذف/شرطی کنید. تست smoke باید پاسخ 200 دارایی‌های manifest را کنترل کند.
4. سیاست uninstall را مستند و پیاده‌سازی کنید؛ برای حذف کامل، optionها، metaها، term meta/taxonomy و roleهای فقط-افزونه را با احتیاط پاکسازی کنید.
5. text domain و slug را به یک مقدار تثبیت کنید و migration سازگار برای ترجمه‌ها/گزینه‌ها در نظر بگیرید.

### P2 — بهبود نگهداری و کیفیت انتشار

1. `RestApiController` را به controllerهای resource-specific و service/repositoryهای کوچک تقسیم کنید؛ DTO/validator مشترک برای درخواست‌ها بسازید.
2. PWA را به templateهای کوچک، ماژول‌های JavaScript و asset registration استاندارد وردپرس تقسیم کنید؛ API client جدا و قابل تست باشد.
3. `FoodMenuWidget` و `MultiLangManager` را به renderer/policy/data-provider تفکیک کنید.
4. تست‌های unit/integration برای Repository، Policy شعبه، routeها، lifecycle (activate/upgrade/uninstall) و smoke برای Elementor/WooCommerce اضافه کنید؛ سپس pipeline lint/test و manifest بستهٔ release بسازید.
5. فهرست ignore/manifest بستهٔ release ایجاد کنید تا artefactهای توسعه، `__pycache__` و پوشه‌های مهارت وارد ZIP نشوند. فایل‌های ریشهٔ توسعهٔ ذکرشده را فقط پس از تأیید مالک حذف یا به پوشهٔ ابزارهای توسعه منتقل کنید.

## موارد تأییدنشده

- نصب واقعی روی نسخه‌های هدف WordPress/PHP/WooCommerce/Elementor، فعال‌سازی/ارتقا و رفتار UI مرورگر اجرا نشده است.
- محتوای ZIP نهایی به‌صورت خودکار فهرست نشد؛ مسیر Unicode با ابزار archive محلی ناسازگار بود. برای انتشار، محتوای ZIP باید جداگانه باز و با manifest release تطبیق داده شود.
