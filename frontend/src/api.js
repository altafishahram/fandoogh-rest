import { languages, translatedEntity } from "./customer-i18n.js";
export function api(config) {
  let token = "",
    branchId = Number(config.branchId) || 0,
    generation = 0;
  return {
    setToken: (v) => (token = v),
    invalidate: () => generation++,
    setBranch(id) {
      const next = Number(id) || 0;
      if (next !== branchId) {
        branchId = next;
        generation++;
      }
    },
    async request(path, method = "GET", body) {
      const requestGeneration = generation,
        requestBranch = branchId;
      if (requestBranch) {
        const [route, query = ""] = path.split("?");
        const params = new URLSearchParams(query);
        params.set("branch_id", requestBranch);
        path = route + "?" + params.toString();
        if (body && !(body instanceof FormData))
          body = { ...body, branch_id: requestBranch };
      }
      function current() {
        if (requestGeneration !== generation) {
          const error = new Error("پاسخ شعبه قبلی کنار گذاشته شد");
          error.code = "stale_branch";
          throw error;
        }
      }
      if (config.demo === true) {
        const result = await demo(path, method, body);
        current();
        return result;
      }
      const headers = {};
      if (config.nonce) headers["X-WP-Nonce"] = config.nonce;
      if (token) headers["X-AdminCafe-Token"] = token;
      if (body && !(body instanceof FormData))
        headers["Content-Type"] = "application/json";
      const response = await fetch(
        `${config.apiBase.replace(/\/$/, "")}${path}`,
        {
          method,
          credentials: "same-origin",
          cache: "no-store",
          headers,
          body: body
            ? body instanceof FormData
              ? body
              : JSON.stringify(body)
            : undefined,
        },
      );
      const data = await response
        .json()
        .catch(() => ({ message: "پاسخ سرور معتبر نیست" }));
      current();
      if (!response.ok) {
        const error = new Error(data.message || "ارتباط با سرور ناموفق بود");
        error.code = data.code;
        error.data = data.data;
        error.status = response.status || data.data?.status;
        throw error;
      }
      return data;
    },
  };
}
const categories = [
  { id: 1, name: "قهوه گرم", parent: 0, icon: "☕", order: 1 },
  { id: 2, name: "نوشیدنی سرد", parent: 0, icon: "◉", order: 2 },
  { id: 3, name: "کیک و دسر", parent: 0, icon: "◷", order: 3 },
  { id: 4, name: "صبحانه", parent: 0, icon: "☀", order: 4 },
];
const products = [
  [
    "اسپرسو",
    "عصاره‌ای غلیظ و خوش‌عطر از دانه‌های تازه برشته",
    85000,
    1,
    "espresso",
  ],
  ["لاته وانیل", "اسپرسو، شیر مخملی و وانیل طبیعی", 145000, 1, "latte"],
  ["کاپوچینو", "تعادل دلپذیر قهوه و فوم شیر", 125000, 1, "cappuccino"],
  ["آیس آمریکانو", "اسپرسوی دبل روی یخ، ساده و باطراوت", 110000, 2, "iced"],
  [
    "چیزکیک سن سباستین",
    "بافت نرم و خامه‌ای با رویه کاراملی",
    165000,
    3,
    "cake",
  ],
  ["صبحانه کافه", "تخم‌مرغ، نان تازه، پنیر و سبزیجات", 245000, 4, "breakfast"],
].map((p, i) => ({
  id: i + 1,
  name: p[0],
  description: p[1],
  price: String(p[2]),
  regular_price: String(p[2]),
  category_ids: [p[3]],
  art: p[4],
  available: true,
  type: "simple",
  variations: [],
  order: i,
  sku: `CAFE-${i + 1}`,
}));
const settings = {
  menu_theme: "cafe",
  custom_css_enabled: true,
  custom_css: {},
  restaurant_name: "کافه آرام",
  tagline: "یک مکث کوچک، یک حال خوب",
  accent: "#bd704b",
  background: "#faf8f5",
  category_background: "#f0e9df",
  layout: "grid",
  font_family: "Vazirmatn",
  currency_code: "IRT",
  dine_in_enabled: true,
  pickup_enabled: true,
  delivery_enabled: true,
  preparation_minutes: 20,
  table_default_mode: "order",
  qr_color: "#242424",
  qr_size: 512,
};
let orders = [
  {
    id: 101,
    number: "۱۰۱",
    stage: "awaiting_approval",
    channel: "table",
    table_label: "میز ۰۳",
    total: "270000",
    currency_symbol: "تومان",
    items: [
      { name: "لاته وانیل", quantity: 1 },
      { name: "کاپوچینو", quantity: 1 },
    ],
    status: "on-hold",
  },
];
let tables = [
  {
    id: 1,
    label: "میز ۰۳",
    mode: "inherit",
    enabled: true,
    token: "demo-table",
    url: "https://example.com/cafe-qr/demo-table",
  },
];
let staff = [];
const demoProductTranslations = [
  [
    "Espresso",
    "Freshly roasted coffee with a rich aroma",
    "浓缩咖啡",
    "新鲜烘焙咖啡，香气浓郁",
    "Espresso",
    "Taze kavrulmuş, yoğun aromalı kahve",
  ],
  [
    "Vanilla latte",
    "Espresso, velvety milk and natural vanilla",
    "香草拿铁",
    "浓缩咖啡、细腻牛奶与天然香草",
    "Vanilyalı latte",
    "Espresso, yumuşak süt ve doğal vanilya",
  ],
  [
    "Cappuccino",
    "A delightful balance of coffee and milk foam",
    "卡布奇诺",
    "咖啡与奶泡的美妙平衡",
    "Cappuccino",
    "Kahve ve süt köpüğünün dengesi",
  ],
  [
    "Iced Americano",
    "Double espresso over ice, simple and refreshing",
    "冰美式",
    "双份浓缩加冰，清爽简单",
    "Buzlu Americano",
    "Buz üzerinde double espresso, ferahlatıcı",
  ],
  [
    "San Sebastian cheesecake",
    "Creamy texture with a caramelized top",
    "巴斯克芝士蛋糕",
    "柔滑奶香与焦糖表面",
    "San Sebastian cheesecake",
    "Karamelize üst yüzeyiyle kremamsı doku",
  ],
  [
    "Cafe breakfast",
    "Eggs, fresh bread, cheese and vegetables",
    "咖啡馆早餐",
    "鸡蛋、新鲜面包、奶酪与蔬菜",
    "Kafe kahvaltısı",
    "Yumurta, taze ekmek, peynir ve sebzeler",
  ],
];
products[1].type = "variable";
products[1].variations = [
  {
    id: 21,
    name: "کوچک",
    price: "145000",
    available: true,
    translations: {
      en: { name: "Small" },
      zh: { name: "小杯" },
      tr: { name: "Küçük" },
    },
  },
  {
    id: 22,
    name: "بزرگ",
    price: "160000",
    available: true,
    translations: {
      en: { name: "Large" },
      zh: { name: "大杯" },
      tr: { name: "Büyük" },
    },
  },
];
products.forEach((p, i) => {
  const row = demoProductTranslations[i];
  p.translations = {
    en: { name: row[0], description: row[1] },
    zh: { name: row[2], description: row[3] },
    tr: { name: row[4], description: row[5] },
  };
});
[
  ["Hot coffee", "热咖啡", "Sıcak kahve"],
  ["Cold drinks", "冷饮", "Soğuk içecekler"],
  ["Cakes and desserts", "蛋糕甜点", "Pasta ve tatlılar"],
  ["Breakfast", "早餐", "Kahvaltı"],
].forEach(
  (row, i) =>
    (categories[i].translations = {
      en: { name: row[0] },
      zh: { name: row[1] },
      tr: { name: row[2] },
    }),
);
settings.enabled_languages = ["fa", "en", "zh", "tr"];
settings.default_language = "fa";
settings.content_translations = {
  en: {
    restaurant_name: "Aram Cafe",
    tagline: "A little pause, a good moment",
  },
  zh: { restaurant_name: "阿拉姆咖啡馆", tagline: "小憩片刻，享受美好" },
  tr: { restaurant_name: "Aram Kafe", tagline: "Küçük bir mola, güzel bir an" },
};
const demoBranches = [
  { id: 1, name: "شعبه اصلی", slug: "main", enabled: true, menu_url: "/" },
];
async function demo(path, method, body) {
  await new Promise((r) => setTimeout(r, 180));
  const [route] = path.split("?");
  const language =
    new URLSearchParams(path.split("?")[1] || "").get("lang") || "fa";
  const branch =
    demoBranches.find(
      (b) =>
        b.id ===
        Number(new URLSearchParams(path.split("?")[1] || "").get("branch_id")),
    ) || demoBranches[0];
  if (route === "/bootstrap")
    return {
      branch_id: branch.id,
      branch,
      branches: demoBranches,
      language,
      locale:
        languages.find((l) => l.code === language)?.html_locale || "fa-IR",
      direction: language === "fa" ? "rtl" : "ltr",
      languages,
      settings: Object.fromEntries(
        Object.entries(settings).filter(([key]) => key !== "custom_css"),
      ),
      products: products.map((p) => translatedEntity(p, language)),
      categories: categories.map((c) => translatedEntity(c, language)),
      context: { id: 1, label: "میز ۰۳", token: "demo-table", can_order: true },
      ordering: { dine_in: true, pickup: true, delivery: true, paused: false },
      csrf_token: "demo",
      currency_symbol: language === "fa" ? "تومان" : "IRT",
    };
  if (route === "/manage/bootstrap")
    return {
      branch_id: branch.id,
      branch,
      branches: demoBranches,
      can_manage_branches: true,
      settings,
      user: { id: 1, name: "مدیر کافه", roles: ["admincafe_manager"] },
      capabilities: [
        "admincafe_manage_menu",
        "admincafe_manage_orders",
        "admincafe_manage_settings",
        "admincafe_manage_tables",
        "admincafe_view_reports",
        "admincafe_manage_staff",
        "admincafe_receive_notifications",
      ],
      pages: [],
      currencies: [
        { code: "IRT", name: "تومان" },
        { code: "IRR", name: "ریال" },
        { code: "USD", name: "دلار آمریکا" },
      ],
      push: { available: false, reason: "پیش‌نمایش آفلاین" },
      roles: [
        "admincafe_manager",
        "admincafe_staff",
        "admincafe_kitchen",
        "admincafe_cashier",
      ],
    };
  if (route === "/orders/table") {
    const o = {
      id: Date.now(),
      number: "۱۰۲",
      stage: "awaiting_approval",
      tracking_token: "demo-track",
      total: "270000",
    };
    orders.unshift(o);
    return o;
  }
  if (route === "/orders/track")
    return {
      number: "۱۰۲",
      stage: "awaiting_approval",
      payment_status: "on-hold",
    };
  if (route === "/manage/reports")
    return {
      revenue: 4850000,
      order_count: 32,
      pending_count: 3,
      top_products: [
        { name: "لاته وانیل", quantity: 18 },
        { name: "اسپرسو", quantity: 12 },
      ],
      daily: [
        { date: "شنبه", total: 800000 },
        { date: "یکشنبه", total: 1200000 },
        { date: "دوشنبه", total: 950000 },
      ],
      currency_symbol: "تومان",
    };
  if (route === "/manage/appearance/preview") {
    if (
      Object.values(body?.custom_css || {}).some((value) =>
        String(value).trim(),
      )
    )
      throw new Error("اعتبارسنجی CSS سفارشی در سایت متصل در دسترس است.");
    return {
      menu_theme: body?.menu_theme || "cafe",
      custom_css_enabled: body?.custom_css_enabled !== false,
      menu_custom_css: "",
    };
  }
  if (route === "/manage/settings") {
    if (method !== "GET") Object.assign(settings, body);
    return settings;
  }
  if (route === "/manage/translation/settings") {
    if (method !== "GET")
      throw new Error("تنظیم سرویس ترجمه در سایت متصل در دسترس است.");
    return {
      enabled: false,
      configured: false,
      provider: "google",
      credential_source: "none",
      daily_character_limit: 100000,
    };
  }
  if (route === "/manage/translation/status")
    return {
      counts: { queued: 0, running: 0, completed: 0, failed: 0, skipped: 0 },
      problems: [],
      usage: { characters: 0, limit: 100000 },
    };
  if (route.startsWith("/manage/translation/"))
    throw new Error("ترجمه خودکار در سایت متصل در دسترس است.");
  if (route === "/manage/notifications")
    return {
      events: [],
      unread: 0,
      push: { available: false, reason: "نسخه نمایشی" },
    };
  if (route === "/manage/push/devices") return [];
  if (route === "/manage/media")
    throw new Error("بارگذاری تصویر در سایت متصل در دسترس است.");
  if (route.startsWith("/manage/import"))
    throw new Error("واردسازی فایل در سایت متصل در دسترس است.");
  if (route === "/manage/reorder") return { success: true };
  const resource = route.match(
    /^\/manage\/(products|categories|tables|staff|orders|branches)(?:\/(\d+))?$/,
  );
  if (resource) {
    let list = {
        products,
        categories,
        tables,
        staff,
        orders,
        branches: demoBranches,
      }[resource[1]],
      id = Number(resource[2]);
    if (method === "GET")
      return id
        ? list.find((x) => x.id === id)
        : resource[1] === "orders"
          ? { orders: list, total: list.length, pages: 1 }
          : list;
    if (method === "DELETE") {
      list.splice(
        list.findIndex((x) => x.id === id),
        1,
      );
      return { deleted: true };
    }
    if (id) {
      const item = list.find((x) => x.id === id);
      Object.assign(item, body);
      return item;
    }
    const item = {
      ...body,
      id: Date.now(),
      token: "demo-new",
      url: "https://example.com/cafe-qr/demo-new",
    };
    if (resource[1] === "branches") item.menu_url = "/menu/" + item.slug + "/";
    list.push(item);
    return item;
  }
  return { success: true };
}
