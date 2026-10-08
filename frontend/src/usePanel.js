import { translate } from "./i18n.js";
import { ref, computed, onMounted, onUnmounted, nextTick } from "vue";
import QRCode from "qrcode";
import { api } from "./api.js";
import { money, sortCategory } from "./domain.js";

const tabs = [
  ["branches", "شعبه‌ها", "▦", "central"],
  ["overview", "نمای کلی", "◫", "admincafe_view_reports"],
  ["orders", "سفارش‌ها", "◷", "admincafe_manage_orders"],
  ["products", "محصولات", "☕", "admincafe_manage_menu"],
  ["categories", "دسته‌بندی‌ها", "▦", "admincafe_manage_menu"],
  ["tables", "میزها و QR", "▤", "admincafe_manage_tables"],
  ["import", "واردسازی", "↥", "admincafe_manage_menu"],
  ["staff", "همکاران", "♧", "admincafe_manage_staff"],
  ["reports", "گزارش‌ها", "▥", "admincafe_view_reports"],
  ["settings", "تنظیمات", "⚙", "admincafe_manage_settings"],
  ["notifications", "اعلان‌ها", "♧", "admincafe_receive_notifications"],
];

export function usePanel(config, root) {
  const t = (message) => translate(message, config);
  const client = api(config),
    bootstrap = ref({
      settings: {},
      capabilities: [],
      user: {},
      pages: [],
      roles: [],
    }),
    branchId = ref(Number(config.branchId) || 0),
    branchEpoch = ref(0),
    branchReady = ref(false),
    tab = ref("overview"),
    busy = ref(false),
    error = ref(""),
    notice = ref(""),
    data = ref([]),
    products = ref([]),
    categories = ref([]),
    reports = ref({}),
    settings = ref({}),
    edit = ref(null),
    form = ref({}),
    search = ref(""),
    stage = ref(""),
    filterCategory = ref(0),
    events = ref([]),
    unread = ref(0),
    push = ref({}),
    devices = ref([]),
    preview = ref(null),
    mapping = ref({}),
    importMode = ref("upsert"),
    days = ref(7),
    drag = ref(null);
  let poll,
    focus,
    notificationReady = false,
    lastEvent = 0;
  const page = ref(1),
    pages = ref(1),
    orderHighlight = ref(0);
  const pushChannels = ref(["table", "pickup", "delivery", "counter"]);
  const canCashier = computed(() =>
    bootstrap.value.user.roles?.some((r) =>
      ["administrator", "admincafe_manager", "admincafe_cashier"].includes(r),
    ),
  );
  const canRefund = computed(() =>
    bootstrap.value.user.roles?.some((r) =>
      ["administrator", "admincafe_manager"].includes(r),
    ),
  );
  const paymentLabels = {
    pending: "منتظر پرداخت",
    "on-hold": "در انتظار تسویه",
    processing: "پرداخت‌شده",
    completed: "تسویه‌شده",
    cancelled: "لغوشده",
    refunded: "بازپرداخت‌شده",
    failed: "پرداخت ناموفق",
  };
  const currencyOptions = computed(() => {
    const currencies = bootstrap.value.currencies || [];
    return Array.isArray(currencies)
      ? currencies.map((c) => ({
          code: c.code || c.id,
          name: c.name || c.label || c.code || c.id,
        }))
      : Object.entries(currencies).map(([code, name]) => ({ code, name }));
  });
  const allowed = computed(() =>
    tabs.filter((t) =>
      t[3] === "central"
        ? bootstrap.value.can_manage_branches
        : bootstrap.value.capabilities.includes(t[3]),
    ),
  );
  const title = computed(() =>
    t(tabs.find((item) => item[0] === tab.value)?.[1] || ""),
  );
  const filtered = computed(() =>
    sortCategory(
      data.value.filter(
        (x) =>
          (!search.value ||
            `${x.name || x.label || x.number || x.display_name || ""}`.includes(
              search.value,
            )) &&
          (!filterCategory.value ||
            x.category_ids?.includes(Number(filterCategory.value))),
      ),
      categories.value.find((c) => c.id === Number(filterCategory.value))
        ?.product_order || [],
    ),
  );
  const stages = {
    awaiting_approval: "منتظر تأیید",
    accepted: "تأیید شده",
    preparing: "در حال آماده‌سازی",
    ready: "آماده تحویل",
    delivered: "تحویل شده",
    cancelled: "لغو شده",
  };
  async function run(fn) {
    const epoch = branchEpoch.value;
    busy.value = true;
    error.value = "";
    notice.value = "";
    try {
      return await fn();
    } catch (e) {
      if (epoch === branchEpoch.value && e.code !== "stale_branch")
        error.value = e.message;
      return false;
    } finally {
      if (epoch === branchEpoch.value) busy.value = false;
    }
  }
  async function load() {
    const epoch = branchEpoch.value;
    return run(async () => {
      if (tab.value === "branches") {
        data.value = await client.request("/manage/branches");
      } else if (["overview", "reports"].includes(tab.value)) {
        reports.value = await client.request(
          "/manage/reports?days=" + days.value,
        );
        if (tab.value === "overview") {
          const r = await client.request("/manage/orders");
          data.value = r.orders || [];
        }
      } else if (tab.value === "settings") {
        settings.value = { ...(await client.request("/manage/settings")) };
        categories.value = await client.request("/manage/categories");
      } else if (tab.value === "notifications") {
        await notifications();
        devices.value = await client.request("/manage/push/devices");
      } else if (
        ["orders", "products", "categories", "tables", "staff"].includes(
          tab.value,
        )
      ) {
        const r = await client.request(
          "/manage/" +
            tab.value +
            (tab.value === "orders"
              ? "?stage=" + stage.value + "&page=" + page.value
              : ""),
        );
        data.value = Array.isArray(r) ? r : r.orders || r.items || [];
        if (tab.value === "orders") pages.value = r.pages || 1;
        if (tab.value === "products") {
          products.value = data.value;
          categories.value = await client.request("/manage/categories");
        }
      }
      return epoch === branchEpoch.value;
    });
  }
  async function openEvent(event) {
    const epoch = branchEpoch.value;
    await go("orders");
    if (epoch !== branchEpoch.value) return;
    window.location.hash = "orders/" + event.order_id;
    orderHighlight.value = Number(event.order_id);
    await run(async () => {
      const detail = await client.request("/manage/orders/" + event.order_id);
      open(detail, "orderDetails");
    });
  }
  async function go(t) {
    tab.value = t;
    page.value = 1;
    search.value = "";
    filterCategory.value = 0;
    if (!(await load())) return;
  }
  function open(item = null, kind = tab.value) {
    focus = document.activeElement;
    edit.value = { kind, id: item?.id || 0 };
    form.value = item
      ? JSON.parse(JSON.stringify(item))
      : {
          name: "",
          label: "",
          description: "",
          price: "",
          sku: "",
          category_ids: [],
          available: true,
          visible: true,
          mode: "inherit",
          enabled: true,
          parent: 0,
          image_id: 0,
          icon: "",
          username: "",
          email: "",
          password: "",
          role: "admincafe_staff",
          items: [],
          channel: "counter",
          slug: "",
          branch_ids: [branchId.value],
        };
    if (kind === "staff" && item)
      form.value.name = item.name || item.display_name;
    if (kind === "products" && form.value.visible === undefined)
      form.value.visible = true;
    nextTick(() =>
      root.querySelector(".ac-dialog input,.ac-dialog button")?.focus(),
    );
  }
  function close() {
    edit.value = null;
    nextTick(() => focus?.focus());
  }
  async function save() {
    await run(async () => {
      const k = edit.value.kind,
        id = edit.value.id;
      const body = { ...form.value };
      if (k === "products") {
        if (body.regular_price !== undefined) delete body.price;
        if (body.variations)
          body.variation_translations = Object.fromEntries(
            body.variations
              .filter((v) => v.translations)
              .map((v) => [v.id, v.translations]),
          );
        if (body.variations)
          body.variations = body.variations.map((v) => ({
            id: v.id,
            price: v.price,
            available: v.available,
          }));
      }
      if (k === "staff") {
        body.name = body.name || body.display_name;
        if (!body.password) delete body.password;
        if (!bootstrap.value.can_manage_branches) delete body.branch_ids;
      }
      if (k === "orders") body.items = body.items.filter((i) => i.quantity > 0);
      await client.request(
        "/manage/" + k + (id ? "/" + id : ""),
        id ? "PATCH" : "POST",
        body,
      );
      close();
      if (k === "branches") await refreshBootstrap();
      if (!(await load())) return;
      notice.value = "تغییرات ذخیره شد.";
    });
  }
  async function remove(x) {
    if (!window.confirm("این مورد حذف شود؟")) return;
    await run(async () => {
      await client.request("/manage/" + tab.value + "/" + x.id, "DELETE");
      if (!(await load())) return;
    });
  }
  async function orderAction(o, payload) {
    await run(async () => {
      await client.request("/manage/orders/" + o.id, "PATCH", payload);
      if (!(await load())) return;
      notice.value = "وضعیت سفارش به‌روزرسانی شد.";
    });
  }
  async function quickPrice(p) {
    await run(async () => {
      await client.request("/manage/products/" + p.id, "PATCH", {
        price: p.price,
        regular_price: p.price,
      });
      notice.value = "قیمت ذخیره شد.";
    });
  }
  async function drop(to) {
    if (drag.value === null || !filterCategory.value) return;
    if (search.value) {
      error.value = "برای تغییر ترتیب، جستجو را پاک کنید.";
      return;
    }
    const list = [...filtered.value],
      from = list.findIndex((x) => x.id === drag.value),
      index = list.findIndex((x) => x.id === to.id);
    const [moved] = list.splice(from, 1);
    list.splice(index, 0, moved);
    await run(async () => {
      await client.request("/manage/reorder", "POST", {
        category_id: Number(filterCategory.value),
        ids: list.map((x) => x.id),
      });
      data.value = [...list, ...data.value.filter((x) => !list.includes(x))];
      const category = categories.value.find(
        (c) => c.id === Number(filterCategory.value),
      );
      if (category) category.product_order = list.map((x) => x.id);
      notice.value = "ترتیب دسته ذخیره شد.";
    });
    drag.value = null;
  }
  async function media(e, target = "form", field = "image_id") {
    const file = e.target.files[0];
    if (!file) return;
    await run(async () => {
      const body = new FormData();
      body.append("file", file);
      const r = await client.request("/manage/media", "POST", body);
      (target === "settings" ? settings.value : form.value)[field] = r.id;
      if (target !== "settings") form.value.image = r.url;
    });
  }
  async function qr(t, type) {
    await run(async () => {
      const opts = {
        width: Number(settings.value.qr_size || 512),
        margin: 2,
        color: { dark: settings.value.qr_color || "#242424", light: "#ffffff" },
      };
      const content =
        type === "svg"
          ? await QRCode.toString(t.url, { ...opts, type: "svg" })
          : await QRCode.toDataURL(t.url, opts);
      const url =
        type === "svg"
          ? URL.createObjectURL(new Blob([content], { type: "image/svg+xml" }))
          : content;
      const a = document.createElement("a");
      a.href = url;
      a.download = `table-${t.id}.${type}`;
      a.click();
      if (type === "svg") URL.revokeObjectURL(url);
    });
  }
  async function importFile(e) {
    if (!e.target.files[0]) return;
    await run(async () => {
      const body = new FormData();
      body.append("file", e.target.files[0]);
      preview.value = await client.request(
        "/manage/import/preview",
        "POST",
        body,
      );
      mapping.value = {};
    });
  }
  async function applyImport() {
    await run(async () => {
      const payload = {
        token: preview.value.token,
        mapping: JSON.parse(JSON.stringify(mapping.value)),
        mode: importMode.value,
      };
      let r;
      for (let batch = 0; batch < 50; batch++) {
        r = await client.request("/manage/import/apply", "POST", payload);
        notice.value = `در حال واردسازی • ${r.remaining || 0} ردیف باقی‌مانده`;
        if (r.done !== false) break;
        if (batch === 49)
          throw new Error(
            "واردسازی ادامه دارد؛ دوباره اعمال کنید تا تکمیل شود.",
          );
      }
      notice.value = `ایجاد: ${r.created} • ویرایش: ${r.updated} • رد شده: ${r.skipped}`;
      if (r.errors?.length)
        error.value = r.errors
          .map((e) => (typeof e === "string" ? e : JSON.stringify(e)))
          .join("، ");
      preview.value = null;
    });
  }
  async function notifications() {
    const r = await client.request("/manage/notifications");
    const fresh = (r.events || []).filter((e) => Number(e.id) > lastEvent);
    if (notificationReady && fresh.length) {
      notice.value = fresh[0].title || "سفارش تازه دریافت شد";
      if (settings.value.notification_sound) {
        try {
          const audio = new (
            window.AudioContext || window.webkitAudioContext
          )();
          const osc = audio.createOscillator(),
            gain = audio.createGain();
          osc.connect(gain);
          gain.connect(audio.destination);
          osc.frequency.value = 660;
          gain.gain.value = 0.08;
          osc.start();
          osc.stop(audio.currentTime + 0.18);
          osc.onended = () => audio.close();
        } catch {}
      }
      if (tab.value === "orders") {
        const queue = await client.request(
          "/manage/orders?stage=" + stage.value,
        );
        data.value = queue.orders || [];
      }
    }
    lastEvent = Math.max(
      lastEvent,
      ...(r.events || []).map((e) => Number(e.id)),
      0,
    );
    notificationReady = true;
    events.value = r.events || [];
    unread.value = r.unread || 0;
    push.value = r.push || bootstrap.value.push || {};
  }
  async function readEvents() {
    await run(async () => {
      await client.request("/manage/notifications/read", "POST", {
        ids: events.value.filter((e) => !e.read).map((e) => e.id),
      });
      await notifications();
    });
  }
  async function subscribe() {
    const epoch = branchEpoch.value;
    await run(async () => {
      if (!push.value.available)
        throw new Error(push.value.reason || "ارسال پوش هنوز آماده نیست");
      if (
        !("serviceWorker" in navigator) ||
        !("PushManager" in window) ||
        !window.isSecureContext
      )
        throw new Error("اعلان مرورگر به HTTPS و مرورگر سازگار نیاز دارد.");
      if ((await Notification.requestPermission()) !== "granted")
        throw new Error("اجازه اعلان داده نشد");
      if (epoch !== branchEpoch.value) return;
      const reg = await navigator.serviceWorker.register(
        config.serviceWorkerUrl ||
          `${config.panelUrl.replace(/\/$/, "")}/sw.js`,
        { scope: config.panelUrl.replace(/\/$/, "") + "/" },
      );
      if (epoch !== branchEpoch.value) return;
      const key = push.value.public_key,
        padded = (key + "=".repeat((4 - (key.length % 4)) % 4))
          .replace(/-/g, "+")
          .replace(/_/g, "/"),
        bytes = Uint8Array.from(atob(padded), (c) => c.charCodeAt(0));
      const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: bytes,
      });
      if (epoch !== branchEpoch.value) return;
      await client.request("/manage/push/subscribe", "POST", {
        subscription: sub.toJSON(),
        label: navigator.userAgent.slice(0, 100),
        channels: pushChannels.value,
      });
      devices.value = await client.request("/manage/push/devices");
      notice.value = "اعلان این دستگاه فعال شد.";
    });
  }
  async function saveDevice(d) {
    await run(async () => {
      await client.request(
        "/manage/push/devices/" + encodeURIComponent(d.id),
        "PATCH",
        { channels: d.channels },
      );
      notice.value = "تنظیمات دستگاه ذخیره شد.";
    });
  }
  async function unsubscribe(d) {
    await run(async () => {
      await client.request("/manage/push/subscribe", "DELETE", {
        endpoint: d.endpoint,
      });
      devices.value = await client.request("/manage/push/devices");
    });
  }
  async function saveSettings() {
    await run(async () => {
      const payload = { ...settings.value };
      if (!bootstrap.value.can_manage_branches)
        for (const key of ["menu_slug", "panel_slug", "currency_code"])
          delete payload[key];
      settings.value = await client.request(
        "/manage/settings",
        "POST",
        payload,
      );
      notice.value = "تنظیمات ذخیره شد.";
    });
  }
  async function counter() {
    products.value = await client.request("/manage/products");
    open(null, "orders");
  }
  function trap(e) {
    const nodes = [
      ...e.currentTarget.querySelectorAll("button,input,select,textarea"),
    ].filter((n) => !n.disabled);
    if (e.shiftKey && document.activeElement === nodes[0]) {
      e.preventDefault();
      nodes.at(-1)?.focus();
    } else if (!e.shiftKey && document.activeElement === nodes.at(-1)) {
      e.preventDefault();
      nodes[0]?.focus();
    }
  }
  const selectableBranches = computed(() =>
    (bootstrap.value.branches || []).filter(
      (b) => bootstrap.value.can_manage_branches || b.enabled !== false,
    ),
  );
  const branch = computed(
    () =>
      bootstrap.value.branches?.find((b) => Number(b.id) === branchId.value) ||
      bootstrap.value.branch ||
      {},
  );
  function preferenceKey() {
    return `admincafe-panel-branch:${config.apiBase || "demo"}:${bootstrap.value.user.id || ""}`;
  }
  async function refreshBootstrap() {
    bootstrap.value = await client.request("/manage/bootstrap");
    bootstrap.value.capabilities = Array.isArray(bootstrap.value.capabilities)
      ? bootstrap.value.capabilities
      : Object.keys(bootstrap.value.capabilities || {}).filter(
          (k) => bootstrap.value.capabilities[k],
        );
    branchId.value =
      Number(bootstrap.value.branch_id) ||
      Number(bootstrap.value.branches?.[0]?.id) ||
      0;
    client.setBranch(branchId.value);
    settings.value = { ...bootstrap.value.settings };
    push.value = bootstrap.value.push || {};
    branchReady.value = true;
  }
  async function startNotifications() {
    const epoch = branchEpoch.value;
    if (
      bootstrap.value.capabilities.includes("admincafe_receive_notifications")
    ) {
      await notifications();
      if (epoch !== branchEpoch.value) return;
      clearInterval(poll);
      poll = setInterval(() => notifications().catch(() => {}), 15000);
    }
  }
  async function switchBranch(id) {
    if (!selectableBranches.value.some((b) => Number(b.id) === Number(id)))
      return;
    clearInterval(poll);
    branchEpoch.value++;
    const epoch = branchEpoch.value;
    branchReady.value = false;
    branchId.value = Number(id);
    client.invalidate();
    client.setBranch(id);
    edit.value = null;
    form.value = {};
    data.value = [];
    products.value = [];
    categories.value = [];
    reports.value = {};
    settings.value = {};
    events.value = [];
    devices.value = [];
    push.value = {};
    unread.value = 0;
    preview.value = null;
    mapping.value = {};
    search.value = "";
    stage.value = "";
    filterCategory.value = 0;
    page.value = 1;
    pages.value = 1;
    orderHighlight.value = 0;
    drag.value = null;
    lastEvent = 0;
    notificationReady = false;
    await run(async () => {
      try {
        await refreshBootstrap();
      } catch (exception) {
        if (epoch !== branchEpoch.value) throw exception;
        if (
          ![403, 404].includes(exception.status) &&
          !["branch_disabled", "branch"].includes(exception.code)
        )
          throw exception;
        // Assignments may have changed since the branch list or saved preference was read.
        client.setBranch(0);
        await refreshBootstrap();
        notice.value =
          "دسترسی به شعبه قبلی در دسترس نیست؛ شعبه مجاز انتخاب شد.";
      }
      try {
        localStorage.setItem(preferenceKey(), String(branchId.value));
      } catch {}
      if (!allowed.value.some((item) => item[0] === tab.value))
        tab.value = allowed.value[0]?.[0] || "orders";
      if (!(await load()) || epoch !== branchEpoch.value) return;
      await startNotifications();
    });
  }
  async function setBranchEnabled(item) {
    await run(async () => {
      await client.request("/manage/branches/" + item.id, "PATCH", {
        enabled: !item.enabled,
      });
      await refreshBootstrap();
      if (!(await load())) return;
    });
  }
  async function copyBranch(item) {
    await run(async () => {
      await navigator.clipboard.writeText(item.menu_url);
      notice.value = "پیوند منو کپی شد.";
    });
  }
  onMounted(async () => {
    await run(async () => {
      try {
        await refreshBootstrap();
      } catch (exception) {
        if (![403, 404].includes(exception.status)) throw exception;
        client.setBranch(0);
        await refreshBootstrap();
      }
      let saved = 0;
      try {
        saved = Number(localStorage.getItem(preferenceKey()));
      } catch {}
      tab.value =
        allowed.value.find((item) => item[0] !== "branches")?.[0] ||
        allowed.value[0]?.[0] ||
        "orders";
      if (
        window.location.hash.startsWith("#orders") &&
        bootstrap.value.capabilities.includes("admincafe_manage_orders")
      )
        tab.value = "orders";
      if (
        saved &&
        saved !== branchId.value &&
        selectableBranches.value.some((b) => Number(b.id) === saved)
      ) {
        await switchBranch(saved);
        return;
      }
      if (!(await load())) return;
      await startNotifications();
    });
  });
  onUnmounted(() => clearInterval(poll));
  return {
    t,
    branchId,
    selectableBranches,
    branch,
    branchEpoch,
    branchReady,
    switchBranch,
    setBranchEnabled,
    copyBranch,
    currencyOptions,
    page,
    pages,
    orderHighlight,
    openEvent,
    pushChannels,
    canCashier,
    canRefund,
    config,
    bootstrap,
    tab,
    busy,
    error,
    notice,
    data,
    products,
    categories,
    reports,
    settings,
    edit,
    form,
    search,
    stage,
    filterCategory,
    events,
    unread,
    push,
    devices,
    preview,
    mapping,
    importMode,
    days,
    drag,
    allowed,
    title,
    filtered,
    stages,
    paymentLabels,
    money,
    go,
    load,
    open,
    close,
    save,
    remove,
    orderAction,
    quickPrice,
    drop,
    media,
    qr,
    importFile,
    applyImport,
    readEvents,
    subscribe,
    unsubscribe,
    saveDevice,
    saveSettings,
    counter,
    trap,
    run,
    client,
  };
}
