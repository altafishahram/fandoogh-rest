<script>
import AppearanceDesigner from "../components/AppearanceDesigner.vue";
import AppearancePreview from "../components/AppearancePreview.vue";
import RestaurantTranslations from "../components/RestaurantTranslations.vue";
import AutomaticTranslations from "../components/AutomaticTranslations.vue";
import { inject } from "vue";
export default {
  components: {
    RestaurantTranslations,
    AutomaticTranslations,
    AppearanceDesigner,
    AppearancePreview,
  },
  setup() {
    return inject("admincafePanel");
  },
};
</script>
<template>
  <template v-if="tab === 'settings'">
    <p class="ac-hint">
      تنظیمات شعبه {{ branch.name }}؛ مسیرهای سایت و واحد پول بین شعبه‌ها مشترک
      هستند.
    </p>
    <div class="ac-settings-grid">
      <section class="ac-surface">
        <h2>زبان‌های منوی مشتری</h2>
        <div class="ac-checks">
          <label
            v-for="l in [
              { code: 'fa', name: 'فارسی' },
              { code: 'en', name: 'English' },
              { code: 'zh', name: '简体中文' },
              { code: 'tr', name: 'Türkçe' },
            ]"
            :key="l.code"
            ><input
              type="checkbox"
              v-model="settings.enabled_languages"
              :value="l.code"
            />{{ l.name }}</label
          >
        </div>
        <label
          >زبان پیش‌فرض<select v-model="settings.default_language">
            <option
              v-for="l in [
                { code: 'fa', name: 'فارسی' },
                { code: 'en', name: 'English' },
                { code: 'zh', name: '简体中文' },
                { code: 'tr', name: 'Türkçe' },
              ].filter((l) =>
                (settings.enabled_languages || []).includes(l.code),
              )"
              :value="l.code"
            >
              {{ l.name }}
            </option>
          </select></label
        >
        <AutomaticTranslations :can-manage="!!bootstrap.can_manage_branches" />
        <h2>{{ t("هویت و مسیرهای کافه") }}</h2>
        <div class="ac-form-grid">
          <label
            >{{ t("نام کافه") }}<input v-model="settings.restaurant_name" />
          </label>
          <label>{{ t("شعار") }}<input v-model="settings.tagline" /> </label>
          <label v-if="bootstrap.can_manage_branches"
            >{{ t("اسلاگ منو") }}<input v-model="settings.menu_slug" />
          </label>
          <label v-if="bootstrap.can_manage_branches"
            >{{ t("اسلاگ مدیریت") }}<input v-model="settings.panel_slug" />
          </label>
          <label
            >{{ t("صفحه مقصد منو")
            }}<select v-model="settings.menu_page_id">
              <option :value="0">{{ t("صفحه مستقل رستوران فندوق") }}</option>
              <option v-for="p in bootstrap.pages" :value="p.id">
                {{ p.title }}
              </option>
            </select>
          </label>
          <label
            >{{ t("تلفن") }}<input v-model="settings.restaurant_phone" />
          </label>
          <label
            >{{ t("آدرس") }}<input v-model="settings.restaurant_address" />
          </label>
          <label
            >{{ t("ساعات کاری") }}<input v-model="settings.hours_text" />
          </label>
          <label
            >{{ t("لوگو")
            }}<input
              type="file"
              accept="image/*"
              @change="media($event, 'settings', 'logo_id')"
            />
          </label>
          <label
            >{{ t("کاور")
            }}<input
              type="file"
              accept="image/*"
              @change="media($event, 'settings', 'cover_id')"
            />
          </label>
        </div>
        <label
          >{{ t("دسته‌های نمایش داده شده در منو (خالی یعنی همه)")
          }}<select multiple v-model="settings.menu_category_ids">
            <option v-for="c in categories" :value="c.id">{{ c.name }}</option>
          </select></label
        >
        <h2>{{ t("سفارش‌گیری") }}</h2>
        <div class="ac-checks">
          <label
            v-for="(label, key) in {
              dine_in_enabled: 'سفارش میز',
              pickup_enabled: 'تحویل حضوری',
              delivery_enabled: 'ارسال',
              ordering_paused: 'توقف سفارش‌گیری',
              notification_sound: 'صدای اعلان',
            }"
          >
            <input type="checkbox" v-model="settings[key]" />{{ label }}</label
          >
        </div>
        <div class="ac-form-grid">
          <label
            >{{ t("آماده‌سازی (دقیقه)")
            }}<input
              type="number"
              min="0"
              v-model.number="settings.preparation_minutes"
            />
          </label>
          <label
            >{{ t("حالت پیش‌فرض میز")
            }}<select v-model="settings.table_default_mode">
              <option value="menu">{{ t("فقط منو") }}</option>
              <option value="order">{{ t("منو و سفارش") }}</option>
            </select>
          </label>
          <label
            >{{ t("رنگ QR") }}<input type="color" v-model="settings.qr_color" />
          </label>
          <label
            >{{ t("اندازه QR")
            }}<input
              type="number"
              min="128"
              max="2048"
              v-model.number="settings.qr_size"
            />
          </label>
        </div>
        <h2>{{ t("ظاهر منو") }}</h2>
        <AppearanceDesigner />
        <div class="ac-form-grid">
          <label
            v-for="(label, key) in {
              accent: 'رنگ اصلی',
              background: 'پس‌زمینه',
              category_background: 'زمینه دسته‌ها',
            }"
            >{{ label }}<input type="color" v-model="settings[key]" />
          </label>
          <label
            >{{ t("چیدمان")
            }}<select v-model="settings.layout">
              <option value="grid">{{ t("کارت") }}</option>
              <option value="list">{{ t("فهرست") }}</option>
            </select>
          </label>
          <label
            >{{ t("فونت") }}<input v-model="settings.font_family" />
          </label>
          <label
            >{{ t("آدرس فونت سفارشی")
            }}<input type="url" v-model="settings.custom_font_url" />
          </label>
          <label v-if="bootstrap.can_manage_branches"
            >{{ t("واحد پول فروشگاه")
            }}<select v-model="settings.currency_code">
              <option
                v-for="currency in currencyOptions"
                :key="currency.code"
                :value="currency.code"
              >
                {{ currency.name }} ({{ currency.code }})
              </option>
            </select></label
          >
          <label
            >{{ t("برچسب نمایش (اختیاری)")
            }}<input v-model="settings.currency_label"
          /></label>
          <p class="ac-hint">
            {{
              t(
                "تغییر واحد پول، مبلغ قیمت‌های موجود را تبدیل نمی‌کند. پیش از ذخیره، قیمت‌ها را بررسی کنید.",
              )
            }}
          </p>
          <label
            v-for="key in [
              'title_size_mobile',
              'title_size_desktop',
              'description_size_mobile',
              'description_size_desktop',
              'price_size_mobile',
              'price_size_desktop',
              'title_weight_mobile',
              'title_weight_desktop',
              'description_weight_mobile',
              'description_weight_desktop',
              'price_weight_mobile',
              'price_weight_desktop',
            ]"
            >{{
              {
                title_size_mobile: "اندازه عنوان در موبایل",
                title_size_desktop: "اندازه عنوان در دسکتاپ",
                description_size_mobile: "اندازه توضیح در موبایل",
                description_size_desktop: "اندازه توضیح در دسکتاپ",
                price_size_mobile: "اندازه قیمت در موبایل",
                price_size_desktop: "اندازه قیمت در دسکتاپ",
                title_weight_mobile: "وزن عنوان موبایل",
                title_weight_desktop: "وزن عنوان دسکتاپ",
                description_weight_mobile: "وزن توضیح موبایل",
                description_weight_desktop: "وزن توضیح دسکتاپ",
                price_weight_mobile: "وزن قیمت موبایل",
                price_weight_desktop: "وزن قیمت دسکتاپ",
              }[key]
            }}<input type="number" min="1" v-model.number="settings[key]" />
          </label>
        </div>
        <h3>{{ t("پیام‌های مشتری") }}</h3>
        <label v-for="key in ['unavailable', 'closed', 'order_received']"
          >{{
            {
              unavailable: "ناموجود",
              closed: "توقف سفارش",
              order_received: "ثبت سفارش",
            }[key]
          }}<input
            :value="settings.messages?.[key]"
            @input="
              settings.messages = {
                ...(settings.messages || {}),
                [key]: $event.target.value,
              }
            "
          />
        </label>
        <RestaurantTranslations :settings="settings" /><button
          class="ac-primary"
          :disabled="busy"
          @click="saveSettings"
        >
          {{ t("ذخیره تنظیمات") }}
        </button>
      </section>
      <AppearancePreview />
    </div>
  </template>
</template>
