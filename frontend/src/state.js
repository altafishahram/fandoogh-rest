import { reactive } from "vue";
export const stores = new Map();
function read(key) {
  try {
    return localStorage.getItem(key);
  } catch {
    return null;
  }
}
export function shared(config) {
  const key = `${config.apiBase || "demo"}:${config.tableToken || "default"}`;
  if (!stores.has(key)) {
    let cart = [];
    try {
      cart = JSON.parse(
        localStorage.getItem(`admincafe:${key}`) || "[]",
      ).filter((i) => i.quantity > 0 && i.product_id);
    } catch {}
    stores.set(
      key,
      reactive({
        cart,
        category: 0,
        pendingOrderId: read(`admincafe-request:${key}`) || "",
        bootstrap: null,
        language: "",
        languageEntered: false,
        languageGateOwner: "",
        languageGateMembers: [],
        languageRequest: 0,
        loadingLanguage: "",
        languagePromise: null,
        bootstrapLoading: true,
        bootstrapError: "",
      }),
    );
  }
  const state = stores.get(key);
  return {
    state,
    persist() {
      try {
        localStorage.setItem(`admincafe:${key}`, JSON.stringify(state.cart));
        localStorage.setItem(`admincafe-request:${key}`, state.pendingOrderId);
      } catch {}
    },
    key,
  };
}
