<script>
import { inject } from "vue";
export default {
  setup() {
    return inject("admincafePanel");
  },
};
</script>
<template>
  <section v-if="tab === 'notifications'" class="ac-surface">
    <div class="ac-section-title">
      <h2>{{ t("همیشه در جریان باشید") }}</h2>
      <button @click="readEvents">{{ t("علامت‌گذاری خوانده‌شده") }}</button>
    </div>
    <p>
      {{
        push.available
          ? "اعلان مرورگر آماده است."
          : push.reason ||
            "اعلان مرورگر در دسترس نیست؛ اعلان‌های داخل پنل فعال‌اند."
      }}
    </p>
    <div class="ac-checks">
      <label
        v-for="(label, key) in {
          table: 'سفارش میز',
          pickup: 'تحویل حضوری',
          delivery: 'ارسال',
          counter: 'صندوق',
        }"
        ><input type="checkbox" v-model="pushChannels" :value="key" />{{
          label
        }}</label
      >
    </div>
    <div class="ac-actions">
      <button class="ac-primary" @click="subscribe">
        {{ t("فعال‌سازی روی این دستگاه") }}
      </button>
      <button
        :disabled="!push.available || busy"
        @click="
          run(async () => {
            await client.request('/manage/push/test', 'POST', {});
            notice = 'اعلان آزمایشی درخواست شد.';
          })
        "
      >
        {{ t("ارسال آزمایشی") }}
      </button>
    </div>
    <div v-for="e in events" class="ac-event" :class="{ unread: !e.read }">
      <button @click="openEvent(e)">
        <h3>{{ e.title }}</h3>
      </button>
      <p>{{ e.body }}</p>
      <small>{{ e.created_at }}</small>
    </div>
    <p v-if="!events.length" class="ac-empty">
      {{ t("اعلان تازه‌ای ندارید.") }}
    </p>
    <h3>{{ t("دستگاه‌های متصل") }}</h3>
    <div v-for="d in devices" class="ac-event">
      <div class="ac-line">
        <span>{{ d.label || "مرورگر" }}</span>
        <button @click="unsubscribe(d)">{{ t("قطع اتصال") }}</button>
      </div>
      <div class="ac-checks">
        <label
          v-for="(label, key) in {
            table: 'سفارش میز',
            pickup: 'تحویل حضوری',
            delivery: 'ارسال',
            counter: 'صندوق',
          }"
          ><input type="checkbox" v-model="d.channels" :value="key" />{{
            t(label)
          }}</label
        >
      </div>
      <p v-if="d.last_error" class="ac-hint">{{ d.last_error }}</p>
      <button class="ac-primary" :disabled="busy" @click="saveDevice(d)">
        {{ t("ذخیره تنظیمات دستگاه") }}
      </button>
    </div>
  </section>
</template>
