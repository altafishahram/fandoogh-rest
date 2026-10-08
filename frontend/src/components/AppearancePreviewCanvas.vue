<script setup>
import { computed } from "vue";
import { themeId } from "../appearance.js";
import { languages } from "../customer-i18n.js";
import CompiledMenuCss from "./CompiledMenuCss.js";
import CountryFlag from "./CountryFlag.vue";
const props = defineProps([
  "settings",
  "surface",
  "device",
  "direction",
  "style",
  "safeCss",
  "money",
]);
const emit = defineEmits(["update:surface"]);
const previewLanguages = computed(() =>
  languages.filter((language) =>
    (
      props.settings.enabled_languages || languages.map((item) => item.code)
    ).includes(language.code),
  ),
);
const countries = {
  fa: "ایران",
  en: "United States",
  zh: "中国",
  tr: "Türkiye",
};
</script>
<template>
  <div
    class="ac-menu ac-appearance-preview"
    :data-ac-theme="themeId(settings)"
    :data-preview-device="device"
    :dir="direction"
    :style="style"
  >
    <CompiledMenuCss
      :css="settings.custom_css_enabled === false ? '' : safeCss"
    />
    <header class="ac-header">
      <div class="ac-brand">
        <span class="ac-logo">☕</span
        ><strong>{{ settings.restaurant_name || "کافه" }}</strong>
      </div>
    </header>
    <template v-if="surface === 'menu'">
      <h2>{{ settings.tagline || "یک مکث کوچک، یک حال خوب" }}</h2>
      <label class="ac-search"
        ><span>⌕</span
        ><input placeholder="جستجو در منو…" aria-label="جستجوی نمونه"
      /></label>
      <nav class="ac-categories" aria-label="دسته‌های نمونه">
        <button class="active">همه</button><button>☕ قهوه</button
        ><button>کیک و دسر</button>
      </nav>
      <div
        class="ac-products"
        :class="{ 'ac-list': settings.layout === 'list' }"
      >
        <button
          v-for="(name, index) in ['لاته وانیل', 'چیزکیک']"
          :key="name"
          class="ac-product"
          @click="emit('update:surface', 'detail')"
        >
          <div
            class="ac-product-art"
            :class="index ? 'ac-art-cake' : 'ac-art-latte'"
          >
            <span class="ac-art-shape">{{ index ? "◒" : "☕" }}</span>
          </div>
          <div class="ac-product-copy">
            <h3>{{ name }}</h3>
            <p>
              {{
                index ? "بافت نرم با رویه کاراملی" : "اسپرسو، شیر و وانیل طبیعی"
              }}
            </p>
            <div class="ac-product-bottom">
              <strong>{{
                money(145000, settings.currency_label || "تومان")
              }}</strong
              ><span class="ac-plus">+</span>
            </div>
          </div>
        </button>
      </div>
      <button class="ac-primary" @click="emit('update:surface', 'cart')">
        افزودن به انتخاب‌ها
      </button>
      <button class="ac-cart-float" @click="emit('update:surface', 'cart')">
        سبد شما · ۲ انتخاب
      </button>
    </template>
    <section
      v-else-if="surface === 'language'"
      class="ac-language-dialog"
      lang="fa-IR"
      dir="rtl"
    >
      <span class="ac-language-globe" aria-hidden="true"
        ><svg viewBox="0 0 32 32" focusable="false">
          <circle cx="16" cy="16" r="12" />
          <ellipse cx="16" cy="16" rx="5" ry="12" />
          <path d="M4 16h24M7 9h18M7 23h18" /></svg
      ></span>
      <span class="ac-eyebrow">خوش آمدید · Welcome</span>
      <h2>زبان منوی خود را انتخاب کنید</h2>
      <p class="ac-language-secondary">Choose your language</p>
      <div class="ac-language-cards">
        <button
          v-for="language in previewLanguages"
          :key="language.code"
          type="button"
          class="ac-language-card"
          :class="{
            suggested: language.code === (settings.default_language || 'fa'),
          }"
          :aria-label="language.name"
          :lang="language.html_locale"
          @click="emit('update:surface', 'menu')"
        >
          <CountryFlag
            :code="language.code"
            :label="countries[language.code]"
          />
          <strong :lang="language.html_locale" :dir="language.direction">{{
            language.name
          }}</strong>
          <small :lang="language.code">{{ countries[language.code] }}</small>
          <span class="ac-language-arrow" aria-hidden="true">↗</span>
        </button>
      </div>
      <p class="ac-language-entry-hint">
        برای ورود، یک زبان را انتخاب کنید.<br /><span lang="en" dir="ltr"
          >Select a language to explore the menu.</span
        >
      </p>
    </section>
    <section
      v-else
      class="ac-dialog"
      :class="surface === 'detail' ? 'ac-food-dialog' : 'ac-cart-dialog'"
    >
      <button
        class="ac-close"
        @click="emit('update:surface', 'menu')"
        aria-label="بستن پیش‌نمایش"
      >
        ×
      </button>
      <h2>{{ surface === "detail" ? "لاته وانیل" : "انتخاب‌های شما" }}</h2>
      <p>اسپرسو، شیر و وانیل طبیعی</p>
      <strong>{{ money(145000, settings.currency_label || "تومان") }}</strong>
      <template v-if="surface === 'cart'"
        ><div class="ac-cart-row">
          <strong>لاته وانیل</strong><span>۱</span
          ><button aria-label="کاهش تعداد نمونه">−</button
          ><button aria-label="افزایش تعداد نمونه">+</button>
        </div>
        <label>نام<input placeholder="نام شما" /></label
        ><label>یادداشت<textarea placeholder="توضیحات سفارش"></textarea></label
      ></template>
      <label v-else
        >انتخاب نوع<select>
          <option>کوچک</option>
          <option>بزرگ</option>
        </select></label
      ><button class="ac-primary" @click="emit('update:surface', 'menu')">
        {{ surface === "cart" ? "ثبت سفارش نمونه" : "افزودن به انتخاب‌ها" }}
      </button>
    </section>
  </div>
</template>
