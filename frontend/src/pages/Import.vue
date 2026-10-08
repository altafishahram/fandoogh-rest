<script>
import { inject } from "vue";
export default {
  setup() {
    return inject("admincafePanel");
  },
};
</script>
<template>
  <section v-if="tab === 'import'" class="ac-surface">
    <span class="ac-eyebrow">{{ t("منوی شما، در چند دقیقه") }}</span>
    <h2>{{ t("واردسازی محصولات") }}</h2>
    <p>
      {{
        t(
          "فایل CSV یا XLSX را انتخاب کنید، ستون‌ها را تطبیق دهید و سپس اعمال کنید.",
        )
      }}
    </p>
    <label class="ac-upload"
      >{{ t("↥ انتخاب فایل")
      }}<input type="file" accept=".csv,.xlsx" @change="importFile" />
    </label>
    <template v-if="preview">
      <h3>{{ preview.total }} ردیف آماده بررسی</h3>
      <div class="ac-form-grid">
        <label
          v-for="key in [
            'name',
            'description',
            'price',
            'sku',
            'category',
            'status',
          ]"
          >{{
            {
              name: "نام محصول",
              description: "توضیح",
              price: "قیمت",
              sku: "شناسه SKU",
              category: "دسته",
              status: "وضعیت",
            }[key]
          }}<select v-model="mapping[key]">
            <option value="">{{ t("انتخاب ستون") }}</option>
            <option v-for="(column, i) in preview.columns" :value="column">
              {{ column }}
            </option>
          </select>
        </label>
      </div>
      <div class="ac-table-scroll">
        <table>
          <thead>
            <tr>
              <th v-for="c in preview.columns">{{ c }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in preview.rows.slice(0, 10)">
              <td v-for="cell in row">{{ cell }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <label
        >{{ t("روش واردسازی")
        }}<select v-model="importMode">
          <option value="upsert">{{ t("ایجاد و به‌روزرسانی با SKU") }}</option>
          <option value="create">{{ t("فقط ایجاد محصولات جدید") }}</option>
        </select>
      </label>
      <button
        class="ac-primary"
        :disabled="busy || !mapping.name"
        @click="applyImport"
      >
        {{ t("اعمال واردسازی") }}
      </button>
    </template>
  </section>
</template>
