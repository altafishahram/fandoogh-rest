<script setup>
import { ref } from "vue";
const props = defineProps({ settings: { type: Object, required: true } });
const code = ref("en");
const languages = [
  ["en", "English"],
  ["zh", "简体中文"],
  ["tr", "Türkçe"],
];
const fields = {
  restaurant_name: "نام کافه",
  tagline: "شعار",
  restaurant_address: "آدرس",
  hours_text: "ساعات کاری",
};
const messages = {
  unavailable: "پیام ناموجود",
  closed: "پیام توقف سفارش",
  order_received: "پیام ثبت سفارش",
};
function value(key, isMessage = false) {
  const data = props.settings.content_translations?.[code.value] || {};
  return isMessage ? data.messages?.[key] || "" : data[key] || "";
}
function update(key, text, isMessage = false) {
  props.settings.content_translations ||= {};
  props.settings.content_translations[code.value] ||= {};
  const data = props.settings.content_translations[code.value];
  if (isMessage) {
    data.messages ||= {};
    data.messages[key] = text;
  } else data[key] = text;
}
</script>
<template>
  <section class="ac-translation-editor">
    <h2>ترجمه اطلاعات و پیام‌های کافه</h2>
    <div class="ac-language-tabs" role="tablist" aria-label="زبان اطلاعات کافه">
      <button
        v-for="lang in languages"
        :key="lang[0]"
        type="button"
        role="tab"
        :aria-selected="code === lang[0]"
        :class="{ active: code === lang[0] }"
        @click="code = lang[0]"
      >
        {{ lang[1] }}
      </button>
    </div>
    <p class="ac-hint">
      فیلد خالی از متن فارسی استفاده می‌کند. جایگزین‌هایی مانند {number} را در
      ترجمه حفظ کنید.
    </p>
    <div class="ac-form-grid">
      <label v-for="(label, key) in fields" :key="key"
        >{{ label
        }}<input
          :value="value(key)"
          dir="ltr"
          :lang="code"
          @input="update(key, $event.target.value)" /></label
      ><label v-for="(label, key) in messages" :key="key"
        >{{ label
        }}<textarea
          :value="value(key, true)"
          dir="ltr"
          :lang="code"
          @input="update(key, $event.target.value, true)"
        ></textarea>
      </label>
    </div>
  </section>
</template>
