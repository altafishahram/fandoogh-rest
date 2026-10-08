<script setup>
import { inject, onMounted, onUnmounted, computed } from "vue";
import { useAutomaticTranslations } from "../automatic-translations.js";
const { client } = inject("admincafePanel");
const {
  settings,
  status,
  apiKey,
  removeKey,
  busy,
  loading,
  error,
  notice,
  refresh,
  save,
  action,
} = useAutomaticTranslations(client);
const counts = computed(() => status.value?.counts || {});
let timer;
let alive = true;
onMounted(async () => {
  await refresh(true);
  if (alive)
    timer = setInterval(() => {
      if (!busy.value) refresh();
    }, 20000);
});
onUnmounted(() => {
  alive = false;
  clearInterval(timer);
  apiKey.value = "";
});
</script>
<template>
  <section
    class="ac-automatic-translations"
    aria-labelledby="ac-auto-translation-title"
  >
    <div class="ac-translation-heading">
      <div>
        <span class="ac-eyebrow">فارسی ← English · 简体中文 · Türkçe</span>
        <h2 id="ac-auto-translation-title">ترجمه خودکار منو</h2>
      </div>
      <span class="ac-pill">{{
        settings.configured
          ? settings.enabled
            ? "فعال"
            : "غیرفعال"
          : "کلید تنظیم نشده"
      }}</span>
    </div>
    <p class="ac-hint">
      با ذخیره متن فارسی، نام و توضیحات غذاها، گزینه‌ها، دسته‌ها و اطلاعات کافه
      در پس‌زمینه ترجمه می‌شوند. اصلاح دستی ترجمه‌ها حفظ می‌شود.
    </p>
    <p v-if="loading" role="status">در حال دریافت وضعیت ترجمه…</p>
    <template v-else>
      <p v-if="error" class="ac-alert" role="alert">{{ error }}</p>
      <p v-if="notice" class="ac-notice" role="status">{{ notice }}</p>
      <form @submit.prevent="save">
        <label class="ac-check"
          ><input
            type="checkbox"
            v-model="settings.enabled"
            :disabled="busy"
          />ترجمه خودکار پس از ذخیره فارسی</label
        >
        <div class="ac-form-grid">
          <label
            >کلید Google Cloud Translation Basic v2<input
              type="password"
              v-model="apiKey"
              dir="ltr"
              autocomplete="new-password"
              spellcheck="false"
              :disabled="
                busy || removeKey || settings.credential_source === 'constant'
              "
              :placeholder="
                settings.configured
                  ? 'کلید ذخیره شده؛ فقط برای جایگزینی وارد کنید'
                  : 'کلید API را وارد کنید'
              "
          /></label>
          <label
            >سقف روزانه نویسه<input
              type="number"
              v-model.number="settings.daily_character_limit"
              min="1"
              max="10000000"
              step="1"
              required
              :disabled="busy"
            /><small>مجموع نویسه‌های ارسالی برای همه زبان‌ها</small></label
          >
        </div>
        <label
          v-if="
            settings.configured && settings.credential_source !== 'constant'
          "
          class="ac-check"
          ><input type="checkbox" v-model="removeKey" :disabled="busy" />حذف
          کلید ذخیره‌شده</label
        >
        <p v-if="removeKey" class="ac-hint">
          با حذف کلید، ترجمه خودکار غیرفعال می‌شود.
        </p>
        <p v-if="settings.credential_source === 'constant'" class="ac-hint">
          کلید در پیکربندی سرور تعریف شده است؛ تغییر یا حذف آن از همان پیکربندی
          انجام می‌شود.
        </p>
        <p class="ac-hint">
          کلید فقط در سرور نگه‌داری می‌شود و پس از ذخیره از این فرم پاک می‌شود.
        </p>
        <button type="submit" class="ac-btn" :disabled="busy">
          {{ busy ? "در حال انجام…" : "ذخیره تنظیمات ترجمه" }}
        </button>
      </form>
      <details class="ac-translation-guide">
        <summary>راه‌اندازی سرویس و هزینه‌ها</summary>
        <p>
          در حساب Google Cloud، صورتحساب را فعال کنید، Cloud Translation API را
          برای پروژه فعال کنید و یک کلید API با محدودیت مناسب برای سرور بسازید.
          استفاده از سرویس تابع تعرفه و سهمیه حساب شماست.
        </p>
        <p>
          ترجمه هر متن به سه زبان، تقریباً سه برابر طول متن فارسی نویسه مصرف
          می‌کند. نتیجه در سایت ذخیره می‌شود؛ بازدید مشتری یا تغییر زبان منو
          درخواست ترجمه و هزینه جدید ندارد.
        </p>
        <a
          href="https://cloud.google.com/translate/docs/setup"
          target="_blank"
          rel="noopener"
          >راهنمای رسمی راه‌اندازی Google Cloud ↗</a
        >
      </details>
      <div class="ac-translation-progress" aria-live="polite">
        <div
          v-for="(label, key) in {
            queued: 'در صف',
            running: 'در حال ترجمه',
            completed: 'تکمیل‌شده',
            failed: 'ناموفق',
            skipped: 'ردشده یا دستی',
          }"
          :key="key"
        >
          <strong>{{ Number(counts[key] || 0).toLocaleString("fa-IR") }}</strong
          ><small>{{ label }}</small>
        </div>
      </div>
      <p v-if="status?.usage" class="ac-hint">
        مصرف امروز:
        {{ Number(status.usage.characters || 0).toLocaleString("fa-IR") }} از
        {{ Number(status.usage.limit || 0).toLocaleString("fa-IR") }} نویسه
      </p>
      <div class="ac-actions">
        <button
          type="button"
          class="ac-btn"
          :disabled="busy || !settings.enabled || !settings.configured"
          @click="action('run')"
        >
          ترجمه غذاها، دسته‌ها و اطلاعات موجود</button
        ><button
          type="button"
          class="ac-btn ac-btn-light"
          :disabled="
            busy ||
            !settings.enabled ||
            !settings.configured ||
            !Number(counts.failed)
          "
          @click="action('retry')"
        >
          تلاش دوباره برای موارد ناموفق</button
        ><button
          type="button"
          class="ac-btn ac-btn-light"
          :disabled="busy"
          @click="refresh()"
        >
          تازه‌سازی وضعیت
        </button>
      </div>
      <ul v-if="status?.problems?.length" class="ac-translation-problems">
        <li
          v-for="(problem, index) in status.problems"
          :key="problem.id || index"
        >
          <strong>{{ problem.name || problem.title || "مورد ناموفق" }}</strong>
          <p>{{ problem.message || problem.error }}</p>
        </li>
      </ul>
    </template>
  </section>
</template>
