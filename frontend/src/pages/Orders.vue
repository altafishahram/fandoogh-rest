<script>
import { inject } from "vue";
export default {
  setup() {
    return inject("admincafePanel");
  },
};
</script>
<template>
  <template v-if="tab === 'orders'">
    <div class="ac-toolbar">
      <select v-model="stage" @change="load">
        <option value="">{{ t("همه وضعیت‌ها") }}</option>
        <option v-for="(label, key) in stages" :value="key">{{ label }}</option>
      </select>
      <button v-if="canCashier" class="ac-primary" @click="counter">
        {{ t("+ سفارش صندوق") }}
      </button>
    </div>
    <div class="ac-order-grid">
      <article
        v-for="o in filtered"
        class="ac-surface ac-order-card"
        :class="{ 'ac-highlight': orderHighlight === o.id }"
      >
        <div class="ac-section-title">
          <strong>#{{ o.number }}</strong>
          <span class="ac-badge" :class="o.stage">{{ stages[o.stage] }}</span>
        </div>
        <h3>
          {{
            o.table ||
            o.table_label ||
            {
              table: "میز",
              counter: "صندوق",
              pickup: "تحویل حضوری",
              delivery: "ارسال",
            }[o.channel]
          }}
        </h3>
        <p>{{ o.customer_name || o.name || "" }}</p>
        <p v-if="o.channel === 'delivery' && o.phone" class="ac-hint">
          {{ t("تلفن مشتری") }}:
          <a :href="'tel:' + o.phone" dir="ltr">{{ o.phone }}</a>
        </p>
        <p v-if="o.channel === 'delivery' && o.address" class="ac-hint">
          {{ t("نشانی تحویل") }}: {{ o.address }}
        </p>
        <div v-for="i in o.items || o.line_items || []" class="ac-line">
          <span>{{ i.name }}</span>
          <b>× {{ i.quantity }}</b>
        </div>
        <p v-if="o.note" class="ac-hint">{{ o.note }}</p>
        <div class="ac-total">
          <span>{{
            paymentLabels[o.payment_status || o.status] || t("وضعیت پرداخت")
          }}</span>
          <strong>{{ money(o.total, o.currency_symbol) }}</strong>
        </div>
        <div class="ac-actions">
          <button @click="open(o, 'orderDetails')">{{ t("جزئیات") }}</button>
          <button
            v-if="o.stage === 'awaiting_approval'"
            class="ac-primary"
            @click="orderAction(o, { stage: 'accepted' })"
          >
            {{ t("تأیید سفارش") }}
          </button>
          <button
            v-if="o.stage === 'accepted'"
            class="ac-primary"
            @click="orderAction(o, { stage: 'preparing' })"
          >
            {{ t("شروع آماده‌سازی") }}
          </button>
          <button
            v-if="o.stage === 'preparing'"
            class="ac-primary"
            @click="orderAction(o, { stage: 'ready' })"
          >
            {{ t("آماده شد") }}
          </button>
          <button
            v-if="o.stage === 'ready'"
            class="ac-primary"
            @click="orderAction(o, { stage: 'delivered' })"
          >
            {{ t("تحویل شد") }}
          </button>
          <button
            v-if="!['delivered', 'cancelled'].includes(o.stage)"
            @click="orderAction(o, { stage: 'cancelled' })"
          >
            {{ t("لغو") }}
          </button>
          <button
            v-if="
              canCashier &&
              !['completed', 'processing', 'refunded'].includes(
                o.payment_status || o.status,
              )
            "
            @click="orderAction(o, { payment_action: 'settle' })"
          >
            {{ t("ثبت تسویه") }}
          </button>
          <button
            v-if="
              canRefund &&
              ['completed', 'processing'].includes(o.payment_status || o.status)
            "
            @click="
              run(async () => {
                if (window.confirm('بازپرداخت مبلغ سفارش انجام شود؟'))
                  await orderAction(o, { payment_action: 'refund' });
              })
            "
          >
            {{ t("بازپرداخت") }}
          </button>
        </div>
      </article>
    </div>
    <p v-if="!data.length" class="ac-empty">
      {{ t("سفارشی در این وضعیت وجود ندارد.") }}
    </p>
    <div v-if="pages > 1" class="ac-actions">
      <button
        :disabled="page <= 1 || busy"
        @click="
          page--;
          load();
        "
      >
        {{ t("صفحه قبل") }}</button
      ><span>{{ page }} / {{ pages }}</span
      ><button
        :disabled="page >= pages || busy"
        @click="
          page++;
          load();
        "
      >
        {{ t("صفحه بعد") }}
      </button>
    </div></template
  >
</template>
