<script setup>
import { computed, inject, ref, watch, onUnmounted, shallowRef } from "vue";
import { loadMenuFont } from "../menu-font.js";
import { appearanceStyle, themeId } from "../appearance.js";
import AppearancePreviewCanvas from "./AppearancePreviewCanvas.vue";
const { settings, client, money } = inject("admincafePanel");
const device = ref("mobile"),
  surface = ref("menu"),
  direction = ref("rtl");
const safeCss = ref(""),
  error = ref(""),
  validating = ref(false);
const style = computed(() => appearanceStyle(settings.value));
const frameTarget = shallowRef(null);
function prepareFrame(event) {
  const frameDocument = event.target.contentDocument;
  if (!frameDocument) return;
  for (const sheet of document.querySelectorAll('link[rel="stylesheet"]')) {
    if (sheet.href.includes("admincafe"))
      frameDocument.head.appendChild(sheet.cloneNode(true));
  }
  // Vite development supplies the same compiled menu styles as inline sheets.
  for (const sheet of document.querySelectorAll("style[data-vite-dev-id]")) {
    if (sheet.textContent.includes(".admincafe-root"))
      frameDocument.head.appendChild(sheet.cloneNode(true));
  }
  frameTarget.value = frameDocument.getElementById("ac-preview-root");
}
let timer,
  revision = 0;
watch(
  () => [
    settings.value.menu_theme,
    settings.value.custom_css_enabled,
    settings.value.custom_css,
  ],
  () => {
    const current = ++revision;
    clearTimeout(timer);
    validating.value = true;
    timer = setTimeout(async () => {
      try {
        const result = await client.request(
          "/manage/appearance/preview",
          "POST",
          {
            menu_theme: themeId(settings.value),
            custom_css_enabled: settings.value.custom_css_enabled !== false,
            custom_css: settings.value.custom_css || {},
          },
        );
        if (current !== revision) return;
        if (typeof result.menu_custom_css !== "string")
          throw new Error("پاسخ پیش‌نمایش معتبر نیست.");
        safeCss.value = result.menu_custom_css;
        error.value = "";
      } catch (e) {
        if (current === revision) error.value = e.message;
      } finally {
        if (current === revision) validating.value = false;
      }
    }, 450);
  },
  { immediate: true, deep: true },
);
watch(
  () => [
    settings.value.custom_font_url,
    settings.value.font_family,
    frameTarget.value,
  ],
  () => {
    if (frameTarget.value)
      loadMenuFont(settings.value, frameTarget.value.ownerDocument).catch(
        () => {},
      );
  },
);
onUnmounted(() => {
  clearTimeout(timer);
  revision++;
});
</script>
<template>
  <aside class="ac-surface ac-appearance-workbench">
    <h2>پیش‌نمایش ظاهر منو</h2>
    <p class="ac-hint">
      نمونه مستقل؛ سفارش واقعی ثبت نمی‌کند. برای اعمال در منوی مشتری، تنظیمات را
      ذخیره کنید.
    </p>
    <div class="ac-preview-controls">
      <label
        >اندازه<select v-model="device">
          <option value="mobile">موبایل</option>
          <option value="desktop">دسکتاپ</option>
        </select></label
      >
      <label
        >نمایش<select v-model="surface">
          <option value="menu">کارت‌ها و دسته‌ها</option>
          <option value="detail">جزئیات غذا</option>
          <option value="language">انتخاب زبان</option>
          <option value="cart">سبد سفارش</option>
        </select></label
      >
      <label
        >جهت<select v-model="direction">
          <option value="rtl">راست به چپ</option>
          <option value="ltr">چپ به راست</option>
        </select></label
      >
    </div>
    <p v-if="validating" role="status">در حال اعتبارسنجی CSS…</p>
    <p v-if="error" class="ac-alert" role="alert">
      {{ error }} آخرین CSS معتبر در پیش‌نمایش حفظ شده است.
    </p>
    <div class="ac-preview-viewport" :class="'ac-preview-' + device">
      <iframe
        class="ac-preview-frame"
        :style="{ width: device === 'mobile' ? '360px' : '960px' }"
        title="پیش‌نمایش مستقل منوی مشتری"
        sandbox="allow-same-origin"
        srcdoc="<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><style>body{margin:0}</style></head><body><div class='admincafe-root' id='ac-preview-root'></div></body></html>"
        @load="prepareFrame"
      ></iframe>
      <Teleport v-if="frameTarget" :to="frameTarget"
        ><AppearancePreviewCanvas
          :settings="settings"
          v-model:surface="surface"
          :device="device"
          :direction="direction"
          :style="style"
          :safe-css="safeCss"
          :money="money"
      /></Teleport>
    </div>
  </aside>
</template>
