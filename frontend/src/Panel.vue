<script>
import { provide } from "vue";
import TranslationEditor from "./components/TranslationEditor.vue";
import { usePanel } from "./usePanel.js";
import Overview from "./pages/Overview.vue";
import Orders from "./pages/Orders.vue";
import Catalog from "./pages/Catalog.vue";
import Import from "./pages/Import.vue";
import Settings from "./pages/Settings.vue";
import Notifications from "./pages/Notifications.vue";
export default {
  props: ["config", "mountRoot"],
  components: {
    TranslationEditor,
    Overview,
    Orders,
    Catalog,
    Import,
    Settings,
    Notifications,
  },
  setup(props) {
    const state = usePanel(props.config, props.mountRoot);
    provide("admincafePanel", state);
    return state;
  },
};
</script>
<template>
  <div class="ac-panel" dir="rtl" @keydown.esc="close">
    <aside class="ac-sidebar">
      <div class="ac-brand">
        <span class="ac-logo">{{ t("آ") }}</span>
        <div>
          <strong>{{ t("رستوران فندوق") }}</strong>
          <small>{{ t("مدیریت با طعم آرامش") }}</small>
        </div>
      </div>
      <nav aria-label="مدیریت کافه" class="ac-select-none">
        <button
          v-for="item in allowed"
          :class="{ active: tab === item[0] }"
          @click="go(item[0])"
        >
          <span>{{ item[2] }}</span
          >{{ t(item[1])
          }}<b v-if="item[0] === 'notifications' && unread">{{ unread }}</b>
        </button>
      </nav>
      <div class="ac-sidebar-bottom">
        <span class="ac-avatar">{{ bootstrap.user.name?.slice(0, 1) }}</span>
        <strong>{{ bootstrap.user.name }}</strong>
        <small>{{ bootstrap.settings.restaurant_name }}</small>
        <a
          :href="config.demo ? '/' : bootstrap.menu_url"
          target="_blank"
          rel="noopener"
          >{{ t("مشاهده منوی کافه ↗") }}</a
        >
        <a v-if="config.logoutUrl" :href="config.logoutUrl">{{
          t("خروج از مدیریت")
        }}</a>
      </div>
    </aside>
    <main class="ac-main">
      <header class="ac-panel-header">
        <div>
          <span class="ac-eyebrow">{{
            bootstrap.settings.restaurant_name || "مدیریت کافه"
          }}</span>
          <h1>{{ title }}</h1>
        </div>
        <span class="ac-pill">{{
          new Date().toLocaleDateString("fa-IR", {
            weekday: "long",
            day: "numeric",
            month: "long",
          })
        }}</span>
      </header>
      <div v-if="config.demo" class="ac-demo">
        {{ t("محیط نمایشی • اطلاعات نمونه است")
        }}<a href="/">{{ t("منوی مشتری ←") }}</a>
      </div>
      <p v-if="error" class="ac-alert" role="alert">{{ error }}</p>
      <p v-if="notice" class="ac-success" role="status">{{ notice }}</p>
      <p v-if="busy" class="ac-loading" role="status">
        {{ t("در حال ارتباط با کافه…") }}
      </p>
      <Overview />
      <Orders />
      <Catalog />
      <Import />
      <Settings />
      <Notifications />
    </main>
    <div v-if="edit" class="ac-overlay" @click.self="close">
      <form
        class="ac-dialog"
        role="dialog"
        aria-modal="true"
        aria-label="ویرایش مورد"
        @submit.prevent="save"
        @keydown.tab="trap"
      >
        <button type="button" class="ac-close" @click="close" aria-label="بستن">
          ×
        </button>
        <span class="ac-eyebrow"
          >{{ edit.id ? "ویرایش" : "افزودن" }} اطلاعات</span
        >
        <h2>
          {{
            {
              products: "محصول",
              categories: "دسته‌بندی",
              tables: "میز",
              staff: "همکار",
              orders: "سفارش صندوق",
              orderDetails: "جزئیات سفارش",
            }[edit.kind]
          }}
        </h2>
        <template v-if="edit.kind === 'products'">
          <TranslationEditor :entity="form" kind="products" />
          <div class="ac-form-grid">
            <label
              >{{ t("قیمت")
              }}<input v-model="form.price" type="number" min="0" step="any" />
            </label>
            <label
              >{{ t("قیمت اصلی")
              }}<input
                v-model="form.regular_price"
                type="number"
                min="0"
                step="any"
              />
            </label>
            <label
              >{{ t("قیمت حراج")
              }}<input
                v-model="form.sale_price"
                type="number"
                min="0"
                step="any"
              />
            </label>
            <label>SKU<input v-model="form.sku" /> </label>
          </div>
          <label
            >{{ t("دسته‌بندی‌ها")
            }}<select multiple v-model="form.category_ids">
              <option v-for="c in categories" :value="c.id">
                {{ c.name }}
              </option>
            </select>
          </label>
          <label>
            <input type="checkbox" v-model="form.available" />{{
              t("موجود")
            }}</label
          >
          <label>
            <input type="checkbox" v-model="form.visible" />{{
              t("نمایش در منو")
            }}</label
          >
          <label
            >{{ t("تصویر")
            }}<input type="file" accept="image/*" @change="media($event)" />
          </label>
          <img
            v-if="form.image"
            :src="form.image"
            alt="تصویر محصول"
            class="ac-thumb"
          />
          <fieldset v-if="form.variations?.length">
            <legend>{{ t("گزینه‌های محصول") }}</legend>
            <div v-for="v in form.variations" :key="v.id">
              <TranslationEditor :entity="v" kind="variation" /><label
                >{{ v.name
                }}<input type="number" min="0" step="any" v-model="v.price" />
                <span>
                  <input type="checkbox" v-model="v.available" />{{
                    t("موجود")
                  }}</span
                >
              </label>
            </div>
          </fieldset>
        </template>
        <template v-if="edit.kind === 'categories'">
          <TranslationEditor :entity="form" kind="categories" />
          <label
            >{{ t("دسته والد")
            }}<select v-model="form.parent">
              <option :value="0">{{ t("بدون والد") }}</option>
              <option
                v-for="c in data.filter((c) => c.id !== edit.id)"
                :value="c.id"
              >
                {{ c.name }}
              </option>
            </select>
          </label>
          <label
            >{{ t("آیکون") }}<input v-model="form.icon" maxlength="20" />
          </label>
          <label
            >{{ t("تصویر")
            }}<input type="file" accept="image/*" @change="media($event)" />
          </label>
        </template>
        <template v-if="edit.kind === 'tables'">
          <label
            >{{ t("نام میز") }}<input v-model="form.label" required />
          </label>
          <label
            >{{ t("حالت")
            }}<select v-model="form.mode">
              <option value="inherit">{{ t("ارث‌بری از تنظیمات") }}</option>
              <option value="menu">{{ t("فقط نمایش منو") }}</option>
              <option value="order">{{ t("منو و سفارش") }}</option>
            </select>
          </label>
          <label>
            <input type="checkbox" v-model="form.enabled" />{{
              t("میز فعال")
            }}</label
          >
        </template>
        <template v-if="edit.kind === 'staff'">
          <label
            >{{ t("نام نمایشی") }}<input v-model="form.name" required />
          </label>
          <label
            >{{ t("نام کاربری")
            }}<input
              v-model="form.username"
              :required="!edit.id"
              :disabled="!!edit.id"
            />
          </label>
          <label
            >{{ t("ایمیل")
            }}<input type="email" v-model="form.email" required />
          </label>
          <label
            >{{ t("گذرواژه")
            }}<input
              type="password"
              v-model="form.password"
              :required="!edit.id"
              autocomplete="new-password"
            />
          </label>
          <label
            >{{ t("نقش")
            }}<select v-model="form.role">
              <option
                v-for="role in bootstrap.roles"
                :value="typeof role === 'string' ? role : role.id"
              >
                {{
                  typeof role === "string"
                    ? {
                        admincafe_manager: "مدیر",
                        admincafe_staff: "کارکنان",
                        admincafe_kitchen: "آشپزخانه",
                        admincafe_cashier: "صندوق‌دار",
                      }[role] || role
                    : role.name
                }}
              </option>
            </select>
          </label>
        </template>
        <template v-if="edit.kind === 'orders'">
          <label>{{ t("نام مشتری") }}<input v-model="form.name" /> </label>
          <label
            >{{ t("تلفن") }}<input v-model="form.phone" inputmode="tel" />
          </label>
          <label
            >{{ t("یادداشت") }}<textarea v-model="form.note"></textarea>
          </label>
          <label
            >{{ t("افزودن محصول")
            }}<select
              @change="
                (p) => {
                  const product = products.find(
                    (x) => x.id === Number(p.target.value),
                  );
                  if (product)
                    form.items.push({
                      product_id: product.id,
                      name: product.name,
                      quantity: 1,
                      variation_id: 0,
                    });
                  p.target.value = '';
                }
              "
            >
              <option value="">{{ t("انتخاب محصول") }}</option>
              <option v-for="p in products" :value="p.id">{{ p.name }}</option>
            </select>
          </label>
          <div v-for="(i, index) in form.items" class="ac-line">
            <span>{{ i.name }}</span>
            <select
              v-if="
                products.find((p) => p.id === i.product_id)?.type === 'variable'
              "
              v-model="i.variation_id"
              required
            >
              <option :value="0">{{ t("انتخاب گزینه") }}</option>
              <option
                v-for="v in products.find((p) => p.id === i.product_id)
                  .variations"
                :value="v.id"
                :disabled="!v.available"
              >
                {{ v.name }}
              </option>
            </select>
            <input
              type="number"
              min="1"
              v-model.number="i.quantity"
              aria-label="تعداد"
            />
            <button type="button" @click="form.items.splice(index, 1)">
              ×
            </button>
          </div>
        </template>
        <section v-if="edit.kind === 'orderDetails'">
          <h3>#{{ form.number }} • {{ stages[form.stage] }}</h3>
          <p>{{ form.table || form.channel }}</p>
          <p>{{ form.name }} {{ form.phone }}</p>
          <p v-if="form.channel === 'delivery' && form.address">
            {{ t("نشانی تحویل") }}: {{ form.address }}
          </p>
          <p>{{ form.note }}</p>
          <div v-for="i in form.items" class="ac-line">
            <span>{{ i.name }}</span
            ><b>× {{ i.quantity }}</b>
          </div>
          <div class="ac-total">
            <span>{{
              paymentLabels[form.payment_status] || t("وضعیت پرداخت")
            }}</span
            ><b>{{ money(form.total, form.currency_symbol) }}</b>
          </div>
        </section>
        <button
          v-if="edit.kind !== 'orderDetails'"
          class="ac-primary"
          :disabled="busy"
        >
          {{ busy ? "در حال ذخیره…" : "ذخیره" }}
        </button>
      </form>
    </div>
  </div>
</template>
