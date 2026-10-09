<script setup>
import { ref, onMounted, onUnmounted, nextTick } from "vue";
import CountryFlag from "./CountryFlag.vue";
const props = defineProps({
  languages: { type: Array, required: true },
  suggested: { type: String, default: "fa" },
});
const emit = defineEmits(["choose"]);
const dialog = ref(null),
  cards = ref([]);
let priorFocus;
const countries = {
  fa: "ایران",
  en: "United States",
  zh: "中国",
  tr: "Türkiye",
};
onMounted(() => {
  priorFocus = document.activeElement;
  nextTick(() =>
    cards.value[
      props.languages.findIndex((l) => l.code === props.suggested)
    ]?.focus(),
  );
});
onUnmounted(() => priorFocus?.focus?.());
function keyboard(event, index) {
  let target = index;
  const count = props.languages.length;
  if (event.key === "Escape") {
    event.preventDefault();
    event.stopPropagation();
    return;
  }
  if (event.key === "Enter" || event.key === " ") {
    event.preventDefault();
    emit("choose", props.languages[index].code);
    return;
  }
  if (event.key === "ArrowLeft") target = (index + 1) % count;
  else if (event.key === "ArrowRight") target = (index + count - 1) % count;
  else if (event.key === "ArrowDown") target = (index + 2) % count;
  else if (event.key === "ArrowUp") target = (index + count - 2) % count;
  else if (event.key === "Home") target = 0;
  else if (event.key === "End") target = count - 1;
  else if (event.key === "Tab") {
    if (event.shiftKey && index === 0) {
      event.preventDefault();
      cards.value[count - 1]?.focus();
    } else if (!event.shiftKey && index === count - 1) {
      event.preventDefault();
      cards.value[0]?.focus();
    }
    return;
  } else return;
  event.preventDefault();
  cards.value[target]?.focus();
}
</script>
<template>
  <div class="ac-language-gate" @keydown.esc.prevent.stop>
    <section
      ref="dialog"
      class="ac-language-dialog"
      role="dialog"
      aria-modal="true"
      aria-label="انتخاب زبان / Choose your language"
      lang="fa-IR"
      dir="rtl"
    >
      <span class="ac-language-globe" aria-hidden="true"
        ><svg viewBox="0 0 32 32" focusable="false">
          <circle cx="16" cy="16" r="12" />
          <ellipse cx="16" cy="16" rx="5" ry="12" />
          <path d="M4 16h24M7 9h18M7 23h18" /></svg></span
      ><span class="ac-eyebrow">خوش آمدید · Welcome</span>
      <h2>زبان منوی خود را انتخاب کنید</h2>
      <p class="ac-language-secondary" lang="en" dir="ltr">
        Choose your language
      </p>
      <div class="ac-language-cards">
        <button
          v-for="(language, index) in languages"
          :key="language.code"
          :ref="(el) => (cards[index] = el)"
          type="button"
          class="ac-language-card"
          :class="{ suggested: language.code === suggested }"
          :aria-label="language.name"
          :lang="language.html_locale || language.code"
          :aria-current="language.code === suggested ? 'true' : undefined"
          @click="emit('choose', language.code)"
          @keydown="keyboard($event, index)"
        >
          <CountryFlag
            :code="language.code"
            :label="countries[language.code]"
          /><strong
            :lang="language.html_locale || language.code"
            :dir="language.direction"
            >{{ language.name }}</strong
          ><small :lang="language.code">{{ countries[language.code] }}</small>
        </button>
      </div>
      <p class="ac-language-entry-hint">
        برای ورود، یک زبان را انتخاب کنید.<br /><span lang="en" dir="ltr"
          >Select a language to explore the menu.</span
        >
      </p>
    </section>
  </div>
</template>
