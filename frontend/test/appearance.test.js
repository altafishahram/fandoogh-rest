import { test } from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import * as Vue from "vue";
import { renderToString } from "@vue/server-renderer";
import { parse } from "@vue/compiler-sfc";
import { compile } from "@vue/compiler-dom";
import {
  appearanceStyle,
  themeId,
  menuThemes,
  cssBlocks,
  emptyCustomCss,
} from "../src/appearance.js";
import CompiledMenuCss from "../src/components/CompiledMenuCss.js";
import { languages } from "../src/customer-i18n.js";
import { api } from "../src/api.js";
import { loadMenuFont } from "../src/menu-font.js";

test("themes preserve manual colors and typography until explicitly selected", () => {
  assert.equal(menuThemes.length, 5);
  assert.equal(new Set(menuThemes.map((t) => t.accent)).size, 5);
  assert.equal(themeId({ menu_theme: "unknown" }), "cafe");
  const style = appearanceStyle({
    menu_theme: "midnight",
    accent: "#123456",
    title_size_mobile: 28,
    price_weight_desktop: 900,
  });
  assert.equal(style["--ac-accent"], "#123456");
  assert.equal(style["--ac-title-mobile"], "28px");
  assert.equal(style["--ac-price-weight-desktop"], 900);
  assert.deepEqual(
    cssBlocks.map((b) => b.key),
    ["general", "card", "detail", "language", "categories", "buttons", "cart"],
  );
  const savedCss = Object.fromEntries(
    cssBlocks.map(({ key }) => [key, "color:red;"]),
  );
  assert.deepEqual(
    { ...savedCss, ...emptyCustomCss() },
    {
      general: "",
      card: "",
      detail: "",
      language: "",
      categories: "",
      buttons: "",
      cart: "",
    },
  );
});

test("compiled CSS renders as a style text child, never HTML", () => {
  const props = Vue.reactive({
    css: ".ac-product { color:red; } </style><script>alert(1)</script>",
  });
  const render = CompiledMenuCss.setup(props);
  assert.equal(render().type, "style");
  assert.equal(render().children, props.css);
  assert.equal(render().props.innerHTML, undefined);
  props.css = "";
  assert.equal(render(), null);
});

test("designer theme action preserves custom edits and reset explicitly clears persisted blocks", async () => {
  const source = await readFile(
    new URL("../src/components/AppearanceDesigner.vue", import.meta.url),
    "utf8",
  );
  const script = parse(source).descriptor.scriptSetup.content.replace(
    /import[\s\S]*?from\s*["'][^"']+["'];?/g,
    "",
  );
  const settings = Vue.ref({
    custom_css: { card: "color:red;" },
    font_family: "Custom",
    title_size_mobile: 27,
    layout: "list",
  });
  const runtime = { ...Vue, inject: () => ({ settings }) };
  const setup = new Function(
    "Vue",
    "cssBlocks",
    "menuThemes",
    "emptyCustomCss",
    "const {computed,inject}=Vue;" + script + ";return {reset,choose};",
  );
  const designer = setup(runtime, cssBlocks, menuThemes, emptyCustomCss);
  designer.choose(menuThemes[2]);
  assert.equal(settings.value.menu_theme, "midnight");
  assert.equal(settings.value.accent, "#d99964");
  assert.deepEqual(settings.value.custom_css, { card: "color:red;" });
  assert.equal(settings.value.font_family, "Custom");
  assert.equal(settings.value.title_size_mobile, 27);
  assert.equal(settings.value.layout, "list");
  designer.reset();
  assert.deepEqual(settings.value.custom_css, {
    general: "",
    card: "",
    detail: "",
    language: "",
    categories: "",
    buttons: "",
    cart: "",
  });
});

test("appearance preview debounces server validation, discards stale responses and retains last valid CSS", async () => {
  const source = await readFile(
    new URL("../src/components/AppearancePreview.vue", import.meta.url),
    "utf8",
  );
  const script = parse(source).descriptor.scriptSetup.content.replace(
    /import[\s\S]*?from\s*["'][^"']+["'];?/g,
    "",
  );
  let callback, scheduled, cleanup;
  const requests = [];
  const settings = Vue.ref({
    custom_css: { card: "color:red;" },
    custom_css_enabled: true,
  });
  const panel = {
    settings,
    money: () => "145000",
    client: {
      request(path, method, body) {
        return new Promise((resolve, reject) =>
          requests.push({ path, method, body, resolve, reject }),
        );
      },
    },
  };
  const runtime = {
    ...Vue,
    inject: () => panel,
    watch: (get, cb, options) => {
      if (options?.immediate) {
        callback = cb;
        cb();
      }
    },
    onUnmounted: (cb) => {
      cleanup = cb;
    },
  };
  const setup = new Function(
    "Vue",
    "appearanceStyle",
    "themeId",
    "CompiledMenuCss",
    "setTimeout",
    "clearTimeout",
    "document",
    "const {computed,inject,ref,watch,onUnmounted,shallowRef}=Vue;" +
      script +
      "; return {safeCss,error,validating,prepareFrame};",
  );
  const state = setup(
    runtime,
    appearanceStyle,
    themeId,
    CompiledMenuCss,
    (cb) => {
      scheduled = cb;
      return 1;
    },
    () => {},
    {
      querySelectorAll(selector) {
        if (selector === 'link[rel="stylesheet"]') {
          return ["fandoogh-rest.css", "admincafe.css", "theme.css"].map(
            (file) => ({
              href: `https://cafe.test/assets/${file}`,
              cloneNode: () => file,
            }),
          );
        }
        return [];
      },
    },
  );
  const copiedStyles = [];
  state.prepareFrame({
    target: {
      contentDocument: {
        head: { appendChild: (sheet) => copiedStyles.push(sheet) },
        getElementById: () => ({}),
      },
    },
  });
  assert.deepEqual(copiedStyles, ["fandoogh-rest.css", "admincafe.css"]);
  assert.equal(requests.length, 0);
  const first = scheduled();
  assert.equal(requests[0].path, "/manage/appearance/preview");
  assert.equal(requests[0].method, "POST");
  assert.deepEqual(requests[0].body.custom_css, { card: "color:red;" });
  settings.value.custom_css = { card: "color:blue;" };
  callback();
  const second = scheduled();
  requests[1].resolve({
    menu_custom_css: ".ac-appearance-preview .ac-product {color:blue;}",
  });
  await second;
  requests[0].resolve({ menu_custom_css: "STALE" });
  await first;
  assert.match(state.safeCss.value, /blue/);
  callback();
  const invalid = scheduled();
  requests[2].reject(new Error("CSS نامعتبر"));
  await invalid;
  assert.match(state.error.value, /CSS نامعتبر/);
  assert.match(state.safeCss.value, /blue/);
  assert.equal(state.validating.value, false);
  cleanup();
});

test("appearance preview template renders independent cards and dialog controls", async () => {
  const flagSource = await readFile(
    new URL("../src/components/CountryFlag.vue", import.meta.url),
    "utf8",
  );
  const flagRender = new Function(
    "Vue",
    compile(parse(flagSource).descriptor.template.content, { mode: "function" })
      .code,
  )(Vue);
  flagRender._rc = true;
  const CountryFlag = { props: ["code", "label"], render: flagRender };
  const source = await readFile(
    new URL("../src/components/AppearancePreviewCanvas.vue", import.meta.url),
    "utf8",
  );
  const render = new Function(
    "Vue",
    compile(parse(source).descriptor.template.content, { mode: "function" })
      .code,
  )(Vue);
  render._rc = true;
  for (const surface of ["menu", "detail", "language", "cart"]) {
    const app = Vue.createSSRApp({
      components: { CompiledMenuCss, CountryFlag },
      render,
      setup: () => ({
        settings: { restaurant_name: "Test", menu_theme: "midnight" },
        surface,
        device: "mobile",
        direction: "rtl",
        style: {},
        safeCss: ".ac-appearance-preview{color:red}",
        validating: false,
        error: "",
        money: () => "145000",
        themeId,
        previewLanguages: languages,
        countries: {
          fa: "ایران",
          en: "United States",
          zh: "中国",
          tr: "Türkiye",
        },
      }),
    });
    const html = await renderToString(app);
    assert.match(html, /ac-appearance-preview/);
    assert.match(html, /data-ac-theme="midnight"/);
    if (surface === "language") {
      assert.equal((html.match(/class="ac-country-flag"/g) || []).length, 4);
      for (const language of languages) assert.ok(html.includes(language.name));
    }
    assert.match(
      html,
      surface === "menu"
        ? /ac-product-copy/
        : new RegExp(
            surface === "detail"
              ? "ac-food-dialog"
              : surface === "cart"
                ? "ac-cart-dialog"
                : "ac-language-dialog",
          ),
    );
  }
});

test("demo hides raw CSS from customers and never pretends to validate drafts", async () => {
  const client = api({ demo: true });
  await client.request("/manage/settings", "POST", {
    custom_css: { card: "color:red;" },
  });
  const boot = await client.request("/bootstrap");
  assert.equal(boot.settings.custom_css, undefined);
  await assert.rejects(
    client.request("/manage/appearance/preview", "POST", {
      custom_css: { card: "color:red;" },
    }),
  );
  assert.equal(
    (
      await client.request("/manage/appearance/preview", "POST", {
        custom_css: {},
      })
    ).menu_custom_css,
    "",
  );
  await client.request("/manage/settings", "POST", { custom_css: {} });
});

test("custom fonts load once per document and remain independent for iframe preview", async () => {
  let loads = 0;
  class Font {
    constructor(family, source) {
      this.family = family;
      this.source = source;
    }
    load() {
      loads++;
      return Promise.resolve(this);
    }
  }
  const makeDocument = () => ({
    defaultView: { FontFace: Font },
    fonts: {
      added: [],
      add(font) {
        this.added.push(font);
      },
    },
  });
  const main = makeDocument(),
    frame = makeDocument();
  const settings = {
    font_family: "MyCafe",
    custom_font_url: "https://cafe.test/fonts/cafe.woff2",
  };
  await Promise.all([
    loadMenuFont(settings, main),
    loadMenuFont(settings, main),
    loadMenuFont(settings, frame),
  ]);
  assert.equal(loads, 2);
  assert.equal(main.fonts.added.length, 1);
  assert.equal(frame.fonts.added.length, 1);
  assert.equal(main.fonts.added[0].family, "MyCafe");
});
