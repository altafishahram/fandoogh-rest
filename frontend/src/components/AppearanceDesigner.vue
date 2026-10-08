<script setup>
import { computed, inject } from "vue";
import { cssBlocks, menuThemes, emptyCustomCss } from "../appearance.js";
const panel = inject("admincafePanel");
const settings = panel.settings;
const css = computed(() => settings.value.custom_css || {});
function choose(theme) {
  Object.assign(settings.value, {
    menu_theme: theme.id,
    accent: theme.accent,
    background: theme.background,
    category_background: theme.category_background,
  });
}
function update(key, value) {
  settings.value.custom_css = { ...css.value, [key]: value };
}
function reset() {
  settings.value.custom_css = emptyCustomCss();
}
const bytes = (value) => new TextEncoder().encode(value || "").length;
</script>
<template>
  <div class="ac-appearance-designer">
    <p class="ac-hint">
      قالب آماده، رنگ‌ها و سبک منو را تغییر می‌دهد. محتوا، چیدمان، فونت و CSS
      شما حفظ می‌شوند؛ رنگ‌ها را می‌توانید در ادامه تغییر دهید.
    </p>
    <div class="ac-theme-options" role="group" aria-label="قالب آماده منو">
      <button
        v-for="theme in menuThemes"
        :key="theme.id"
        type="button"
        :aria-pressed="(settings.menu_theme || 'cafe') === theme.id"
        @click="choose(theme)"
        :style="{ '--ac-swatch': theme.accent }"
      >
        <span class="ac-theme-swatch" aria-hidden="true"></span
        ><strong>{{ theme.name }}</strong
        ><small>{{ theme.description }}</small>
      </button>
    </div>
    <h3>CSS سفارشی منو</h3>
    <label
      ><input
        type="checkbox"
        :checked="settings.custom_css_enabled !== false"
        @change="settings.custom_css_enabled = $event.target.checked"
      />فعال‌سازی CSS سفارشی</label
    >
    <p class="ac-hint">
      فقط ظاهر منوی مشتری تغییر می‌کند. کادر کل منو، قواعد کامل CSS می‌پذیرد؛
      کادرهای عناصر، اعلان‌های CSS بدون انتخابگر می‌پذیرند. نشانی خارجی و قواعد
      ناامن مجاز نیستند. برای دیدن نتیجه، پیش‌نمایش معتبر سرور لازم است.
    </p>
    <details v-for="block in cssBlocks" :key="block.key" class="ac-css-editor">
      <summary>
        {{ block.label }} <small>{{ bytes(css[block.key]) }} بایت</small>
      </summary>
      <label :for="'ac-css-' + block.key"
        >{{ block.full ? "قواعد کامل CSS" : "اعلان‌های CSS" }} —
        <code dir="ltr">{{ block.selector }}</code></label
      >
      <textarea
        :id="'ac-css-' + block.key"
        dir="ltr"
        spellcheck="false"
        rows="5"
        :value="css[block.key] || ''"
        :placeholder="
          block.full
            ? '.ac-product h3 { color: #467454; }'
            : 'border-radius: 24px;\nbox-shadow: 0 4px 16px #0002;'
        "
        @input="update(block.key, $event.target.value)"
      ></textarea>
      <button type="button" class="ac-outline" @click="update(block.key, '')">
        پاک کردن این کادر
      </button>
    </details>
    <button type="button" class="ac-outline" @click="reset">
      پاک کردن همه CSSها
    </button>
  </div>
</template>
