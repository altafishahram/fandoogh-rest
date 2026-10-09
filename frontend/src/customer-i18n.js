export const languages = [
  { code: "fa", name: "فارسی", html_locale: "fa-IR", direction: "rtl" },
  { code: "en", name: "English", html_locale: "en-US", direction: "ltr" },
  { code: "zh", name: "简体中文", html_locale: "zh-CN", direction: "ltr" },
  { code: "tr", name: "Türkçe", html_locale: "tr-TR", direction: "ltr" },
];
const rows = [
  ["آشپزخانه و کافه", "Kitchen & café", "餐厅与咖啡馆", "Mutfak ve kafe"],
  ["سفارش آنلاین", "Online ordering", "在线点餐", "Çevrimiçi sipariş"],
  [
    "{count} دقیقه آماده‌سازی",
    "{count} min preparation",
    "准备约需 {count} 分钟",
    "{count} dakika hazırlık",
  ],
  ["از منوی ما", "From our menu", "来自我们的菜单", "Menümüzden"],
  ["همه", "All", "全部", "Tümü"],
  [
    "جزئیات {name}",
    "Details for {name}",
    "{name} 的详情",
    "{name} ayrıntıları",
  ],
  [
    "تصویر در دسترس نیست",
    "Image unavailable",
    "图片不可用",
    "Görsel mevcut değil",
  ],
  ["افزودن {name}", "Add {name}", "添加 {name}", "{name} ekle"],
  [
    "انتخاب گزینه برای {name}",
    "Choose an option for {name}",
    "为 {name} 选择规格",
    "{name} için seçenek seçin",
  ],
  ["پاک کردن فیلترها", "Clear filters", "清除筛选", "Filtreleri temizle"],
  [
    "تصویر {number} از {name}",
    "Image {number} of {name}",
    "{name} 的第 {number} 张图片",
    "{name} için {number}. görsel",
  ],
  [
    "تصویر {number} از {count}",
    "Image {number} of {count}",
    "第 {number} 张，共 {count} 张",
    "{count} görselden {number}. görsel",
  ],
  ["تصاویر {name}", "Images of {name}", "{name} 的图片", "{name} görselleri"],
  [
    "تصویر {current} از {total}",
    "Image {current} of {total}",
    "第 {current} 张，共 {total} 张",
    "{total} görselden {current}. görsel",
  ],
  ["تعداد", "Quantity", "数量", "Adet"],
  ["شعبه", "Branch", "分店", "Şube"],
  [
    "سبد پرداخت مربوط به شعبه دیگری است. آن را پاک کرده و با سفارش این شعبه جایگزین کنیم؟",
    "The checkout cart belongs to another branch. Clear it and replace it with this branch’s order?",
    "结算购物车属于另一家分店。清空并替换为本分店的订单吗？",
    "Ödeme sepeti başka bir şubeye ait. Silip bu şubenin siparişiyle değiştirelim mi?",
  ],
  ["آ", "A", "咖", "A"],
  ["کافه", "Cafe", "咖啡馆", "Kafe"],
  ["خوش آمدید", "Welcome", "欢迎光临", "Hoş geldiniz"],
  [
    "پیش‌نمایش نمایشی • سفارش و پرداخت واقعی انجام نمی‌شود",
    "Demo preview • no real orders or payments",
    "演示预览 • 不会产生真实订单或付款",
    "Demo önizleme • gerçek sipariş veya ödeme yapılmaz",
  ],
  ["پنل مدیریت ←", "Management panel →", "管理面板 →", "Yönetim paneli →"],
  [
    "قهوه، گفتگو، لحظه‌های خوب",
    "Coffee, conversation, good moments",
    "咖啡、交流与美好时光",
    "Kahve, sohbet, güzel anlar",
  ],
  [
    "منوی تازه در حال آماده شدن است…",
    "Preparing the fresh menu…",
    "正在准备菜单…",
    "Menü hazırlanıyor…",
  ],
  ["با عشق آماده می‌کنیم", "Made with care", "用心制作", "Özenle hazırlıyoruz"],
  [
    "یک مکث کوچک، یک حال خوب",
    "A little pause, a good moment",
    "小憩片刻，享受美好",
    "Küçük bir mola, güzel bir an",
  ],
  [
    "از عطر اولین قهوه تا شیرینی آخرین لقمه.",
    "From the first coffee aroma to the last sweet bite.",
    "从第一缕咖啡香到最后一口甜蜜。",
    "İlk kahvenin kokusundan son tatlı lokmaya.",
  ],
  [
    "انتخاب امروز شما چیست؟",
    "What will you choose today?",
    "今天想选什么？",
    "Bugün ne seçersiniz?",
  ],
  ["✦ دانه‌های تازه", "✦ Fresh beans", "✦ 新鲜咖啡豆", "✦ Taze çekirdekler"],
  [
    "◷ {count} دقیقه آماده‌سازی",
    "◷ {count} min preparation",
    "◷ 约 {count} 分钟准备",
    "◷ {count} dakika hazırlık",
  ],
  ["فضای کافه", "Cafe atmosphere", "咖啡馆环境", "Kafe ortamı"],
  [
    "طعم مورد علاقه‌تان را پیدا کنید",
    "Find your favorite flavor",
    "找到您喜爱的口味",
    "Sevdiğiniz lezzeti bulun",
  ],
  ["منوی کافه", "Cafe menu", "咖啡馆菜单", "Kafe menüsü"],
  ["جستجو در منو…", "Search the menu…", "搜索菜单…", "Menüde ara…"],
  ["جستجوی محصولات", "Search products", "搜索商品", "Ürün ara"],
  ["دسته‌بندی منو", "Menu categories", "菜单分类", "Menü kategorileri"],
  ["✦ همه", "✦ All", "✦ 全部", "✦ Tümü"],
  ["ناموجود", "Unavailable", "暂无供应", "Mevcut değil"],
  [
    "محصولی با این انتخاب پیدا نشد.",
    "No products match your selection.",
    "没有符合选择的商品。",
    "Seçiminize uygun ürün bulunamadı.",
  ],
  [
    "سفارش {number} ثبت شد",
    "Order {number} received",
    "订单 {number} 已收到",
    "{number} numaralı sipariş alındı",
  ],
  ["وضعیت: {stage}", "Status: {stage}", "状态：{stage}", "Durum: {stage}"],
  [
    "منتظر تأیید کارکنان",
    "Awaiting staff approval",
    "等待员工确认",
    "Personel onayı bekleniyor",
  ],
  ["تأیید شد", "Accepted", "已确认", "Onaylandı"],
  ["در حال آماده‌سازی", "Preparing", "制作中", "Hazırlanıyor"],
  ["آماده تحویل", "Ready", "可取餐", "Hazır"],
  ["تحویل شد", "Delivered", "已送达", "Teslim edildi"],
  ["لغو شد", "Cancelled", "已取消", "İptal edildi"],
  [
    "آماده‌سازی پس از تأیید کارکنان شروع می‌شود.",
    "Preparation starts after staff approval.",
    "员工确认后开始制作。",
    "Hazırlık personel onayından sonra başlar.",
  ],
  [
    "اینجا، برای لحظه‌های خوب شما.",
    "Here for your good moments.",
    "为您的美好时光而来。",
    "Güzel anlarınız için buradayız.",
  ],
  [
    "با آرامش انتخاب کنید، با عشق آماده می‌کنیم.",
    "Take your time. We prepare with care.",
    "慢慢挑选，我们用心准备。",
    "Rahatça seçin, özenle hazırlayalım.",
  ],
  [
    "{count} انتخاب شما",
    "Your {count} items",
    "已选 {count} 件",
    "Seçtiğiniz {count} ürün",
  ],
  ["سبد شما", "Your cart", "您的购物车", "Sepetiniz"],
  [
    "هنوز چیزی انتخاب نکرده‌اید.",
    "You have not selected anything yet.",
    "您尚未选择商品。",
    "Henüz bir ürün seçmediniz.",
  ],
  [
    "مشاهده {count} انتخاب",
    "View {count} items",
    "查看 {count} 件商品",
    "{count} ürünü görüntüle",
  ],
  ["جزئیات محصول", "Product details", "商品详情", "Ürün ayrıntıları"],
  ["سبد سفارش", "Order cart", "订单购物车", "Sipariş sepeti"],
  ["بستن", "Close", "关闭", "Kapat"],
  ["بستن خطا", "Dismiss error", "关闭错误提示", "Hatayı kapat"],
  ["یک انتخاب خوش‌طعم", "A delicious choice", "美味之选", "Lezzetli bir seçim"],
  ["انتخاب نوع", "Choose an option", "选择规格", "Seçenek seçin"],
  ["انتخاب کنید", "Please select", "请选择", "Lütfen seçin"],
  ["افزودن به انتخاب‌ها", "Add to cart", "加入购物车", "Sepete ekle"],
  [
    "در حال حاضر ناموجود",
    "Currently unavailable",
    "当前暂无供应",
    "Şu anda mevcut değil",
  ],
  [
    "کمی تا یک حال خوب",
    "Good moments are close",
    "美好时光即将到来",
    "Güzel anlar çok yakın",
  ],
  ["انتخاب‌های شما", "Your selections", "您的选择", "Seçimleriniz"],
  [
    "سبد شما خالی است.",
    "Your cart is empty.",
    "您的购物车为空。",
    "Sepetiniz boş.",
  ],
  ["کاهش {name}", "Decrease {name}", "减少 {name}", "{name} azalt"],
  ["افزایش {name}", "Increase {name}", "增加 {name}", "{name} artır"],
  ["نوع سفارش", "Order type", "订单类型", "Sipariş türü"],
  ["سرو در {table}", "Serve at {table}", "送至 {table}", "{table} için servis"],
  ["تحویل حضوری", "Pickup", "到店自取", "Gel-al"],
  ["ارسال با پیک", "Delivery", "配送", "Teslimat"],
  [
    "نام شما (اختیاری)",
    "Your name (optional)",
    "您的姓名（选填）",
    "Adınız (isteğe bağlı)",
  ],
  ["توضیحات سفارش", "Order notes", "订单备注", "Sipariş notu"],
  [
    "مثلاً بدون شکر…",
    "For example, no sugar…",
    "例如：不加糖…",
    "Örneğin, şekersiz…",
  ],
  ["جمع انتخاب‌ها", "Item subtotal", "商品小计", "Ürün ara toplamı"],
  [
    "پس از تأیید کارکنان، سفارش آماده می‌شود. پرداخت با صندوق.",
    "Prepared after staff approval. Pay at the counter.",
    "员工确认后制作，请在收银台付款。",
    "Personel onayından sonra hazırlanır. Kasada ödeme yapın.",
  ],
  [
    "هزینه ارسال، مالیات و پرداخت در صفحه امن فروشگاه محاسبه می‌شود.",
    "Shipping, taxes and payment are calculated at secure checkout.",
    "配送费、税费及付款将在安全结账页面计算。",
    "Teslimat, vergi ve ödeme güvenli ödeme sayfasında hesaplanır.",
  ],
  ["در حال ثبت…", "Submitting…", "正在提交…", "Gönderiliyor…"],
  ["ارسال برای تأیید", "Send for approval", "提交确认", "Onaya gönder"],
  ["ادامه و پرداخت", "Continue to checkout", "继续结账", "Ödemeye devam et"],
  ["زبان منو", "Menu language", "菜单语言", "Menü dili"],
  [
    "لطفاً یک گزینه انتخاب کنید",
    "Please choose an option",
    "请选择规格",
    "Lütfen bir seçenek seçin",
  ],
  [
    "پرداخت واقعی در پیش‌نمایش در دسترس نیست.",
    "Real payment is unavailable in this preview.",
    "演示中无法进行真实付款。",
    "Bu önizlemede gerçek ödeme yapılamaz.",
  ],
  [
    "دریافت سبد ووکامرس ناموفق بود",
    "Could not load the store cart",
    "无法加载商店购物车",
    "Mağaza sepeti yüklenemedi",
  ],
  [
    "افزودن به سبد ووکامرس ناموفق بود",
    "Could not update the store cart",
    "无法更新商店购物车",
    "Mağaza sepeti güncellenemedi",
  ],
  [
    "فونت سفارشی بارگذاری نشد؛ فونت پیش‌فرض فعال است.",
    "Custom font could not load; the default font is active.",
    "自定义字体无法加载，已使用默认字体。",
    "Özel yazı tipi yüklenemedi; varsayılan yazı tipi kullanılıyor.",
  ],
  [
    "پاسخ سرور معتبر نیست",
    "The server response is invalid",
    "服务器响应无效",
    "Sunucu yanıtı geçersiz",
  ],
  [
    "ارتباط با سرور ناموفق بود",
    "Could not connect to the server",
    "无法连接服务器",
    "Sunucuya bağlanılamadı",
  ],
];
export const dictionaries = Object.fromEntries(
  languages.map((l, index) => [
    l.code,
    Object.fromEntries(rows.map((row) => [row[0], row[index]])),
  ]),
);
export function translateCustomer(
  message,
  language = "fa",
  params = {},
  serverStrings = {},
) {
  const pattern =
    serverStrings[message] ||
    dictionaries[language]?.[message] ||
    dictionaries.fa[message] ||
    message;
  return pattern.replace(/\{([a-zA-Z_]+)\}/g, (all, key) =>
    params[key] === undefined ? all : String(params[key]),
  );
}
export function selectLanguage({
  url = "",
  saved = "",
  defaultLanguage = "fa",
  enabled = ["fa", "en", "zh", "tr"],
} = {}) {
  let explicit = "";
  try {
    explicit = new URL(url, "http://localhost").searchParams.get("lang") || "";
  } catch {}
  return (
    [explicit, saved, defaultLanguage, ...enabled].find((code) =>
      enabled.includes(code),
    ) || "fa"
  );
}
export function translatedEntity(entity, language) {
  if (!entity) return entity;
  const translated = entity.translations?.[language] || {};
  return {
    ...entity,
    ...Object.fromEntries(
      Object.entries(translated).filter(
        ([, value]) => typeof value === "string" && value.trim(),
      ),
    ),
  };
}
export function cartLabel(item, products, language) {
  const product = products.find((p) => p.id === item.product_id);
  if (!product) return item.name;
  const p = translatedEntity(product, language),
    variant = product.variations?.find((v) => v.id === item.variation_id);
  return (
    p.name + (variant ? " • " + translatedEntity(variant, language).name : "")
  );
}
export function localeNumber(value, locale = "fa-IR", options = {}) {
  const normalized = String(value)
    .replace(/[۰-۹]/g, (c) => String("۰۱۲۳۴۵۶۷۸۹".indexOf(c)))
    .replace(/[٠-٩]/g, (c) => String("٠١٢٣٤٥٦٧٨٩".indexOf(c)));
  const numeric = Number(normalized);
  return Number.isFinite(numeric)
    ? new Intl.NumberFormat(locale, options).format(numeric)
    : String(value);
}
