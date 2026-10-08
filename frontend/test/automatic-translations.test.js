import { test } from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import * as Vue from "vue";
import { compile } from "@vue/compiler-dom";
import { parse } from "@vue/compiler-sfc";
import { renderToString } from "@vue/server-renderer";
import { useAutomaticTranslations } from "../src/automatic-translations.js";

const redacted = {
  enabled: true,
  configured: true,
  provider: "google",
  credential_source: "stored",
  daily_character_limit: 100000,
};
const progress = {
  counts: { queued: 2, running: 1, completed: 9, failed: 3, skipped: 4 },
  problems: [{ id: 7, name: "لاته", message: "سهمیه سرویس تمام شده است" }],
  usage: { characters: 1000, limit: 100000 },
};
test("translation configuration isolates and clears credentials, preserves key on blank edit, and removes only explicitly", async () => {
  const writes = [];
  const controller = useAutomaticTranslations({
    request: async (path, method, body) => {
      if (method === "POST") {
        writes.push({ path, body });
        return { ...redacted, api_key: "must-never-be-retained" };
      }
      return path.endsWith("status")
        ? progress
        : { ...redacted, api_key: "must-never-be-retained" };
    },
  });
  await controller.refresh(true);
  assert.equal(
    JSON.stringify(controller.settings.value).includes("api_key"),
    false,
  );
  controller.apiKey.value = "new-secret";
  await controller.save();
  assert.equal(writes[0].body.api_key, "new-secret");
  assert.equal(controller.apiKey.value, "");
  assert.equal(
    JSON.stringify(controller.settings.value).includes("must-never"),
    false,
  );
  await controller.save();
  assert.equal(Object.hasOwn(writes[1].body, "api_key"), false);
  controller.removeKey.value = true;
  controller.apiKey.value = "ignored-replacement";
  await controller.save();
  assert.equal(writes[2].body.remove_key, true);
  assert.equal(writes[2].body.enabled, false);
  assert.equal(Object.hasOwn(writes[2].body, "api_key"), false);
  assert.equal(controller.apiKey.value, "");
});
test("failed translation settings save clears and redacts submitted key", async () => {
  const controller = useAutomaticTranslations({
    request: async () => {
      throw new Error("rejected sensitive-key");
    },
  });
  controller.apiKey.value = "sensitive-key";
  await controller.save();
  assert.equal(controller.apiKey.value, "");
  assert.equal(controller.error.value.includes("sensitive-key"), false);
  assert.match(controller.error.value, /rejected/);
  assert.equal(controller.busy.value, false);
});
test("status refresh performs no translation writes; run and retry only enqueue explicit work", async () => {
  const calls = [];
  const controller = useAutomaticTranslations({
    request: async (path, method = "GET", body) => {
      calls.push({ path, method, body });
      return path.endsWith("settings") ? redacted : progress;
    },
  });
  await controller.refresh(true);
  assert.ok(calls.every((c) => c.method === "GET"));
  await controller.action("run");
  await controller.action("retry");
  assert.deepEqual(
    calls.filter((c) => c.method === "POST").map((c) => c.path),
    ["/manage/translation/run", "/manage/translation/retry"],
  );
  assert.equal(controller.status.value.counts.failed, 3);
});
test("automatic translation UI renders redacted key state, failed items and costs in Persian", async () => {
  const source = await readFile(
    new URL("../src/components/AutomaticTranslations.vue", import.meta.url),
    "utf8",
  );
  const render = new Function(
    "Vue",
    compile(parse(source).descriptor.template.content, { mode: "function" })
      .code,
  )(Vue);
  render._rc = true;
  const controller = useAutomaticTranslations({
    request: async (path) => (path.endsWith("settings") ? redacted : progress),
  });
  await controller.refresh(true);
  const html = await renderToString(
    Vue.createSSRApp({
      setup: () => ({
        ...controller,
        canManage: true,
        counts: Vue.computed(() => controller.status.value.counts),
      }),
      render,
    }),
  );
  assert.match(html, /type="password"/);
  assert.match(html, /کلید ذخیره شده/);
  assert.match(html, /لاته/);
  assert.match(html, /سهمیه سرویس تمام شده/);
  assert.match(html, /سه برابر/);
  assert.match(html, /مصرف امروز/);
  assert.doesNotMatch(html, /must-never-be-retained/);
  const branchHtml = await renderToString(
    Vue.createSSRApp({
      setup: () => ({
        ...controller,
        canManage: false,
        counts: Vue.computed(() => controller.status.value.counts),
      }),
      render,
    }),
  );
  assert.match(branchHtml, /<fieldset disabled/);
  assert.doesNotMatch(branchHtml, /ذخیره تنظیمات ترجمه/);
  assert.match(branchHtml, /ترجمه غذاها، دسته‌ها و اطلاعات موجود/);
  assert.match(branchHtml, /تلاش دوباره برای موارد ناموفق/);
});
