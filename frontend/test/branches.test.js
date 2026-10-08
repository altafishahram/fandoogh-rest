import { test } from "node:test";
import assert from "node:assert/strict";
import { api } from "../src/api.js";
import { shared, stores } from "../src/state.js";
import { branchCheckout } from "../src/branch-context.js";
import { usePanel } from "../src/usePanel.js";

test("branch API captures context and discards responses after a branch switch", async () => {
  const original = globalThis.fetch;
  let complete, observed;
  globalThis.fetch = (url, options) => {
    observed = { url, options };
    return new Promise((resolve) => {
      complete = resolve;
    });
  };
  try {
    const client = api({ apiBase: "/api", branchId: 2 });
    const pending = client.request("/manage/products?page=3", "POST", {
      name: "Coffee",
    });
    assert.equal(
      new URL(observed.url, "https://test").searchParams.get("branch_id"),
      "2",
    );
    assert.equal(JSON.parse(observed.options.body).branch_id, 2);
    client.setBranch(4);
    complete({ ok: true, json: async () => [{ id: 1 }] });
    await assert.rejects(pending, (error) => error.code === "stale_branch");
    globalThis.fetch = async () => ({
      ok: false,
      json: async () => ({
        code: "cart_branch_conflict",
        message: "Other branch",
        data: { status: 409 },
      }),
    });
    await assert.rejects(
      client.request("/checkout", "POST", {}),
      (error) =>
        error.code === "cart_branch_conflict" && error.data.status === 409,
    );
  } finally {
    globalThis.fetch = original;
  }
});

test("cart, language gate, order request and tracking share only the same branch and table", () => {
  const original = globalThis.localStorage;
  const values = new Map();
  globalThis.localStorage = {
    getItem: (key) => values.get(key) || null,
    setItem: (key, value) => values.set(key, value),
  };
  stores.clear();
  try {
    const a = shared({
      apiBase: "/site-a",
      branchId: 1,
      tableToken: "table-a",
    });
    a.state.cart.push({ product_id: 8, quantity: 2 });
    a.state.pendingOrderId = "request-a";
    a.state.tracking = { tracking_token: "track-a" };
    a.state.languageEntered = true;
    a.persist();
    assert.equal(
      shared({ apiBase: "/site-a", branchId: 1, tableToken: "table-a" }).state,
      a.state,
    );
    for (const config of [
      { apiBase: "/site-a", branchId: 2, tableToken: "table-a" },
      { apiBase: "/site-b", branchId: 1, tableToken: "table-a" },
      { apiBase: "/site-a", branchId: 1, tableToken: "table-b" },
    ]) {
      const b = shared(config).state;
      assert.deepEqual(b.cart, []);
      assert.equal(b.tracking, null);
      assert.equal(b.pendingOrderId, "");
      assert.equal(b.languageEntered, false);
    }
    stores.clear();
    const restored = shared({
      apiBase: "/site-a",
      branchId: 1,
      tableToken: "table-a",
    }).state;
    assert.equal(restored.cart[0].quantity, 2);
    assert.equal(restored.tracking.tracking_token, "track-a");
    assert.equal(restored.pendingOrderId, "request-a");
  } finally {
    stores.clear();
    globalThis.localStorage = original;
  }
});

test("checkout replacement requires affirmative customer consent and never retries other errors", async () => {
  for (const consent of [false, true]) {
    const calls = [];
    let confirmations = 0;
    const client = {
      request: async (path, method, body) => {
        calls.push(body);
        if (calls.length === 1) {
          const e = new Error("conflict");
          e.code = "cart_branch_conflict";
          throw e;
        }
        return { url: "/checkout" };
      },
    };
    const result = await branchCheckout(
      client,
      { branch_id: 2, channel: "pickup" },
      () => {
        confirmations++;
        return consent;
      },
    );
    assert.equal(confirmations, 1);
    assert.equal(calls.length, consent ? 2 : 1);
    assert.equal(result?.url || null, consent ? "/checkout" : null);
    if (consent) assert.equal(calls[1].replace_cart, true);
  }
  let confirmation = false;
  await assert.rejects(
    branchCheckout(
      {
        request: async () => {
          throw new Error("offline");
        },
      },
      {},
      () => {
        confirmation = true;
      },
    ),
    /offline/,
  );
  assert.equal(confirmation, false);
});

test("panel switch clears forms and rejects pending old-branch data", async () => {
  const originalFetch = globalThis.fetch,
    originalStorage = globalThis.localStorage;
  let finishOld;
  globalThis.localStorage = {
    setItem() {},
    getItem() {
      return null;
    },
  };
  const branches = [
    { id: 1, name: "A", enabled: true },
    { id: 2, name: "B", enabled: true },
  ];
  globalThis.fetch = async (url) => {
    const parsed = new URL(url, "https://test"),
      id = Number(parsed.searchParams.get("branch_id"));
    if (parsed.pathname === "/api/manage/products" && id === 1)
      return new Promise((resolve) => {
        finishOld = () =>
          resolve({ ok: true, json: async () => [{ id: 101 }] });
      });
    let value = [];
    if (parsed.pathname.endsWith("/bootstrap"))
      value = {
        branch_id: id,
        branches,
        user: { id: 3, roles: [] },
        settings: { restaurant_name: id === 2 ? "B" : "A" },
        capabilities: ["admincafe_manage_menu"],
      };
    if (parsed.pathname.endsWith("/products")) value = [{ id: 202 }];
    return { ok: true, json: async () => value };
  };
  try {
    const panel = usePanel(
      { apiBase: "/api", branchId: 1 },
      { querySelector() {} },
    );
    panel.bootstrap.value = {
      branches,
      capabilities: ["admincafe_manage_menu"],
      user: { id: 3, roles: [] },
      settings: {},
    };
    panel.tab.value = "products";
    panel.edit.value = { kind: "products", id: 101 };
    panel.form.value = { name: "Old branch" };
    const old = panel.load();
    await panel.switchBranch(2);
    assert.equal(panel.edit.value, null);
    assert.deepEqual(panel.form.value, {});
    assert.equal(panel.data.value[0].id, 202);
    finishOld();
    await old;
    assert.equal(panel.data.value[0].id, 202);
    assert.equal(panel.error.value, "");
    assert.equal(panel.branch.value.name, "B");
  } finally {
    globalThis.fetch = originalFetch;
    globalThis.localStorage = originalStorage;
  }
});

test("overlapping switches cannot restart old notification polls after a stale nested load", async () => {
  const originals = {
    fetch: globalThis.fetch,
    localStorage: globalThis.localStorage,
    setInterval: globalThis.setInterval,
    clearInterval: globalThis.clearInterval,
  };
  let finishOld, beginOld;
  const oldStarted = new Promise((resolve) => {
    beginOld = resolve;
  });
  let polls = 0,
    notifications = 0;
  globalThis.localStorage = {
    getItem() {
      return null;
    },
    setItem() {},
  };
  globalThis.setInterval = () => ++polls;
  globalThis.clearInterval = () => {};
  const branches = [1, 2, 3].map((id) => ({
    id,
    name: String(id),
    enabled: true,
  }));
  globalThis.fetch = async (url) => {
    const u = new URL(url, "https://test"),
      id = Number(u.searchParams.get("branch_id"));
    if (u.pathname.endsWith("/products") && id === 2)
      return new Promise((resolve) => {
        finishOld = () => resolve({ ok: true, json: async () => [{ id: 2 }] });
        beginOld();
      });
    let data = [];
    if (u.pathname.endsWith("/bootstrap"))
      data = {
        branch_id: id,
        branches,
        user: { id: 4, roles: [] },
        settings: {},
        capabilities: [
          "admincafe_manage_menu",
          "admincafe_receive_notifications",
        ],
      };
    if (u.pathname.endsWith("/products")) data = [{ id }];
    if (u.pathname.endsWith("/notifications")) {
      notifications++;
      data = { events: [], unread: 0 };
    }
    return { ok: true, json: async () => data };
  };
  try {
    const panel = usePanel(
      { apiBase: "/api", branchId: 1 },
      { querySelector() {} },
    );
    panel.bootstrap.value = {
      branches,
      capabilities: ["admincafe_manage_menu"],
      user: { id: 4, roles: [] },
      settings: {},
    };
    panel.tab.value = "products";
    const old = panel.switchBranch(2);
    await oldStarted;
    await panel.switchBranch(3);
    finishOld();
    await old;
    assert.equal(panel.branchId.value, 3);
    assert.equal(panel.data.value[0].id, 3);
    assert.equal(notifications, 1);
    assert.equal(polls, 1);
  } finally {
    Object.assign(globalThis, originals);
  }
});

test("revoked branch preference recovers through a context-free bootstrap and disables manager choices", async () => {
  let finishRejected;
  const originalFetch = globalThis.fetch,
    originalStorage = globalThis.localStorage;
  const requests = [],
    saved = new Map();
  globalThis.localStorage = {
    getItem: (key) => saved.get(key) || null,
    setItem: (key, value) => saved.set(key, value),
  };
  const branches = [
    { id: 1, name: "Allowed", enabled: true },
    { id: 2, name: "Revoked", enabled: true },
    { id: 3, name: "Disabled", enabled: false },
  ];
  globalThis.fetch = async (url) => {
    const u = new URL(url, "https://test"),
      id = Number(u.searchParams.get("branch_id"));
    requests.push(id);
    if (u.pathname.endsWith("/bootstrap") && id === 2)
      return new Promise((resolve) => {
        finishRejected = () =>
          resolve({
            ok: false,
            status: 403,
            json: async () => ({
              message: "Revoked",
              code: "forbidden",
              data: { status: 403 },
            }),
          });
      });
    const data = u.pathname.endsWith("/bootstrap")
      ? {
          branch_id: 1,
          branches: [branches[0], branches[2]],
          can_manage_branches: false,
          user: { id: 5, roles: [] },
          settings: {},
          capabilities: ["admincafe_manage_menu"],
        }
      : [];
    return { ok: true, json: async () => data };
  };
  try {
    const panel = usePanel(
      { apiBase: "/api", branchId: 1 },
      { querySelector() {} },
    );
    panel.bootstrap.value = {
      branches,
      can_manage_branches: false,
      capabilities: ["admincafe_manage_menu"],
      user: { id: 5, roles: [] },
      settings: {},
    };
    panel.tab.value = "products";
    panel.branchReady.value = true;
    const switching = panel.switchBranch(2);
    assert.equal(panel.branchReady.value, false);
    finishRejected();
    await switching;
    assert.equal(panel.branchReady.value, true);
    assert.equal(panel.branchId.value, 1);
    assert.deepEqual(requests.slice(0, 2), [2, 0]);
    assert.deepEqual(
      panel.selectableBranches.value.map((b) => b.id),
      [1],
    );
    assert.equal(saved.get("admincafe-panel-branch:/api:5"), "1");
    await panel.switchBranch(3);
    assert.equal(panel.branchId.value, 1);
  } finally {
    globalThis.fetch = originalFetch;
    globalThis.localStorage = originalStorage;
  }
});

test("tab navigation clears previous cards immediately and rejects delayed responses from older views", async () => {
  const original = globalThis.fetch;
  let notifyCategoryStarted;
  const categoryStarted = new Promise((resolve) => {
    notifyCategoryStarted = resolve;
  });
  const pending = new Map(),
    calls = [];
  globalThis.fetch = (url) => {
    const path = new URL(url, "https://test").pathname;
    calls.push(path);
    if (path === "/api/manage/categories") notifyCategoryStarted();
    return new Promise((resolve) =>
      pending.set(path, (value) =>
        resolve({ ok: true, json: async () => value }),
      ),
    );
  };
  try {
    const panel = usePanel(
      { apiBase: "/api", branchId: 1 },
      { querySelector() {} },
    );
    panel.bootstrap.value = {
      branches: [{ id: 1, name: "Main", enabled: true }],
      can_manage_branches: true,
      user: { id: 1, roles: [] },
      settings: {},
      capabilities: ["admincafe_manage_menu"],
    };
    panel.tab.value = "overview";
    panel.data.value = [{ id: 88, number: "Order" }];
    panel.edit.value = { kind: "orderDetails", id: 88 };
    const overview = panel.load();
    const branches = panel.go("branches");
    assert.deepEqual(panel.data.value, []);
    assert.equal(panel.edit.value, null);
    pending.get("/api/manage/reports")({ revenue: 999 });
    await overview;
    assert.deepEqual(panel.reports.value, {});
    assert.equal(panel.busy.value, true);
    assert.equal(calls.includes("/api/manage/orders"), false);
    const products = panel.go("products");
    pending.get("/api/manage/branches")([{ id: 99, name: "Old branch" }]);
    await branches;
    assert.deepEqual(panel.data.value, []);
    assert.equal(panel.busy.value, true);
    pending.get("/api/manage/products")([{ id: 7, name: "Coffee" }]);
    // Allow the product loader to request its categories before leaving the view.
    await categoryStarted;
    const tables = panel.go("tables");
    assert.deepEqual(panel.data.value, []);
    assert.deepEqual(panel.products.value, []);
    pending.get("/api/manage/categories")([{ id: 66, name: "Old category" }]);
    await products;
    assert.deepEqual(panel.categories.value, []);
    assert.equal(panel.busy.value, true);
    pending.get("/api/manage/tables")([{ id: 4, label: "Table" }]);
    await tables;
    assert.deepEqual(
      panel.data.value.map((item) => item.id),
      [4],
    );
    assert.equal(panel.busy.value, false);
    assert.equal(panel.error.value, "");
  } finally {
    globalThis.fetch = original;
  }
});

test("a failed request from a previous tab cannot overwrite the current view's error", async () => {
  const original = globalThis.fetch;
  let rejectOld;
  globalThis.fetch = (url) =>
    new URL(url, "https://test").pathname.endsWith("/products")
      ? new Promise((resolve, reject) => {
          rejectOld = reject;
        })
      : Promise.resolve({
          ok: true,
          json: async () => [{ id: 2, name: "Current branch" }],
        });
  try {
    const panel = usePanel(
      { apiBase: "/api", branchId: 1 },
      { querySelector() {} },
    );
    panel.tab.value = "products";
    const old = panel.load();
    await panel.go("branches");
    rejectOld(new Error("Old view network failure"));
    await old;
    assert.equal(panel.error.value, "");
    assert.equal(panel.data.value[0].name, "Current branch");
    assert.equal(panel.busy.value, false);
  } finally {
    globalThis.fetch = original;
  }
});
