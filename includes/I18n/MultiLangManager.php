<?php
namespace RMM\I18n;

if (!defined('ABSPATH')) {
    exit;
}

class MultiLangManager {

    const LANG_COOKIE = 'rmm_selected_lang';
    const CHOSEN_COOKIE = 'rmm_lang_chosen';
    private static $current_lang = null;

    public function __construct() {
        add_action('init', [$this, 'init_language']);
        add_filter('the_title', [$this, 'filter_post_title'], 10, 2);
        add_filter('woocommerce_product_get_name', [$this, 'filter_product_title'], 10, 2);
        add_filter('woocommerce_product_get_short_description', [$this, 'filter_product_description'], 10, 2);
        add_filter('woocommerce_currency_symbol', [$this, 'filter_currency_symbol'], 10, 2);
        add_filter('body_class', [$this, 'add_language_body_class']);
        add_action('wp_footer', [$this, 'render_entrance_popup']);
    }

    /**
     * Complete catalog of supported languages
     */
    public static function get_all_languages() {
        return [
            'fa' => [
                'code'      => 'fa',
                'name'      => __('فارسی', 'restaurant-menu-manager'),
                'native'    => 'فارسی',
                'flag'      => '🇮🇷',
                'direction' => 'rtl',
                'locale'    => 'fa_IR',
            ],
            'en' => [
                'code'      => 'en',
                'name'      => __('انگلیسی', 'restaurant-menu-manager'),
                'native'    => 'English',
                'flag'      => '🇬🇧',
                'direction' => 'ltr',
                'locale'    => 'en_US',
            ],
            'ar' => [
                'code'      => 'ar',
                'name'      => __('عربی', 'restaurant-menu-manager'),
                'native'    => 'العربية',
                'flag'      => '🇸🇦',
                'direction' => 'rtl',
                'locale'    => 'ar',
            ],
            'tr' => [
                'code'      => 'tr',
                'name'      => __('ترکی', 'restaurant-menu-manager'),
                'native'    => 'Türkçe',
                'flag'      => '🇹🇷',
                'direction' => 'ltr',
                'locale'    => 'tr_TR',
            ],
            'fr' => [
                'code'      => 'fr',
                'name'      => __('فرانسوی', 'restaurant-menu-manager'),
                'native'    => 'Français',
                'flag'      => '🇫🇷',
                'direction' => 'ltr',
                'locale'    => 'fr_FR',
            ],
            'de' => [
                'code'      => 'de',
                'name'      => __('آلمانی', 'restaurant-menu-manager'),
                'native'    => 'Deutsch',
                'flag'      => '🇩🇪',
                'direction' => 'ltr',
                'locale'    => 'de_DE',
            ],
            'ru' => [
                'code'      => 'ru',
                'name'      => __('روسی', 'restaurant-menu-manager'),
                'native'    => 'Русский',
                'flag'      => '🇷🇺',
                'direction' => 'ltr',
                'locale'    => 'ru_RU',
            ],
            'zh' => [
                'code'      => 'zh',
                'name'      => __('چینی', 'restaurant-menu-manager'),
                'native'    => '中文',
                'flag'      => '🇨🇳',
                'direction' => 'ltr',
                'locale'    => 'zh_CN',
            ],
        ];
    }

    /**
     * Get enabled languages configured in plugin settings
     */
    public static function get_enabled_languages() {
        $all = self::get_all_languages();
        $enabled_codes = \RMM\Core\Settings::get('enabled_languages', ['fa', 'en']);

        if (!is_array($enabled_codes) || empty($enabled_codes)) {
            $enabled_codes = ['fa', 'en'];
        }

        // Always make sure default language is included
        $default = \RMM\Core\Settings::get('default_language', 'fa');
        if (!in_array($default, $enabled_codes)) {
            array_unshift($enabled_codes, $default);
        }

        $result = [];
        foreach ($enabled_codes as $code) {
            if (isset($all[$code])) {
                $result[$code] = $all[$code];
            }
        }

        return !empty($result) ? $result : ['fa' => $all['fa']];
    }

    /**
     * Resolve active language: URL param -> Cookie -> WPML/Polylang -> Default
     */
    public static function get_current_language() {
        if (self::$current_lang !== null) {
            return self::$current_lang;
        }

        $enabled = array_keys(self::get_enabled_languages());

        // 1. URL Query parameter ?lang=en
        if (!empty($_GET['lang'])) {
            $lang = sanitize_key($_GET['lang']);
            if (in_array($lang, $enabled)) {
                self::$current_lang = $lang;
                self::set_cookie($lang);
                return self::$current_lang;
            }
        }

        // 2. Cookie
        if (!empty($_COOKIE[self::LANG_COOKIE])) {
            $cookie_lang = sanitize_key($_COOKIE[self::LANG_COOKIE]);
            if (in_array($cookie_lang, $enabled)) {
                self::$current_lang = $cookie_lang;
                return self::$current_lang;
            }
        }

        // 3. WPML / Polylang compatibility
        if (function_exists('pll_current_language')) {
            $pll_lang = pll_current_language();
            if ($pll_lang && in_array($pll_lang, $enabled)) {
                self::$current_lang = $pll_lang;
                return self::$current_lang;
            }
        }

        if (defined('ICL_LANGUAGE_CODE') && in_array(ICL_LANGUAGE_CODE, $enabled)) {
            self::$current_lang = ICL_LANGUAGE_CODE;
            return self::$current_lang;
        }

        // 4. Default from plugin settings
        $default = \RMM\Core\Settings::get('default_language', 'fa');
        self::$current_lang = in_array($default, $enabled) ? $default : ($enabled[0] ?? 'fa');
        return self::$current_lang;
    }

    public static function set_cookie($lang) {
        if (!headers_sent()) {
            setcookie(self::LANG_COOKIE, sanitize_key($lang), time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false);
        }
    }

    public static function is_rtl() {
        $lang = self::get_current_language();
        $all = self::get_all_languages();
        return isset($all[$lang]) ? ($all[$lang]['direction'] === 'rtl') : true;
    }

    public function init_language() {
        self::get_current_language();
    }

    /**
     * Get translated field for product
     */
    public static function get_product_translation($product_id, $field = 'name', $lang = null) {
        if (!$lang) {
            $lang = self::get_current_language();
        }

        if ($lang === 'fa') {
            return '';
        }

        $translations = get_post_meta($product_id, '_rmm_translations', true);
        if (is_array($translations) && isset($translations[$lang][$field]) && !empty($translations[$lang][$field])) {
            return $translations[$lang][$field];
        }

        // Legacy fallback
        if ($lang === 'en') {
            if ($field === 'name') {
                return get_post_meta($product_id, '_rmm_title_en', true);
            }
            if ($field === 'desc') {
                return get_post_meta($product_id, '_rmm_desc_en', true);
            }
        }

        return '';
    }

    /**
     * Get translated name for category / taxonomy term
     */
    public static function get_term_translation($term_id, $lang = null) {
        if (!$lang) {
            $lang = self::get_current_language();
        }

        if ($lang === 'fa') {
            return '';
        }

        $trans = get_term_meta($term_id, '_rmm_translations', true);
        if (is_array($trans) && !empty($trans[$lang]['name'])) {
            return $trans[$lang]['name'];
        }

        if ($lang === 'en') {
            $en = get_term_meta($term_id, '_rmm_title_en', true);
            if (!empty($en)) {
                return $en;
            }
        }

        return '';
    }

    /**
     * Check if current request is REST API or admin/portal management
     */
    public static function is_rest_or_admin() {
        if (is_admin() && !wp_doing_ajax()) {
            return true;
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }
        if (!empty($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, '/wp-json/') !== false || strpos($uri, 'rest_route=') !== false) {
                return true;
            }
            $slug = \RMM\Core\Settings::get('dashboard_slug', 'restaurant-portal');
            if (!empty($slug) && strpos($uri, '/' . $slug) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Filter WordPress post title for products in the loop
     */
    public function filter_post_title($title, $id = 0) {
        if (self::is_rest_or_admin()) {
            return $title;
        }

        if (!$id) {
            return $title;
        }

        $lang = self::get_current_language();
        if ($lang !== 'fa' && get_post_type($id) === 'product') {
            $translated = self::get_product_translation($id, 'name', $lang);
            if (!empty($translated)) {
                return $translated;
            }
        }

        return $title;
    }

    /**
     * Filter WooCommerce product name for native secondary language
     */
    public function filter_product_title($name, $product) {
        if (self::is_rest_or_admin()) {
            return $name;
        }

        $lang = self::get_current_language();
        if ($lang !== 'fa' && $product) {
            $p_id = is_object($product) ? $product->get_id() : $product;
            $translated = self::get_product_translation($p_id, 'name', $lang);
            if (!empty($translated)) {
                return $translated;
            }
        }

        return $name;
    }

    /**
     * Filter WooCommerce short description for native secondary language
     */
    public function filter_product_description($desc, $product) {
        if (self::is_rest_or_admin()) {
            return $desc;
        }

        $lang = self::get_current_language();
        if ($lang !== 'fa' && $product) {
            $p_id = is_object($product) ? $product->get_id() : $product;
            $translated = self::get_product_translation($p_id, 'desc', $lang);
            if (!empty($translated)) {
                return $translated;
            }
        }

        return $desc;
    }

    /**
     * Translate currency symbol (Toman) for active language
     */
    public function filter_currency_symbol($symbol, $currency) {
        if (self::is_rest_or_admin()) {
            return $symbol;
        }

        $lang = self::get_current_language();
        if ($lang === 'fa') {
            return $symbol;
        }

        return self::get_currency_symbol_for_lang($lang);
    }

    /**
     * Get currency symbol for a specific language
     */
    public static function get_currency_symbol_for_lang($lang = null) {
        if (!$lang) {
            $lang = self::get_current_language();
        }

        $map = [
            'fa' => 'تومان',
            'en' => 'Toman',
            'ar' => 'تومان',
            'tr' => 'Tümen',
            'fr' => 'Tomans',
            'de' => 'Toman',
            'ru' => 'Туман',
            'zh' => '图曼',
        ];

        return $map[$lang] ?? 'Toman';
    }

    /**
     * Multi-language UI Strings Dictionary for Front-end Elements
     */
    public static function get_all_ui_strings($lang = null) {
        if (!$lang) {
            $lang = self::get_current_language();
        }

        $dictionary = [
            'fa' => [
                'all_categories'    => 'همه دسته‌ها',
                'search_placeholder'=> 'جستجوی غذا...',
                'digital_menu'      => 'منو دیجیتال',
                'minutes'           => 'دقیقه',
                'calories'          => 'کالری',
                'calories_label'    => 'ارزش غذایی و کالری:',
                'kcal'              => 'کیلوکالری',
                'category'          => 'دسته‌بندی:',
                'ingredients'       => 'ترکیبات و مواد اولیه:',
                'view_image'        => 'مشاهده تصویر بزرگ‌تر',
                'close'             => 'بستن',
                'grid_view'         => 'نمایش شبکه‌ای',
                'list_view'         => 'نمایش لیستی',
                'out_of_stock'      => 'ناموجود در این شعبه',
                'order_whatsapp'    => 'سفارش در واتساپ',
                'call_order'        => 'تماس تلفنی',
                'choose_options'    => 'انتخاب مخلفات',
                'add_to_cart'       => 'افزودن به سبد',
                'view_food'         => 'مشاهده غذا',
                'portions'          => 'انتخاب پرس / سایز',
                'addons'            => 'مخلفات و افزودنی‌های دلخواه',
                'quantity'          => 'تعداد',
                'kitchen_note'      => 'یادداشت برای آشپزخانه',
                'note_placeholder'  => 'مثلاً: کم نمک، بدون پیاز...',
                'total_price'       => 'مبلغ کل سفارش',
                'submit_order'      => 'تایید و افزودن به سبد خرید',
                'loading'           => 'در حال بارگذاری...',
                'server_error'      => 'خطای ارتباط با سرور',
                'added_to_cart'     => 'به سبد سفارش اضافه شد',
            ],
            'en' => [
                'all_categories'    => 'All Categories',
                'search_placeholder'=> 'Search food...',
                'digital_menu'      => 'Digital Menu',
                'minutes'           => 'mins',
                'calories'          => 'kcal',
                'calories_label'    => 'Calories & Nutrition:',
                'kcal'              => 'kcal',
                'category'          => 'Category:',
                'ingredients'       => 'Ingredients:',
                'view_image'        => 'View Full Image',
                'close'             => 'Close',
                'grid_view'         => 'Grid View',
                'list_view'         => 'List View',
                'out_of_stock'      => 'Out of stock in this branch',
                'order_whatsapp'    => 'Order via WhatsApp',
                'call_order'        => 'Call to Order',
                'choose_options'    => 'Choose Options',
                'add_to_cart'       => 'Add to Cart',
                'view_food'         => 'View Details',
                'portions'          => 'Choose Size / Portion',
                'addons'            => 'Extras & Add-ons',
                'quantity'          => 'Quantity',
                'kitchen_note'      => 'Special Kitchen Note',
                'note_placeholder'  => 'e.g. Less salt, no onion...',
                'total_price'       => 'Total Amount',
                'submit_order'      => 'Confirm & Add to Cart',
                'loading'           => 'Loading...',
                'server_error'      => 'Server communication error',
                'added_to_cart'     => 'Added to your order',
            ],
            'ar' => [
                'all_categories'    => 'جميع الأقسام',
                'search_placeholder'=> 'البحث عن وجبة...',
                'digital_menu'      => 'قائمة رقمية',
                'minutes'           => 'دقيقة',
                'calories'          => 'سعرة حرارية',
                'calories_label'    => 'القيمة الغذائية والسعرات:',
                'kcal'              => 'سعرة',
                'category'          => 'الفئة:',
                'ingredients'       => 'المكونات والمقادير:',
                'view_image'        => 'عرض الصورة كاملة',
                'close'             => 'إغلاق',
                'grid_view'         => 'عرض شبكي',
                'list_view'         => 'عرض طولي',
                'out_of_stock'      => 'غير متوفر في هذا الفرع',
                'order_whatsapp'    => 'الطلب عبر واتساب',
                'call_order'        => 'الاتصال للطلب',
                'choose_options'    => 'اختر الإضافات',
                'add_to_cart'       => 'أضف إلى الطلب',
                'view_food'         => 'عرض التفاصيل',
                'portions'          => 'اختر الحجم / الحصة',
                'addons'            => 'الإضافات والمقبلات',
                'quantity'          => 'الكمية',
                'kitchen_note'      => 'ملاحظات للمطبخ',
                'note_placeholder'  => 'مثلاً: ملح قليل، بدون بصل...',
                'total_price'       => 'المبلغ الإجمالي',
                'submit_order'      => 'تأكيد وإضافة للسلة',
                'loading'           => 'جاري التحميل...',
                'server_error'      => 'خطأ في الاتصال بالخادم',
                'added_to_cart'     => 'تمت الإضافة للطلب',
            ],
            'tr' => [
                'all_categories'    => 'Tüm Kategoriler',
                'search_placeholder'=> 'Yemek ara...',
                'digital_menu'      => 'Dijital Menü',
                'minutes'           => 'dk',
                'calories'          => 'kalori',
                'calories_label'    => 'Kalori ve Besin Değeri:',
                'kcal'              => 'kcal',
                'category'          => 'Kategori:',
                'ingredients'       => 'İçindekiler:',
                'view_image'        => 'Görseli Büyüt',
                'close'             => 'Kapat',
                'grid_view'         => 'Kılavuz Görünümü',
                'list_view'         => 'Liste Görünümü',
                'out_of_stock'      => 'Bu şubede tükendi',
                'order_whatsapp'    => 'WhatsApp ile Sipariş',
                'call_order'        => 'Telefonla Sipariş',
                'choose_options'    => 'Seçenekleri Belirle',
                'add_to_cart'       => 'Sepete Ekle',
                'view_food'         => 'Detayları Gör',
                'portions'          => 'Porsiyon / Boyut Seç',
                'addons'            => 'Ekstralar ve Garnitürler',
                'quantity'          => 'Adet',
                'kitchen_note'      => 'Mutfak Notu',
                'note_placeholder'  => 'Örn: az tuzlu, soğansız...',
                'total_price'       => 'Toplam Tutar',
                'submit_order'      => 'Onayla ve Sepete Ekle',
                'loading'           => 'Yükleniyor...',
                'server_error'      => 'Sunucu iletişim hatası',
                'added_to_cart'     => 'Sepete eklendi',
            ],
            'fr' => [
                'all_categories'    => 'Toutes Catégories',
                'search_placeholder'=> 'Rechercher un plat...',
                'digital_menu'      => 'Menu Digital',
                'minutes'           => 'min',
                'calories'          => 'kcal',
                'calories_label'    => 'Valeur nutritive & calories :',
                'kcal'              => 'kcal',
                'category'          => 'Catégorie :',
                'ingredients'       => 'Ingrédients :',
                'view_image'        => 'Agrandir l’image',
                'close'             => 'Fermer',
                'grid_view'         => 'Vue Grille',
                'list_view'         => 'Vue Liste',
                'out_of_stock'      => 'Épuisé dans cette succursale',
                'order_whatsapp'    => 'Commander par WhatsApp',
                'call_order'        => 'Appeler pour commander',
                'choose_options'    => 'Choisir les options',
                'add_to_cart'       => 'Ajouter au panier',
                'view_food'         => 'Voir les détails',
                'portions'          => 'Choisir la portion',
                'addons'            => 'Suppléments & Extras',
                'quantity'          => 'Quantité',
                'kitchen_note'      => 'Note pour la cuisine',
                'note_placeholder'  => 'Ex : peu salé, sans oignon...',
                'total_price'       => 'Montant Total',
                'submit_order'      => 'Confirmer & Ajouter',
                'loading'           => 'Chargement...',
                'server_error'      => 'Erreur de communication',
                'added_to_cart'     => 'Ajouté à la commande',
            ],
            'de' => [
                'all_categories'    => 'Alle Kategorien',
                'search_placeholder'=> 'Gericht suchen...',
                'digital_menu'      => 'Digitales Menü',
                'minutes'           => 'Min',
                'calories'          => 'kcal',
                'calories_label'    => 'Nährwerte & Kalorien:',
                'kcal'              => 'kcal',
                'category'          => 'Kategorie:',
                'ingredients'       => 'Zutaten:',
                'view_image'        => 'Bild vergrößern',
                'close'             => 'Schließen',
                'grid_view'         => 'Rasteransicht',
                'list_view'         => 'Listenansicht',
                'out_of_stock'      => 'In dieser Filiale ausverkauft',
                'order_whatsapp'    => 'Per WhatsApp bestellen',
                'call_order'        => 'Telefonisch bestellen',
                'choose_options'    => 'Optionen wählen',
                'add_to_cart'       => 'In den Warenkorb',
                'view_food'         => 'Details ansehen',
                'portions'          => 'Portion wählen',
                'addons'            => 'Extras & Beilagen',
                'quantity'          => 'Menge',
                'kitchen_note'      => 'Hinweis für die Küche',
                'note_placeholder'  => 'z.B. wenig Salz, ohne Zwiebeln...',
                'total_price'       => 'Gesamtbetrag',
                'submit_order'      => 'Bestätigen & Hinzufügen',
                'loading'           => 'Wird geladen...',
                'server_error'      => 'Serverfehler',
                'added_to_cart'     => 'Hinzugefügt',
            ],
            'ru' => [
                'all_categories'    => 'Все категории',
                'search_placeholder'=> 'Поиск блюда...',
                'digital_menu'      => 'Цифровое меню',
                'minutes'           => 'мин',
                'calories'          => 'ккал',
                'calories_label'    => 'Пищевая ценность и калории:',
                'kcal'              => 'ккал',
                'category'          => 'Категория:',
                'ingredients'       => 'Ингредиенты:',
                'view_image'        => 'Увеличить фото',
                'close'             => 'Закрыть',
                'grid_view'         => 'Сетка',
                'list_view'         => 'Список',
                'out_of_stock'      => 'Нет в наличии в этом филиале',
                'order_whatsapp'    => 'Заказ через WhatsApp',
                'call_order'        => 'Позвонить для заказа',
                'choose_options'    => 'Выбрать опции',
                'add_to_cart'       => 'В корзину',
                'view_food'         => 'Подробнее',
                'portions'          => 'Выбрать порцию',
                'addons'            => 'Добавки и гарниры',
                'quantity'          => 'Количество',
                'kitchen_note'      => 'Примечание для кухни',
                'note_placeholder'  => 'Например: меньше соли, без лука...',
                'total_price'       => 'Итоговая сумма',
                'submit_order'      => 'Подтвердить и добавить',
                'loading'           => 'Загрузка...',
                'server_error'      => 'Ошибка сервера',
                'added_to_cart'     => 'Добавлено к заказу',
            ],
            'zh' => [
                'all_categories'    => '所有分类',
                'search_placeholder'=> '搜索菜品...',
                'digital_menu'      => '电子菜单',
                'minutes'           => '分钟',
                'calories'          => '卡路里',
                'calories_label'    => '营养成分与热量：',
                'kcal'              => '千卡',
                'category'          => '分类：',
                'ingredients'       => '主要成分与配料：',
                'view_image'        => '查看大图',
                'close'             => '关闭',
                'grid_view'         => '网格视图',
                'list_view'         => '列表视图',
                'out_of_stock'      => '本店缺货',
                'order_whatsapp'    => '通过WhatsApp点餐',
                'call_order'        => '电话点餐',
                'choose_options'    => '选择配料',
                'add_to_cart'       => '加入订单',
                'view_food'         => '查看详情',
                'portions'          => '选择份量',
                'addons'            => '加料与配菜',
                'quantity'          => '数量',
                'kitchen_note'      => '厨房备注',
                'note_placeholder'  => '例如：少盐，不要洋葱...',
                'total_price'       => '总金额',
                'submit_order'      => '确认并加入订单',
                'loading'           => '加载中...',
                'server_error'      => '服务器连接错误',
                'added_to_cart'     => '已添加到订单',
            ],
        ];

        return $dictionary[$lang] ?? $dictionary['fa'];
    }

    /**
     * Get single localized UI string
     */
    public static function get_ui_string($key, $lang = null) {
        $strings = self::get_all_ui_strings($lang);
        return $strings[$key] ?? $key;
    }

    public function add_language_body_class($classes) {
        $lang = self::get_current_language();
        $classes[] = 'rmm-lang-' . sanitize_html_class($lang);
        $classes[] = self::is_rtl() ? 'rmm-rtl' : 'rmm-ltr';
        return $classes;
    }

    /**
     * Render Entrance Language Selection Popup Modal
     */
    public function render_entrance_popup() {
        if (\RMM\Core\Settings::get('enable_lang_entrance_popup', 'no') !== 'yes') {
            return;
        }

        // Only render on frontend
        if (is_admin()) {
            return;
        }

        $enabled_languages = self::get_enabled_languages();
        if (count($enabled_languages) < 2) {
            return;
        }

        $current_lang = self::get_current_language();
        ?>
        <div id="rmm-entrance-modal" class="rmm-entrance-modal-backdrop" style="display:none;" data-current="<?php echo esc_attr($current_lang); ?>">
            <div class="rmm-entrance-card">
                <button type="button" class="rmm-entrance-close" id="rmm-entrance-close-btn" aria-label="Close">&times;</button>
                <div class="rmm-entrance-header">
                    <div class="rmm-entrance-icon">🍽️</div>
                    <h3 class="rmm-entrance-title"><?php _e('زبان خود را انتخاب کنید', 'restaurant-menu-manager'); ?></h3>
                    <p class="rmm-entrance-subtitle">Select your preferred language / اختر لغتك المفضلة</p>
                </div>

                <div class="rmm-entrance-grid">
                    <?php foreach ($enabled_languages as $code => $data) : ?>
                        <button type="button" class="rmm-entrance-lang-btn <?php echo ($code === $current_lang) ? 'is-active' : ''; ?>" data-lang="<?php echo esc_attr($code); ?>">
                            <span class="rmm-entrance-flag"><?php echo esc_html($data['flag']); ?></span>
                            <div class="rmm-entrance-lang-info">
                                <span class="rmm-entrance-lang-native"><?php echo esc_html($data['native']); ?></span>
                                <span class="rmm-entrance-lang-name"><?php echo esc_html($data['name']); ?></span>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="rmm-entrance-footer">
                    <label class="rmm-remember-checkbox">
                        <input type="checkbox" id="rmm-entrance-remember" checked>
                        <span><?php _e('به خاطر سپردن انتخاب من (Remember choice)', 'restaurant-menu-manager'); ?></span>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render a clean Language Switcher HTML
     */
    public static function render_language_switcher($style = 'pills') {
        $enabled = self::get_enabled_languages();
        if (count($enabled) < 2) {
            return '';
        }

        $current = self::get_current_language();
        $base_url = remove_query_arg('lang');

        ob_start();
        ?>
        <div class="rmm-lang-switcher rmm-lang-switcher-<?php echo esc_attr($style); ?>" style="display: inline-flex; align-items: center; gap: 4px; background: rgba(241,245,249,0.9); padding: 3px 6px; border-radius: 20px; backdrop-filter: blur(4px);">
            <?php foreach ($enabled as $code => $data) : ?>
                <a href="<?php echo esc_url(add_query_arg('lang', $code, $base_url)); ?>" 
                   class="rmm-lang-btn <?php echo ($current === $code) ? 'active' : ''; ?>" 
                   style="padding: 3px 8px; border-radius: 12px; font-size: 12px; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s ease; <?php echo ($current === $code) ? 'background: #0284c7; color: #fff;' : 'color: #64748b;'; ?>"
                   data-lang="<?php echo esc_attr($code); ?>"
                   title="<?php echo esc_attr($data['name']); ?>">
                    <span><?php echo esc_html($data['flag']); ?></span>
                    <span><?php echo esc_html($data['native']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
