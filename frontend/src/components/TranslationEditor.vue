<script setup>
import { ref, computed } from "vue";
const props = defineProps({
  entity: { type: Object, required: true },
  kind: { type: String, default: "products" },
});
const code = ref("fa");
const languages = [
  ["fa", "فارسی"],
  ["en", "English"],
  ["zh", "简体中文"],
  ["tr", "Türkçe"],
];
const fields = computed(() =>
  props.kind === "products"
    ? ["name", "description", "short_description"]
    : ["name"],
);
const labels = {
  name: "نام",
  description: "توضیحات",
  short_description: "توضیح کوتاه",
};
function value(field) {
  return code.value === "fa"
    ? props.entity[field] || ""
    : props.entity.translations?.[code.value]?.[field] || "";
}
function update(field, text) {
  if (code.value === "fa") {
    props.entity[field] = text;
    return;
  }
  props.entity.translations ||= {};
  props.entity.translations[code.value] ||= {};
  props.entity.translations[code.value][field] = text;
}
</script>
<template>
  <section class="ac-translation-editor">
    <div class="ac-language-tabs" role="tablist" aria-label="زبان محتوای محصول">
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
    <p v-if="code !== 'fa'" class="ac-hint">
      فارسی متن مبدأ است. اصلاح دستی ترجمه حفظ می‌شود؛ ترجمه خالی، متن فارسی را
      نمایش می‌دهد.
    </p>
    <label v-for="field in fields" :key="field"
      >{{ labels[field]
      }}<textarea
        v-if="field !== 'name'"
        :value="value(field)"
        :dir="code === 'fa' ? 'rtl' : 'ltr'"
        :lang="code"
        @input="update(field, $event.target.value)"
        :maxlength="field === 'description' ? 20000 : 5000"
      ></textarea
      ><input
        v-else
        :value="value(field)"
        :required="code === 'fa' && kind !== 'variation'"
        :disabled="code === 'fa' && kind === 'variation'"
        :dir="code === 'fa' ? 'rtl' : 'ltr'"
        :lang="code"
        maxlength="250"
        @input="update(field, $event.target.value)"
    /></label>
  </section>
</template>
