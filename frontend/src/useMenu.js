import {
  languages,
  translateCustomer,
  selectLanguage,
  translatedEntity,
  cartLabel,
  localeNumber,
} from "./customer-i18n.js";
import {
  ref,
  toRef,
  computed,
  onMounted,
  onUnmounted,
  watch,
  nextTick,
} from "vue";
import { api } from "./api.js";
import { loadMenuFont } from "./menu-font.js";
import { appearanceStyle, themeId } from "./appearance.js";
import { loadCustomerBootstrap } from "./customer-language.js";
import { shared } from "./state.js";
import { branchCheckout } from "./branch-context.js";
import {
  money,
  filterProducts,
  orderItems,
  canOrder,
  cartRemovals,
} from "./domain.js";

let languageMountId = 0;
export function useMenu(config, root) {
  const client = api(config),
    { state, persist } = shared(config),
    loading = toRef(state, "bootstrapLoading"),
    error = toRef(state, "bootstrapError"),
    category = config.categoryId
      ? ref(Number(config.categoryId))
      : toRef(state, "category"),
    search = ref(""),
    selected = ref(null),
    variation = ref(0),
    quantity = ref(1),
    openingFromKeyboard = ref(false),
    cartOpen = ref(false),
    busy = ref(false),
    channel = ref("table"),
    note = ref(""),
    name = ref(""),
    tracking = toRef(state, "tracking");
  let poll, focus;
  const mountId = String(++languageMountId);
  state.languageGateMembers.push(mountId);
  let entryFocusPending = false;
  if (!state.languageGateOwner) state.languageGateOwner = mountId;
  const entered = toRef(state, "languageEntered");
  const ownsGate = computed(
    () => !entered.value && state.languageGateOwner === mountId,
  );
  function confirmLanguage(code) {
    if (!availableLanguages.value.some((l) => l.code === code)) return;
    entryFocusPending = state.languageGateOwner === mountId;
    switchLanguage(code);
    entered.value = true;
  }

  const preferenceKey = `admincafe-language:${config.apiBase || "demo"}`;
  let saved = "";
  try {
    saved = localStorage.getItem(preferenceKey) || "";
  } catch {}
  if (!state.language)
    state.language = selectLanguage({
      url: window.location.href,
      saved,
      defaultLanguage: config.defaultLanguage || config.language || "fa",
      enabled: config.enabledLanguages || languages.map((l) => l.code),
    });
  const language = toRef(state, "language");
  const descriptor = computed(
    () => languages.find((l) => l.code === language.value) || languages[0],
  );
  const locale = computed(() =>
    state.bootstrap?.language === language.value
      ? state.bootstrap.locale || descriptor.value.html_locale
      : descriptor.value.html_locale,
  );
  const direction = computed(() => descriptor.value.direction);
  const t = (message, params = {}) =>
    translateCustomer(
      message,
      language.value,
      params,
      state.bootstrap?.language === language.value
        ? state.bootstrap.strings || {}
        : {},
    );
  const number = (value) => localeNumber(value, locale.value);
  const customerMoney = (value, symbol) =>
    `${new Intl.NumberFormat(locale.value, { maximumFractionDigits: 2 }).format(Number(value) || 0)} ${symbol || ""}`;
  const availableLanguages = computed(
    () =>
      state.bootstrap?.languages ||
      languages.filter((l) =>
        (config.enabledLanguages || languages.map((x) => x.code)).includes(
          l.code,
        ),
      ),
  );
  const stages = {
    awaiting_approval: "منتظر تأیید کارکنان",
    accepted: "تأیید شد",
    preparing: "در حال آماده‌سازی",
    ready: "آماده تحویل",
    delivered: "تحویل شد",
    cancelled: "لغو شد",
  };
  function switchLanguage(code) {
    if (!availableLanguages.value.some((l) => l.code === code)) return;
    language.value = code;
    try {
      localStorage.setItem(preferenceKey, code);
    } catch {}
    const url = new URL(window.location.href);
    url.searchParams.set("lang", code);
    window.history.replaceState(window.history.state, "", url);
  }
  const label = (item) => cartLabel(item, boot.value.products, language.value);
  async function loadLanguage(code) {
    const query = new URLSearchParams({ lang: code });
    if (config.tableToken) query.set("table", config.tableToken);
    const response = await loadCustomerBootstrap(state, code, () =>
      client.request("/bootstrap?" + query.toString()),
    );
    client.setToken(state.bootstrap?.csrf_token || "");
    if (entryFocusPending) {
      entryFocusPending = false;
      nextTick(() => root.querySelector(".ac-language-switch select")?.focus());
    }
    if (!response) return;
    if (response.settings.currency_label)
      response.currency_symbol = response.settings.currency_label;
    if (!response.context)
      channel.value = response.ordering.pickup ? "pickup" : "delivery";
    if (selected.value) {
      const fresh = boot.value.products.find((p) => p.id === selected.value.id);
      if (fresh) selected.value = fresh;
    }
  }
  watch([language, entered], ([code, isEntered]) => {
    if (isEntered) loadLanguage(code);
  });

  const boot = computed(() => {
    const raw = state.bootstrap || {
      settings: {},
      products: [],
      categories: [],
    };
    const translated =
      raw.settings.content_translations?.[language.value] || {};
    return {
      ...raw,
      settings: {
        ...raw.settings,
        ...Object.fromEntries(
          Object.entries(translated).filter(
            ([, v]) => typeof v === "string" && v.trim(),
          ),
        ),
      },
      products: raw.products.map((p) => ({
        ...translatedEntity(p, language.value),
        variations: (p.variations || []).map((v) =>
          translatedEntity(v, language.value),
        ),
      })),
      categories: raw.categories.map((c) =>
        translatedEntity(c, language.value),
      ),
    };
  });
  const enabled = computed(() => canOrder(boot.value, config, channel.value));
  watch(
    [language, locale, () => state.bootstrap],
    () => {
      if (config.standalone !== true) return;
      document.documentElement.lang = locale.value;
      document.documentElement.dir = direction.value;
      document.title = `${boot.value.settings.restaurant_name || t("کافه")} | ${t("منوی کافه")}`;
    },
    { immediate: true },
  );
  const products = computed(() => {
    const list = filterProducts(
        boot.value.products,
        category.value,
        search.value,
        boot.value.categories,
      ),
      order =
        boot.value.categories.find((c) => c.id === category.value)
          ?.product_order || [];
    return order.length
      ? list.sort(
          (a, b) =>
            (order.includes(a.id) ? order.indexOf(a.id) : 9999) -
            (order.includes(b.id) ? order.indexOf(b.id) : 9999),
        )
      : list;
  });
  const total = computed(() =>
    state.cart.reduce((sum, i) => sum + Number(i.price) * i.quantity, 0),
  );
  const count = computed(() =>
    state.cart.reduce((sum, i) => sum + i.quantity, 0),
  );
  const heroProduct = computed(
    () =>
      boot.value.products.find((p) => p.available && p.image) ||
      boot.value.products.find((p) => p.image),
  );
  const galleryImages = computed(() => {
    const product = selected.value;
    if (!product) return [];
    const images = [...(product.images || [])];
    if (product.image && !images.some((image) => image.src === product.image))
      images.unshift({
        id: product.image_id,
        src: product.image,
        alt: product.name,
      });
    const choice = product.variations?.find(
      (v) => v.id === Number(variation.value),
    );
    if (choice?.image) {
      return [
        {
          id: choice.image_id,
          src: choice.image,
          alt: choice.name || product.name,
        },
        ...images.filter((image) => image.src !== choice.image),
      ];
    }
    return images;
  });
  watch(
    () => state.cart,
    () => {
      state.pendingOrderId = "";
      persist();
    },
    { deep: true },
  );
  watch(cartOpen, (value) => {
    if (value) {
      focus = document.activeElement;
      nextTick(() => root.querySelector(".ac-dialog button")?.focus());
    }
  });
  const appearance = computed(() => ({
    ...(config.appearance || {}),
    ...boot.value.settings,
  }));
  const menuTheme = computed(() => themeId(appearance.value));
  const customCss = computed(() =>
    appearance.value.custom_css_enabled === false
      ? ""
      : appearance.value.menu_custom_css || "",
  );
  const style = computed(() => appearanceStyle(appearance.value));
  watch(
    () => [appearance.value.custom_font_url, appearance.value.font_family],
    () => {
      loadMenuFont(appearance.value, document).catch(() => {
        error.value = "فونت سفارشی بارگذاری نشد؛ فونت پیش‌فرض فعال است.";
      });
    },
    { immediate: true },
  );

  function close() {
    selected.value = null;
    cartOpen.value = false;
    nextTick(() => focus?.focus());
  }
  function openCart(event) {
    openingFromKeyboard.value = event?.detail === 0;
    cartOpen.value = true;
  }
  function open(p, event) {
    focus = document.activeElement;
    openingFromKeyboard.value = event?.detail === 0;
    selected.value = p;
    variation.value = 0;
    quantity.value = 1;
    nextTick(() => root.querySelector(".ac-dialog button")?.focus());
  }
  function quantityFor(product) {
    return state.cart
      .filter((i) => i.product_id === product.id)
      .reduce((sum, i) => sum + i.quantity, 0);
  }
  function quickAdd(product, event) {
    if (!enabled.value || !product.available) return;
    if (product.type === "variable") {
      open(product, event);
      return;
    }
    const existing = state.cart.find(
      (i) => i.product_id === product.id && !i.variation_id,
    );
    if (existing) existing.quantity = Math.min(99, existing.quantity + 1);
    else
      state.cart.push({
        key: `${product.id}:0`,
        product_id: product.id,
        variation_id: 0,
        name: product.name,
        price: product.price,
        quantity: 1,
      });
  }
  function changeProduct(product, delta) {
    if (!enabled.value) return;
    if (product.type === "variable") {
      open(product);
      return;
    }
    const item = state.cart.find(
      (i) => i.product_id === product.id && !i.variation_id,
    );
    if (item) change(item, delta);
    else if (delta > 0) quickAdd(product);
  }
  function add() {
    const p = selected.value,
      v = p?.variations?.find((v) => v.id === Number(variation.value));
    if (!p || !enabled.value || !p.available || (v && !v.available)) return;
    if (p.type === "variable" && !v) {
      error.value = "لطفاً یک گزینه انتخاب کنید";
      return;
    }
    const id = `${p.id}:${v?.id || 0}`,
      existing = state.cart.find((i) => i.key === id);
    const amount = Math.max(
      1,
      Math.min(99, Math.floor(Number(quantity.value) || 1)),
    );
    if (existing) existing.quantity = Math.min(99, existing.quantity + amount);
    else
      state.cart.push({
        key: id,
        product_id: p.id,
        variation_id: v?.id || 0,
        name: p.name + (v ? " • " + v.name : ""),
        price: v?.price ?? p.price,
        quantity: amount,
      });
    close();
  }
  function change(i, d) {
    i.quantity = Math.min(99, i.quantity + d);
    if (i.quantity <= 0) state.cart.splice(state.cart.indexOf(i), 1);
  }
  async function submit() {
    busy.value = true;
    error.value = "";
    try {
      if (channel.value === "table") {
        if (!state.pendingOrderId) state.pendingOrderId = crypto.randomUUID();
        persist();
        tracking.value = await client.request("/orders/table", "POST", {
          language: language.value,
          table_token: boot.value.context.token,
          items: orderItems(state.cart),
          name: name.value,
          note: note.value,
          request_id:
            state.pendingOrderId ||
            (state.pendingOrderId = crypto.randomUUID()),
        });
        state.cart = [];
        persist();
        cartOpen.value = false;
        startTracking();
      } else {
        if (config.demo)
          throw new Error("پرداخت واقعی در پیش‌نمایش در دسترس نیست.");
        const checkout = await branchCheckout(
          client,
          {
            channel: channel.value,
            language: language.value,
          },
          () =>
            window.confirm(
              t(
                "سبد پرداخت مربوط به شعبه دیگری است. آن را پاک کرده و با سفارش این شعبه جایگزین کنیم؟",
              ),
            ),
        );
        if (!checkout) return;
        const storeUrl = boot.value.wc_store_api_url.replace(/\/$/, "");
        const storeEndpoint = (path) =>
          storeUrl +
          path +
          "?lang=" +
          encodeURIComponent(language.value) +
          "&branch_id=" +
          encodeURIComponent(boot.value.branch_id || config.branchId || "");
        const cartResponse = await fetch(storeEndpoint("/cart"), {
          credentials: "same-origin",
          headers: { Nonce: boot.value.wc_nonce },
          cache: "no-store",
        });
        const serverCart = await cartResponse.json();
        if (!cartResponse.ok)
          throw new Error(
            serverCart.message || "دریافت سبد ووکامرس ناموفق بود",
          );
        for (const key of cartRemovals(
          serverCart.items || [],
          state.cart,
          boot.value.products,
        )) {
          const response = await fetch(storeEndpoint("/cart/remove-item"), {
            method: "POST",
            credentials: "same-origin",
            headers: {
              "Content-Type": "application/json",
              Nonce: boot.value.wc_nonce,
            },
            body: JSON.stringify({ key }),
          });
          const result = await response.json();
          if (!response.ok)
            throw new Error(
              result.message || "افزودن به سبد ووکامرس ناموفق بود",
            );
        }
        for (const item of state.cart) {
          const existing = serverCart.items?.find(
            (i) => i.id === (item.variation_id || item.product_id),
          );
          const r = await fetch(
            storeEndpoint(existing ? "/cart/update-item" : "/cart/add-item"),
            {
              method: "POST",
              credentials: "same-origin",
              headers: {
                "Content-Type": "application/json",
                Nonce: boot.value.wc_nonce,
              },
              body: JSON.stringify(
                existing
                  ? { key: existing.key, quantity: item.quantity }
                  : {
                      id: item.variation_id || item.product_id,
                      quantity: item.quantity,
                    },
              ),
            },
          );
          const d = await r.json();
          if (!r.ok)
            throw new Error(d.message || "افزودن به سبد ووکامرس ناموفق بود");
        }
        window.location.assign(checkout.url);
      }
    } catch (e) {
      error.value =
        e instanceof TypeError ? "ارتباط با سرور ناموفق بود" : e.message;
    } finally {
      busy.value = false;
    }
  }
  function startTracking() {
    clearInterval(poll);
    if (!tracking.value?.tracking_token) return;
    poll = setInterval(async () => {
      try {
        tracking.value = {
          ...tracking.value,
          ...(await client.request(
            "/orders/track?token=" +
              encodeURIComponent(tracking.value.tracking_token) +
              "&lang=" +
              language.value,
          )),
        };
        persist();
        if (["delivered", "cancelled"].includes(tracking.value.stage))
          clearInterval(poll);
      } catch {}
    }, 10000);
  }
  onMounted(async () => {
    startTracking();
    if (!entered.value) return;
    await loadLanguage(language.value);
  });
  function trap(e) {
    const nodes = [
      ...e.currentTarget.querySelectorAll(
        'button,input,select,textarea,a[href],[tabindex]:not([tabindex="-1"])',
      ),
    ].filter((n) => !n.disabled);
    if (e.shiftKey && document.activeElement === nodes[0]) {
      e.preventDefault();
      nodes.at(-1)?.focus();
    } else if (!e.shiftKey && document.activeElement === nodes.at(-1)) {
      e.preventDefault();
      nodes[0]?.focus();
    }
  }
  onUnmounted(() => {
    clearInterval(poll);
    state.languageGateMembers = state.languageGateMembers.filter(
      (id) => id !== mountId,
    );
    if (state.languageGateOwner === mountId)
      state.languageGateOwner = state.languageGateMembers[0] || "";
  });
  return {
    t,
    entered,
    ownsGate,
    confirmLanguage,
    language,
    locale,
    direction,
    availableLanguages,
    switchLanguage,
    number,
    label,
    stages,
    config,
    state,
    loading,
    error,
    category,
    search,
    selected,
    variation,
    quantity,
    openingFromKeyboard,
    heroProduct,
    galleryImages,
    quantityFor,
    quickAdd,
    changeProduct,
    cartOpen,
    busy,
    channel,
    note,
    name,
    tracking,
    boot,
    enabled,
    products,
    total,
    count,
    style,
    menuTheme,
    customCss,
    money: customerMoney,
    trap,
    open,
    openCart,
    close,
    add,
    change,
    submit,
  };
}
