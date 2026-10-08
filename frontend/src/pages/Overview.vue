<script>
import { inject } from "vue";
export default {
  setup() {
    return inject("admincafePanel");
  },
};
</script>
<template>
  <template v-if="tab === 'overview' || tab === 'reports'">
    <div class="ac-stats">
      <article>
        <small>{{ t("درآمد پرداخت‌شده") }}</small>
        <h2>{{ money(reports.revenue, reports.currency_symbol) }}</h2>
        <span>{{ t("پس از کسر بازپرداخت‌ها") }}</span>
      </article>
      <article>
        <small>{{ t("تعداد سفارش") }}</small>
        <h2>{{ reports.order_count || 0 }}</h2>
        <span>{{ t("سفارش‌های ادمین کافه") }}</span>
      </article>
      <article>
        <small>{{ t("منتظر رسیدگی") }}</small>
        <h2>{{ reports.pending_count || 0 }}</h2>
        <span>{{ t("هر سفارش، یک مهمان") }}</span>
      </article>
    </div>
    <div class="ac-dashboard-grid">
      <article class="ac-surface">
        <div class="ac-section-title">
          <h2>{{ t("فروش روزانه") }}</h2>
          <select v-model="days" @change="load">
            <option :value="7">{{ t("۷ روز اخیر") }}</option>
            <option :value="30">{{ t("۳۰ روز اخیر") }}</option>
            <option :value="90">{{ t("۹۰ روز اخیر") }}</option>
          </select>
        </div>
        <div class="ac-chart">
          <div
            v-for="d in reports.daily"
            :title="money(d.total || d.revenue, reports.currency_symbol)"
          >
            <span
              :style="{
                height:
                  Math.max(
                    8,
                    (Number(d.total || d.revenue) /
                      Math.max(
                        ...reports.daily.map((x) =>
                          Number(x.total || x.revenue),
                        ),
                        1,
                      )) *
                      140,
                  ) + 'px',
              }"
            >
            </span>
            <small>{{ d.date }}</small>
          </div>
        </div>
      </article>
      <article class="ac-surface">
        <h2>{{ t("محبوب‌ترین انتخاب‌ها") }}</h2>
        <div v-for="(p, i) in reports.top_products" class="ac-ranked">
          <span>{{ i + 1 }}</span>
          <strong>{{ p.name }}</strong>
          <small>{{ p.quantity || p.count }} سفارش</small>
        </div>
        <p v-if="!reports.top_products?.length" class="ac-empty">
          {{ t("هنوز داده‌ای ثبت نشده است.") }}
        </p>
      </article>
    </div>
    <section v-if="tab === 'overview'" class="ac-surface">
      <div class="ac-section-title">
        <h2>{{ t("سفارش‌های تازه") }}</h2>
        <button @click="go('orders')">{{ t("مشاهده همه ←") }}</button>
      </div>
      <div v-for="o in data.slice(0, 5)" class="ac-order-mini">
        <strong>#{{ o.number }}</strong>
        <span>{{ o.table || o.table_label || o.channel }}</span>
        <span class="ac-badge">{{ stages[o.stage] }}</span>
        <b>{{ money(o.total, o.currency_symbol) }}</b>
      </div>
    </section>
  </template>
</template>
