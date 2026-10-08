<script>
import { inject } from "vue";
export default {
  setup() {
    return inject("admincafePanel");
  },
};
</script>
<template>
  <section v-if="tab === 'branches' && bootstrap.can_manage_branches">
    <div class="ac-toolbar">
      <p>
        سایت و درگاه پرداخت مشترک؛ منو، کارکنان، سفارش‌ها و ظاهر هر شعبه مستقل
        است.
      </p>
      <button class="ac-primary" @click="open(null, 'branches')">
        + شعبه جدید
      </button>
    </div>
    <div class="ac-branches-grid">
      <article v-for="b in data" :key="b.id" class="ac-surface">
        <span class="ac-pill">{{ b.enabled ? "فعال" : "غیرفعال" }}</span>
        <h2>{{ b.name }}</h2>
        <p dir="ltr">{{ b.slug }}</p>
        <a :href="b.menu_url" target="_blank" rel="noopener" dir="ltr">{{
          b.menu_url
        }}</a>
        <div class="ac-actions">
          <button @click="copyBranch(b)">کپی پیوند</button>
          <button @click="qr({ id: 'branch-' + b.id, url: b.menu_url }, 'png')">
            QR منو
          </button>
          <button @click="qr({ id: 'branch-' + b.id, url: b.menu_url }, 'svg')">
            QR برداری
          </button>
          <button @click="open(b, 'branches')">ویرایش</button>
          <button @click="setBranchEnabled(b)">
            {{ b.enabled ? "غیرفعال کردن" : "فعال کردن" }}
          </button>
        </div>
      </article>
    </div>
    <p class="ac-hint">
      غیرفعال کردن شعبه اطلاعات و سابقه سفارش‌ها را حفظ می‌کند.
    </p>
  </section>
</template>
