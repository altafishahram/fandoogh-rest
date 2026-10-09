import { test } from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import * as Vue from "vue";
import { compile } from "@vue/compiler-dom";
import { parse } from "@vue/compiler-sfc";
import { useMenu } from "../src/useMenu.js";
import { stores } from "../src/state.js";
import CompiledMenuCss from "../src/components/CompiledMenuCss.js";
import * as galleryHelpers from "../src/gallery.js";
const source = await readFile(
  new URL("../src/Menu.vue", import.meta.url),
  "utf8",
);
const render = new Function(
  "Vue",
  compile(parse(source).descriptor.template.content, { mode: "function" }).code,
)(Vue);
render._rc = true;
async function compiledTemplate(path) {
  const file = await readFile(new URL(path, import.meta.url), "utf8");
  const descriptor = parse(file).descriptor;
  const fn = new Function(
    "Vue",
    compile(descriptor.template.content, { mode: "function" }).code,
  )(Vue);
  fn._rc = true;
  return { descriptor, render: fn };
}
const flag = await compiledTemplate("../src/components/CountryFlag.vue");
const CountryFlag = { props: ["code", "label"], render: flag.render };
const gate = await compiledTemplate("../src/components/LanguageGate.vue");
const gateSetup = new Function(
  "Vue",
  "defineProps",
  "defineEmits",
  "CountryFlag",
  gate.descriptor.scriptSetup.content
    .replace(/import[\s\S]*?from\s*["'][^"']+["'];?/g, "")
    .replace(
      "const props = defineProps",
      "const {ref,onMounted,onUnmounted,nextTick}=Vue;const props = defineProps",
    ) + ";return {dialog,cards,countries,keyboard};",
);
const LanguageGate = {
  props: ["languages", "suggested"],
  emits: ["choose"],
  components: { CountryFlag },
  setup(props, { emit }) {
    return gateSetup(
      Vue,
      () => props,
      () => emit,
      CountryFlag,
    );
  },
  render: gate.render,
};
const gallery = await compiledTemplate("../src/components/FoodGallery.vue");
const gallerySetup = new Function(
  "Vue",
  "helpers",
  "defineProps",
  "defineEmits",
  "const {computed,onBeforeUnmount,ref,watch}=Vue;" +
    "const {galleryGestureIntent,galleryIndex,galleryIndexLabel,galleryKeyIndex,gallerySwipeIndex,normalizeGalleryImages}=helpers;" +
    gallery.descriptor.scriptSetup.content.replace(
      /import[\s\S]*?from\s*["'][^"']+["'];?/g,
      "",
    ) +
    ";return {props,images,orderedImages,index,failed,viewport,dragX,dragging,instant,trackStyle,announcement,select,keydown,pointerdown,pointermove,pointerup,resetGesture,imageError,galleryIndexLabel};",
);
const FoodGallery = {
  props: [
    "images",
    "name",
    "imageLabel",
    "indexLabel",
    "errorLabel",
    "emptyLabel",
    "direction",
  ],
  emits: ["change"],
  setup(props, { emit }) {
    return gallerySetup(
      Vue,
      galleryHelpers,
      () => props,
      () => emit,
    );
  },
  render: gallery.render,
};
const actualMenu = new Function(
  "h",
  "FoodGallery",
  "CompiledMenuCss",
  "LanguageGate",
  "CountryFlag",
  "useMenu",
  parse(source)
    .descriptor.script.content.replace(
      /import[\s\S]*?from\s*["'][^"']+["'];?/g,
      "",
    )
    .replace("export default", "return"),
)(Vue.h, FoodGallery, CompiledMenuCss, LanguageGate, CountryFlag, useMenu);
const all = (n, predicate) => [
  ...(predicate(n) ? [n] : []),
  ...n.children.flatMap((c) => all(c, predicate)),
];

function node(type, text = "") {
  return {
    type,
    tagName: type.toUpperCase(),
    text,
    children: [],
    props: {},
    parent: null,
    focus() {
      document.activeElement = this;
    },
    addEventListener() {},
    removeEventListener() {},
    get options() {
      return this.children.filter((child) => child.type === "option");
    },
    get selectedOptions() {
      return this.options.filter((option) => option.selected);
    },
    get value() {
      return this.props.value ?? this._value ?? this.text;
    },
    set value(value) {
      this.props.value = value;
    },
    getAttribute(key) {
      return this.props[key];
    },
    setAttribute(key, value) {
      this.props[key] = value;
    },
  };
}
const renderer = Vue.createRenderer({
  createElement: (type) => node(type),
  createText: (text) => node("#text", text),
  createComment: (text) => node("#comment", text),
  setText: (n, text) => (n.text = text),
  setElementText: (n, text) => {
    n.text = text;
    n.children = [];
  },
  parentNode: (n) => n.parent,
  nextSibling: (n) =>
    n.parent?.children[n.parent.children.indexOf(n) + 1] || null,
  insert(n, parent, anchor) {
    if (n.parent) n.parent.children.splice(n.parent.children.indexOf(n), 1);
    const index = anchor ? parent.children.indexOf(anchor) : -1;
    index < 0 ? parent.children.push(n) : parent.children.splice(index, 0, n);
    n.parent = parent;
  },
  remove(n) {
    if (n.parent) n.parent.children.splice(n.parent.children.indexOf(n), 1);
  },
  patchProp: (n, key, previous, next) => (n.props[key] = next),
});
const text = (n) => n.text + n.children.map(text).join(" ");
const find = (n, predicate) =>
  predicate(n) ? n : n.children.map((c) => find(c, predicate)).find(Boolean);
const settle = async () => {
  await new Promise((resolve) => setTimeout(resolve, 220));
  await Vue.nextTick();
};
test("all customer roots render every language and preserve quantities, selected variation and request ID", async () => {
  const original = {
    window: globalThis.window,
    document: globalThis.document,
    localStorage: globalThis.localStorage,
  };
  const memory = new Map();
  memory.set("admincafe-language:demo", "zh");
  memory.set(
    "admincafe:demo:opaque-token",
    JSON.stringify([
      {
        key: "2:22",
        product_id: 2,
        variation_id: 22,
        quantity: 3,
        price: 120000,
        name: "Saved latte",
      },
    ]),
  );
  memory.set("admincafe-request:demo:opaque-token", "saved-request");
  const location = {
    href: "https://cafe.test/menu?table=opaque-token&lang=en",
  };
  globalThis.window = {
    location,
    history: {
      state: null,
      replaceState: (_state, _title, url) => (location.href = String(url)),
    },
  };
  globalThis.localStorage = {
    getItem: (key) => memory.get(key) || null,
    setItem: (key, value) => memory.set(key, value),
  };
  globalThis.document = {
    activeElement: null,
    title: "Host page",
    documentElement: { lang: "en-GB", dir: "ltr" },
  };
  const mounts = [];
  try {
    stores.clear();
    const make = (component, standalone = false) => {
      const tree = node("root");
      const app = renderer.createApp({
        components: actualMenu.components,
        setup() {
          return useMenu(
            {
              demo: true,
              component,
              tableToken: "opaque-token",
              standalone,
              appearance: {
                menu_theme: "midnight",
                accent: "#d99964",
                menu_custom_css:
                  '.admincafe-root[data-admincafe-app="menu"] .ac-menu .ac-language-dialog{border-radius:28px;}',
              },
            },
            { querySelector: () => ({ focus() {} }) },
          );
        },
        render,
      });
      const instance = app.mount(tree);
      mounts.push(app);
      return { tree, instance };
    };
    const menu = make("menu"),
      cart = make("cart");
    await Vue.nextTick();
    assert.match(text(menu.tree), /Choose your language/);
    assert.doesNotMatch(text(menu.tree), /Cafe menu|Espresso/);
    assert.equal(menu.instance.state.bootstrap, null);
    assert.equal(
      find(menu.tree, (n) => n.props.class === "ac-menu").props[
        "data-ac-theme"
      ],
      "midnight",
    );
    assert.match(
      find(menu.tree, (n) => n.type === "style").text,
      /ac-language-dialog\{border-radius:28px/,
    );
    const savedCart = JSON.stringify(menu.instance.state.cart);
    assert.equal(menu.instance.state.pendingOrderId, "saved-request");
    assert.equal(
      all(menu.tree, (n) => n.props.class === "ac-language-dialog").length,
      1,
    );
    assert.equal(
      all(cart.tree, (n) => n.props.class === "ac-language-dialog").length,
      0,
    );
    const cards = all(menu.tree, (n) =>
      String(n.props.class || "")
        .split(/\s+/)
        .includes("ac-language-card"),
    );
    assert.equal(cards.length, 4);
    assert.equal(
      all(menu.tree, (n) => n.type === "svg" && n.props.role === "img").length,
      4,
    );
    const english = cards.find((n) => n.props["aria-label"] === "English");
    assert.ok(english);
    assert.equal(english.props.lang, "en-US");
    assert.equal(
      find(menu.tree, (n) => n.props.role === "dialog").props.lang,
      "fa-IR",
    );
    const americanFlag = find(english, (n) => n.type === "svg");
    assert.equal(
      all(
        americanFlag,
        (n) =>
          n.type === "path" &&
          String(n.props.transform || "").startsWith("translate("),
      ).length,
      50,
    );
    assert.equal(english.props["aria-current"], "true");
    english.props.onKeydown({ key: "ArrowLeft", preventDefault() {} });
    assert.equal(document.activeElement.props["aria-label"], "简体中文");
    english.props.onKeydown({
      key: "Escape",
      preventDefault() {},
      stopPropagation() {},
    });
    assert.equal(menu.instance.entered, false);
    english.props.onKeydown({
      key: "Enter",
      preventDefault() {},
      stopPropagation() {},
    });
    await settle();
    assert.equal(
      all(menu.tree, (n) => n.props.class === "ac-language-dialog").length,
      0,
    );
    assert.equal(cart.instance.entered, true);
    assert.equal(JSON.stringify(menu.instance.state.cart), savedCart);
    assert.equal(menu.instance.state.pendingOrderId, "saved-request");
    assert.match(text(menu.tree), /Cafe menu/);
    assert.equal(
      find(menu.tree, (n) => n.props.class === "ac-menu")?.props.dir,
      "ltr",
    );
    const originalCart = JSON.parse(JSON.stringify(menu.instance.state.cart));
    const simple = menu.instance.products.find(
      (p) => p.type === "simple" && p.available,
    );
    assert.ok(simple);
    menu.instance.open(simple);
    menu.instance.quantity = 2;
    menu.instance.add();
    await Vue.nextTick();
    assert.equal(
      menu.instance.state.cart.find((item) => item.product_id === simple.id)
        .quantity,
      2,
    );
    const afterSimpleAdd = JSON.stringify(menu.instance.state.cart);
    menu.instance.open({ ...simple, available: false });
    menu.instance.quantity = 2;
    menu.instance.add();
    menu.instance.quickAdd({ ...simple, available: false });
    assert.equal(JSON.stringify(menu.instance.state.cart), afterSimpleAdd);
    const variable = menu.instance.products.find(
      (p) => p.type === "variable" && p.available,
    );
    assert.ok(variable);
    menu.instance.quickAdd(variable);
    assert.equal(menu.instance.selected.id, variable.id);
    assert.equal(menu.instance.quantity, 1);
    assert.equal(JSON.stringify(menu.instance.state.cart), afterSimpleAdd);
    menu.instance.open({
      ...variable,
      image: "/main.webp",
      images: [{ src: "/main.webp" }, { src: "/detail.webp" }],
      variations: [
        { id: 987, name: "Large", available: true, image: "/detail.webp" },
      ],
    });
    menu.instance.variation = 987;
    assert.deepEqual(
      menu.instance.galleryImages.map((image) => image.src),
      ["/detail.webp", "/main.webp"],
    );
    await Vue.nextTick();
    const renderedImages = all(
      menu.tree,
      (n) =>
        n.type === "img" &&
        ["/main.webp", "/detail.webp"].includes(n.props.src),
    );
    assert.equal(renderedImages.length, 2);
    assert.equal(
      renderedImages.find((image) => image.props.src === "/detail.webp").parent
        .props["aria-hidden"],
      false,
    );
    menu.instance.state.cart = originalCart;
    await Vue.nextTick();
    menu.instance.open(menu.instance.products.find((p) => p.id === 2));
    menu.instance.variation = 22;
    menu.instance.add();
    await Vue.nextTick();
    menu.instance.state.cart[0].quantity = 3;
    await Vue.nextTick();
    menu.instance.state.pendingOrderId = "stable-request";
    const cartBefore = JSON.stringify(menu.instance.state.cart);
    assert.equal(cart.instance.count, 3);
    menu.instance.open(menu.instance.products.find((p) => p.id === 2));
    menu.instance.variation = 22;
    for (const [code, expected, direction] of [
      ["zh", "咖啡馆菜单", "ltr"],
      ["tr", "Kafe menüsü", "ltr"],
      ["fa", "منوی کافه", "rtl"],
      ["en", "Cafe menu", "ltr"],
    ]) {
      menu.instance.switchLanguage(code);
      await settle();
      assert.match(text(menu.tree), new RegExp(expected));
      assert.equal(
        find(menu.tree, (n) => n.props.class === "ac-menu")?.props.dir,
        direction,
      );
      assert.equal(cart.instance.language, code);
      assert.equal(menu.instance.variation, 22);
      assert.equal(menu.instance.selected.id, 2);
      assert.equal(JSON.stringify(menu.instance.state.cart), cartBefore);
      assert.equal(menu.instance.state.pendingOrderId, "stable-request");
      assert.equal(menu.instance.state.cart[0].variation_id, 22);
      assert.equal(menu.instance.state.cart[0].quantity, 3);
      assert.equal(document.title, "Host page");
      assert.equal(document.documentElement.lang, "en-GB");
      assert.equal(document.documentElement.dir, "ltr");
      assert.ok(
        new URL(location.href).searchParams.get("table") === "opaque-token",
      );
    }
    const standalone = make("menu", true);
    await settle();
    standalone.instance.switchLanguage("zh");
    await settle();
    assert.equal(document.documentElement.lang, "zh-CN");
    assert.equal(document.documentElement.dir, "ltr");
    assert.match(document.title, /阿拉姆咖啡馆.*咖啡馆菜单/);
    standalone.instance.switchLanguage("fa");
    await settle();
    assert.equal(document.documentElement.lang, "fa-IR");
    assert.equal(document.documentElement.dir, "rtl");
    assert.match(document.title, /کافه آرام.*منوی کافه/);
    assert.equal(JSON.stringify(menu.instance.state.cart), cartBefore);
    assert.equal(menu.instance.state.pendingOrderId, "stable-request");
  } finally {
    mounts.forEach((app) => app.unmount());
    stores.clear();
    for (const [key, value] of Object.entries(original)) {
      if (value === undefined) delete globalThis[key];
      else globalThis[key] = value;
    }
  }
});
