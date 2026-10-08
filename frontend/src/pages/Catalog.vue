<script>
import { inject } from "vue";
export default {
  setup() {
    return inject("admincafePanel");
  },
};
</script>
<template>
  <template v-if="['products', 'categories', 'tables', 'staff'].includes(tab)">
    <div class="ac-toolbar">
      <label class="ac-search">
        <span>⌕</span>
        <input
          v-model="search"
          placeholder="جستجو…"
          aria-label="جستجوی موارد"
        />
      </label>
      <select v-if="tab === 'products'" v-model="filterCategory">
        <option :value="0">{{ t("همه دسته‌ها") }}</option>
        <option v-for="c in categories" :value="c.id">{{ c.name }}</option>
      </select>
      <button class="ac-primary" @click="open()">
        +
        {{
          tab === "products"
            ? "محصول"
            : tab === "categories"
              ? "دسته"
              : tab === "tables"
                ? "میز"
                : "همکار"
        }}
        جدید
      </button>
    </div>
    <p v-if="tab === 'products' && filterCategory" class="ac-hint">
      {{
        t(
          "برای تغییر ترتیب این دسته، کارت‌ها را بکشید. روی موبایل از دکمه‌های\n      جابه‌جایی استفاده کنید.",
        )
      }}
    </p>
    <div v-if="tab === 'tables'" class="ac-actions">
      <button
        @click="
          qr(
            {
              id: 'menu',
              url: bootstrap.menu_url || 'https://example.com/menu',
            },
            'svg',
          )
        "
      >
        {{ t("QR منوی عمومی / SVG") }}</button
      ><button
        @click="
          qr(
            {
              id: 'menu',
              url: bootstrap.menu_url || 'https://example.com/menu',
            },
            'png',
          )
        "
      >
        {{ t("QR منوی عمومی / PNG") }}
      </button>
    </div>
    <div class="ac-management-grid">
      <article
        v-for="(x, index) in filtered"
        :key="x.id"
        class="ac-surface ac-management-card"
        :draggable="tab === 'products' && !!filterCategory"
        @dragstart="drag = x.id"
        @dragover.prevent
        @drop.prevent="drop(x)"
      >
        <img
          v-if="x.image"
          :src="x.image"
          :alt="x.name || x.label"
          class="ac-thumb"
        />
        <span v-else class="ac-item-icon">{{
          x.icon || (tab === "products" ? "☕" : tab === "tables" ? "▤" : "◉")
        }}</span>
        <div>
          <h3>{{ x.name || x.label || x.display_name || x.username }}</h3>
          <small>{{
            tab === "products"
              ? x.sku
              : tab === "tables"
                ? {
                    inherit: "حالت پیش‌فرض",
                    menu: "فقط منو",
                    order: "منو و سفارش",
                  }[x.mode]
                : x.email || ""
          }}</small>
        </div>
        <label
          v-if="tab === 'products' && x.type !== 'variable'"
          class="ac-price-edit"
          >{{ t("قیمت")
          }}<input
            type="number"
            min="0"
            step="any"
            v-model="x.price"
            @change="quickPrice(x)"
            :aria-label="'قیمت ' + x.name"
          />
        </label>
        <span v-if="tab === 'products'" class="ac-badge">{{
          x.available ? "موجود" : "ناموجود"
        }}</span>
        <div class="ac-actions">
          <button @click="open(x)">{{ t("ویرایش") }}</button>
          <button class="ac-danger" @click="remove(x)">{{ t("حذف") }}</button>
          <button v-if="tab === 'tables'" @click="qr(x, 'svg')">
            QR / SVG
          </button>
          <button v-if="tab === 'tables'" @click="qr(x, 'png')">PNG</button>
          <button
            v-if="tab === 'products' && filterCategory && index > 0"
            @click="
              drag = x.id;
              drop(filtered[index - 1]);
            "
            :aria-label="'جابجایی ' + x.name + ' به بالا'"
          >
            ↑
          </button>
          <button
            v-if="
              tab === 'products' &&
              filterCategory &&
              index < filtered.length - 1
            "
            @click="
              drag = x.id;
              drop(filtered[index + 1]);
            "
            :aria-label="'جابجایی ' + x.name + ' به پایین'"
          >
            ↓
          </button>
        </div>
        <a
          v-if="tab === 'tables'"
          :href="x.url"
          target="_blank"
          rel="noopener"
          class="ac-hint"
          >{{ t("باز کردن لینک میز ↗") }}</a
        >
      </article>
    </div>
    <p v-if="!filtered.length" class="ac-empty">
      {{ t("هنوز موردی ثبت نشده است. با دکمه افزودن، اولین مورد را بسازید.") }}
    </p>
  </template>
</template>
