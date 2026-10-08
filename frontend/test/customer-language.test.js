import { test } from "node:test";
import assert from "node:assert/strict";
import {
  dictionaries,
  languages,
  translateCustomer,
  selectLanguage,
  translatedEntity,
  cartLabel,
  localeNumber,
} from "../src/customer-i18n.js";
import { loadCustomerBootstrap } from "../src/customer-language.js";
import { shared, stores } from "../src/state.js";
test("all four customer catalogs have identical keys and interpolation tokens", () => {
  const source = Object.keys(dictionaries.fa).sort();
  assert.ok(source.length > 60);
  for (const language of ["en", "zh", "tr"]) {
    assert.deepEqual(Object.keys(dictionaries[language]).sort(), source);
    for (const key of source) {
      assert.ok(dictionaries[language][key].trim());
      const tokens = (value) =>
        [...value.matchAll(/\{[a-zA-Z_]+\}/g)].map((m) => m[0]).sort();
      assert.deepEqual(
        tokens(dictionaries[language][key]),
        tokens(key),
        `${language}: ${key}`,
      );
    }
  }
});
test("initial language preference respects URL then saved value then enabled default", () => {
  assert.equal(
    selectLanguage({
      url: "https://cafe.test/menu?lang=fa",
      saved: "zh",
      defaultLanguage: "en",
    }),
    "fa",
  );
  assert.equal(
    selectLanguage({
      url: "https://cafe.test/menu?table=secret&lang=zh",
      saved: "en",
      defaultLanguage: "tr",
    }),
    "zh",
  );
  assert.equal(
    selectLanguage({
      url: "https://cafe.test/menu",
      saved: "en",
      defaultLanguage: "tr",
    }),
    "en",
  );
  assert.equal(selectLanguage({ saved: "de", defaultLanguage: "tr" }), "tr");
  assert.equal(
    selectLanguage({
      url: "https://cafe.test/menu?lang=en",
      saved: "zh",
      defaultLanguage: "fa",
      enabled: ["fa", "tr"],
    }),
    "fa",
  );
});
test("customer direction and numbers are local while order numbers preserve prefixes", () => {
  assert.equal(languages.find((l) => l.code === "fa").direction, "rtl");
  for (const code of ["en", "zh", "tr"])
    assert.equal(languages.find((l) => l.code === code).direction, "ltr");
  assert.equal(localeNumber(12, "fa-IR"), "۱۲");
  assert.equal(localeNumber("۱۲", "en-US"), "12");
  assert.equal(localeNumber("CAFE-102", "en-US"), "CAFE-102");
  assert.equal(
    translateCustomer("مشاهده {count} انتخاب", "tr", { count: 2 }),
    "2 ürünü görüntüle",
  );
  assert.equal(
    translateCustomer("ارتباط با سرور ناموفق بود", "zh"),
    "无法连接服务器",
  );
});
test("content translation falls back independently and keeps product IDs/prices/availability", () => {
  const product = {
    id: 1,
    name: "لاته",
    description: "شیر",
    price: "50",
    available: true,
    translations: { en: { name: "Latte", description: "" } },
  };
  const translated = translatedEntity(product, "en");
  assert.equal(translated.name, "Latte");
  assert.equal(translated.description, "شیر");
  assert.equal(translated.id, 1);
  assert.equal(translated.price, "50");
  assert.equal(translated.available, true);
  assert.equal(product.name, "لاته");
});
test("shared cart survives language switches with quantities variants request ID and token unchanged", () => {
  const original = globalThis.localStorage;
  globalThis.localStorage = { getItem: () => null, setItem: () => {} };
  try {
    stores.clear();
    const config = { apiBase: "/test", tableToken: "opaque-table" };
    const first = shared(config).state;
    first.cart.push({
      key: "1:12",
      product_id: 1,
      variation_id: 12,
      quantity: 3,
      name: "لاته بزرگ",
    });
    first.pendingOrderId = "request-id";
    first.language = "fa";
    const products = [
      {
        id: 1,
        name: "لاته",
        translations: { en: { name: "Latte" } },
        variations: [
          { id: 12, name: "بزرگ", translations: { en: { name: "Large" } } },
        ],
      },
    ];
    const before = JSON.stringify(first.cart);
    first.language = "en";
    const second = shared({ ...config, language: "zh" }).state;
    assert.equal(first, second);
    assert.equal(JSON.stringify(second.cart), before);
    assert.equal(second.pendingOrderId, "request-id");
    assert.equal(cartLabel(second.cart[0], products, "en"), "Latte • Large");
    assert.equal(config.tableToken, "opaque-table");
  } finally {
    stores.clear();
    globalThis.localStorage = original;
  }
});
test("out-of-order bootstrap responses cannot replace the active language", async () => {
  const state = { language: "en", languageRequest: 0 };
  let resolveEn, resolveZh;
  const english = loadCustomerBootstrap(
    state,
    "en",
    () => new Promise((resolve) => (resolveEn = resolve)),
  );
  state.language = "zh";
  const chinese = loadCustomerBootstrap(
    state,
    "zh",
    () => new Promise((resolve) => (resolveZh = resolve)),
  );
  resolveZh({
    language: "zh",
    locale: "zh-CN",
    products: [{ id: 1, name: "拿铁" }],
  });
  await chinese;
  resolveEn({
    language: "en",
    locale: "en-US",
    products: [{ id: 1, name: "Latte" }],
  });
  await english;
  assert.equal(state.bootstrap.language, "zh");
  assert.equal(state.bootstrap.products[0].name, "拿铁");
  assert.equal(state.bootstrapLoading, false);
});
test("failed bootstrap remains in selected locale with a translated network error", async () => {
  const state = { language: "tr", languageRequest: 0 };
  await loadCustomerBootstrap(state, "tr", async () => {
    throw new TypeError("Failed to fetch");
  });
  assert.equal(state.language, "tr");
  assert.equal(state.bootstrapLoading, false);
  assert.equal(
    translateCustomer(state.bootstrapError, "tr"),
    "Sunucuya bağlanılamadı",
  );
});
