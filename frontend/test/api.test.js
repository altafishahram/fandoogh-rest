import { test } from "node:test";
import assert from "node:assert/strict";
import { api } from "../src/api.js";

test("guest bootstrap omits empty WordPress nonce to avoid cookie-check rejection", async () => {
  const original = globalThis.fetch;
  let headers;
  globalThis.fetch = async (url, options) => {
    headers = options.headers;
    return { ok: true, json: async () => ({ csrf_token: "guest-token" }) };
  };
  try {
    await api({ apiBase: "/wp-json/admincafe/v1", nonce: "" }).request(
      "/bootstrap",
    );
    assert.equal(Object.hasOwn(headers, "X-WP-Nonce"), false);
    assert.equal(Object.hasOwn(headers, "X-AdminCafe-Token"), false);
  } finally {
    globalThis.fetch = original;
  }
});

test("production requests carry session token and credentials; forbidden response is surfaced", async () => {
  const original = globalThis.fetch;
  let observed;
  globalThis.fetch = async (url, options) => {
    observed = { url, options };
    return {
      ok: false,
      json: async () => ({ message: "اجازه دسترسی ندارید" }),
    };
  };
  try {
    const client = api({
      apiBase: "https://cafe.test/wp-json/admincafe/v1/",
      nonce: "management-nonce",
    });
    client.setToken("guest-token");
    await assert.rejects(
      () => client.request("/orders/table", "POST", { items: [] }),
      /اجازه دسترسی/,
    );
    assert.equal(observed.options.credentials, "same-origin");
    assert.equal(observed.options.headers["X-AdminCafe-Token"], "guest-token");
    assert.equal(observed.options.headers["X-WP-Nonce"], "management-nonce");
    assert.equal(observed.options.cache, "no-store");
  } finally {
    globalThis.fetch = original;
  }
});

test("network failure never falls back to a fabricated production order", async () => {
  const original = globalThis.fetch;
  globalThis.fetch = async () => {
    throw new Error("network failed");
  };
  try {
    await assert.rejects(
      () =>
        api({ apiBase: "/wp-json/admincafe/v1", demo: false }).request(
          "/orders/table",
          "POST",
          {},
        ),
      /network failed/,
    );
  } finally {
    globalThis.fetch = original;
  }
});
