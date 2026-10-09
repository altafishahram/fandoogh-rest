<script>
import { h } from "vue";
import FoodGallery from "./components/FoodGallery.vue";
import CompiledMenuCss from "./components/CompiledMenuCss.js";
import LanguageGate from "./components/LanguageGate.vue";
import CountryFlag from "./components/CountryFlag.vue";
import { useMenu } from "./useMenu.js";
const paths = {
  search: "M21 21l-5-5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0",
  plus: "M12 5v14M5 12h14",
  minus: "M5 12h14",
  close: "M6 6l12 12M6 18 18 6",
  bag: "M5 7h14l1 14H4L5 7ZM8 8V6a4 4 0 0 1 8 0v2",
  pin: "M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0ZM12 8v4M10 10h4",
  clock: "M12 8v4l3 2M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0",
  food: "M4 18h16M6 15a6 6 0 0 1 12 0M12 6v2M3 21h18",
  check: "M5 12l4 4L19 6",
  arrow: "M5 12h14m-6-6 6 6-6 6",
  nut: "M6 10c-2 4-1 10 6 12 7-2 8-8 6-12M5 10c4-4 10-4 14 0M12 8c0-4 3-6 6-6-1 4-3 6-6 6Zm0 0C11 4 8 3 5 3c0 3 3 5 7 5ZM12 11v7",
};
const MenuIcon = {
  props: { name: String },
  setup: (props) => () =>
    h(
      "svg",
      {
        viewBox: "0 0 24 24",
        fill: "none",
        stroke: "currentColor",
        "stroke-width": 1.5,
        "stroke-linecap": "round",
        "stroke-linejoin": "round",
        "aria-hidden": "true",
        focusable: "false",
        class: "ac-menu-icon",
      },
      [h("path", { d: paths[props.name] || paths.food })],
    ),
};
export default {
  props: ["config", "mountRoot"],
  components: {
    LanguageGate,
    CountryFlag,
    CompiledMenuCss,
    FoodGallery,
    MenuIcon,
  },
  setup(props) {
    return useMenu(props.config, props.mountRoot);
  },
};
</script>
<template>
  <div
    class="ac-menu"
    :data-ac-theme="menuTheme"
    :dir="direction"
    :lang="locale"
    :style="style"
    @keydown.esc="close"
  >
    <CompiledMenuCss :css="customCss" />
    <LanguageGate
      v-if="ownsGate"
      :languages="availableLanguages"
      :suggested="language"
      @choose="confirmLanguage"
    /><template v-if="entered">
      <div v-if="config.demo" class="ac-demo">
        {{ t("پیش‌نمایش نمایشی • سفارش و پرداخت واقعی انجام نمی‌شود") }}
        <a href="/panel.html">{{ t("پنل مدیریت ←") }}</a>
      </div>
      <header
        class="ac-header"
        :class="{
          'ac-compact-header': ['categories', 'products', 'cart'].includes(
            config.component,
          ),
        }"
      >
        <div
          v-if="!['categories', 'products', 'cart'].includes(config.component)"
          class="ac-brand"
        >
          <img
            v-if="boot.settings.logo"
            :src="boot.settings.logo"
            :alt="boot.settings.restaurant_name"
            class="ac-logo"
          />
          <span v-else class="ac-logo"><MenuIcon name="nut" /></span>
          <div>
            <strong>{{ boot.settings.restaurant_name || t("کافه") }}</strong
            ><small>{{ t("آشپزخانه و کافه") }}</small>
          </div>
        </div>
        <div class="ac-header-actions">
          <span
            v-if="boot.branch?.name || config.branchName"
            class="ac-branch-label"
            ><MenuIcon name="pin" />{{
              boot.branch?.name || config.branchName
            }}</span
          >
          <label class="ac-language-switch">
            <CountryFlag
              :code="language"
              :label="availableLanguages.find((l) => l.code === language)?.name"
            />
            <span class="ac-visually-hidden">{{ t("زبان منو") }}</span>
            <select
              :value="language"
              @change="switchLanguage($event.target.value)"
              :aria-label="t('زبان منو')"
            >
              <option
                v-for="l in availableLanguages"
                :key="l.code"
                :value="l.code"
              >
                {{ l.name }}
              </option>
            </select>
          </label>
          <button
            v-if="enabled"
            class="ac-header-cart"
            @click="openCart($event)"
            :aria-label="t('سبد شما')"
          >
            <MenuIcon name="bag" /><span v-if="count" class="ac-cart-count">{{
              number(count)
            }}</span>
          </button>
        </div>
      </header>
      <div role="alert" class="ac-alert" v-if="error">
        {{ t(error) }}
        <button @click="error = ''" :aria-label="t('بستن خطا')">
          <MenuIcon name="close" />
        </button>
      </div>
      <p v-if="loading" class="ac-loading" role="status" aria-live="polite">
        {{ t("منوی تازه در حال آماده شدن است…") }}
      </p>
      <template v-else>
        <section
          v-if="config.component === 'menu' || !config.component"
          class="ac-hero"
          :class="{
            'ac-hero-text': !boot.settings.cover && !heroProduct?.image,
          }"
        >
          <div class="ac-hero-copy">
            <span class="ac-eyebrow">{{ t("با عشق آماده می‌کنیم") }}</span>
            <h1>{{ boot.settings.tagline || t("یک مکث کوچک، یک حال خوب") }}</h1>
            <p>
              {{ t("از عطر اولین قهوه تا شیرینی آخرین لقمه.") }}
              {{ t("انتخاب امروز شما چیست؟") }}
            </p>
            <div class="ac-hero-meta">
              <span class="ac-pill">{{
                boot.context?.label || t(enabled ? "سفارش آنلاین" : "منوی کافه")
              }}</span>
              <span
                ><MenuIcon name="clock" />{{
                  t("{count} دقیقه آماده‌سازی", {
                    count: number(boot.settings.preparation_minutes || 20),
                  })
                }}</span
              >
              <span
                v-if="boot.branch?.name || config.branchName"
                class="ac-mobile-branch"
                ><MenuIcon name="pin" />{{
                  boot.branch?.name || config.branchName
                }}</span
              >
            </div>
          </div>
          <div
            v-if="boot.settings.cover || heroProduct?.image"
            class="ac-hero-frame"
          >
            <img
              :src="boot.settings.cover || heroProduct.image"
              :alt="boot.settings.cover ? t('فضای کافه') : heroProduct.name"
              class="ac-cover"
            />
            <button
              v-if="!boot.settings.cover && heroProduct"
              class="ac-hero-feature"
              @click="open(heroProduct, $event)"
            >
              <small>{{ t("از منوی ما") }}</small
              ><strong>{{ heroProduct.name }}</strong>
            </button>
          </div>
        </section>
        <section v-if="config.component !== 'cart'" class="ac-catalog">
          <div class="ac-section-title">
            <div>
              <span class="ac-eyebrow">{{
                t("طعم مورد علاقه‌تان را پیدا کنید")
              }}</span>
              <h2>{{ t("منوی کافه") }}</h2>
            </div>
            <label class="ac-search"
              ><MenuIcon name="search" /><input
                v-model="search"
                type="search"
                :placeholder="t('جستجو در منو…')"
                :aria-label="t('جستجوی محصولات')"
            /></label>
          </div>
          <nav
            v-if="config.component !== 'products'"
            class="ac-categories"
            :aria-label="t('دسته‌بندی منو')"
          >
            <button
              :class="{ active: !category }"
              :aria-pressed="!category"
              @click="category = 0"
            >
              {{ t("همه") }}
            </button>
            <button
              v-for="c in boot.categories"
              :key="c.id"
              :class="{ active: category === c.id }"
              :aria-pressed="category === c.id"
              @click="category = c.id"
            >
              {{ c.name }}
            </button>
          </nav>
          <div
            v-if="config.component !== 'categories'"
            class="ac-products"
            :class="{ 'ac-list': boot.settings.layout === 'list' }"
          >
            <article
              v-for="p in products"
              :key="p.id"
              class="ac-product"
              :class="{ 'ac-product-no-photo': !p.image }"
            >
              <button
                class="ac-product-art"
                @click="open(p, $event)"
                :aria-label="t('جزئیات {name}', { name: p.name })"
              >
                <img
                  v-if="p.image"
                  :src="p.image"
                  :alt="p.name"
                  loading="lazy"
                  decoding="async"
                />
                <span v-else class="ac-photo-placeholder"
                  ><MenuIcon name="food" /><span>{{
                    t("تصویر در دسترس نیست")
                  }}</span></span
                >
                <span v-if="!p.available" class="ac-stock">{{
                  t("ناموجود")
                }}</span>
              </button>
              <div class="ac-product-copy">
                <button class="ac-product-title" @click="open(p, $event)">
                  <h3>{{ p.name }}</h3>
                </button>
                <p>{{ p.short_description || p.description }}</p>
                <div class="ac-product-bottom">
                  <strong>{{ money(p.price, boot.currency_symbol) }}</strong>
                  <div
                    v-if="
                      enabled &&
                      p.available &&
                      quantityFor(p) &&
                      p.type !== 'variable'
                    "
                    class="ac-stepper ac-card-stepper"
                  >
                    <button
                      @click="changeProduct(p, -1)"
                      :aria-label="t('کاهش {name}', { name: p.name })"
                    >
                      <MenuIcon name="minus" /></button
                    ><span>{{ number(quantityFor(p)) }}</span
                    ><button
                      class="ac-plus"
                      @click="changeProduct(p, 1)"
                      :aria-label="t('افزایش {name}', { name: p.name })"
                    >
                      <MenuIcon name="plus" />
                    </button>
                  </div>
                  <button
                    v-else-if="enabled"
                    class="ac-plus"
                    :disabled="!p.available"
                    @click="quickAdd(p, $event)"
                    :aria-label="
                      t(
                        p.type === 'variable'
                          ? 'انتخاب گزینه برای {name}'
                          : 'افزودن {name}',
                        { name: p.name },
                      )
                    "
                  >
                    <MenuIcon name="plus" />
                  </button>
                  <button
                    v-else
                    class="ac-plus"
                    @click="open(p, $event)"
                    :aria-label="t('جزئیات {name}', { name: p.name })"
                  >
                    <MenuIcon name="arrow" />
                  </button>
                </div>
              </div>
            </article>
            <div v-if="!products.length" class="ac-empty">
              <MenuIcon name="search" />
              <p>{{ t("محصولی با این انتخاب پیدا نشد.") }}</p>
              <button
                class="ac-filter-reset"
                @click="
                  search = '';
                  category = 0;
                "
              >
                {{ t("پاک کردن فیلترها") }}
              </button>
            </div>
          </div>
        </section>
        <section v-if="tracking" class="ac-tracking" aria-live="polite">
          <span><MenuIcon name="check" /></span>
          <div>
            <h3>
              {{
                t(
                  boot.settings.messages?.order_received ||
                    "سفارش {number} ثبت شد",
                  { number: number(tracking.number) },
                )
              }}
            </h3>
            <p>
              {{
                t("وضعیت: {stage}", {
                  stage: t(stages[tracking.stage] || tracking.stage),
                })
              }}
            </p>
            <small>{{
              t("آماده‌سازی پس از تأیید کارکنان شروع می‌شود.")
            }}</small>
          </div>
        </section>
        <footer
          v-if="!config.component || config.component === 'menu'"
          class="ac-footer"
        >
          <strong>{{ boot.settings.restaurant_name }}</strong>
          <p>
            {{
              boot.settings.restaurant_address ||
              t("اینجا، برای لحظه‌های خوب شما.")
            }}
          </p>
          <small>{{
            boot.settings.hours_text ||
            t("با آرامش انتخاب کنید، با عشق آماده می‌کنیم.")
          }}</small>
        </footer>
        <button
          v-if="count && enabled"
          class="ac-cart-float"
          @click="openCart($event)"
        >
          <span>{{ t("{count} انتخاب شما", { count: number(count) }) }}</span>
          <strong
            >{{ money(total, boot.currency_symbol) }} <MenuIcon name="bag"
          /></strong>
        </button>
        <section
          v-if="config.component === 'cart' && enabled"
          class="ac-embedded-cart"
        >
          <h2>{{ t("سبد شما") }}</h2>
          <p v-if="!count">{{ t("هنوز چیزی انتخاب نکرده‌اید.") }}</p>
          <button v-else class="ac-primary" @click="openCart($event)">
            {{ t("مشاهده {count} انتخاب", { count: number(count) }) }}
          </button>
        </section>
      </template>
      <div v-if="selected || cartOpen" class="ac-overlay" @click.self="close">
        <section
          class="ac-dialog"
          :class="[
            selected ? 'ac-food-dialog' : 'ac-cart-dialog',
            { 'ac-keyboard-open': openingFromKeyboard },
          ]"
          role="dialog"
          aria-modal="true"
          :aria-label="t(selected ? 'جزئیات محصول' : 'سبد سفارش')"
          @keydown.tab="trap"
        >
          <button class="ac-close" @click="close" :aria-label="t('بستن')">
            <MenuIcon name="close" />
          </button>
          <template v-if="selected">
            <div class="ac-detail-media">
              <FoodGallery
                :key="`${selected.id}:${variation}`"
                :images="galleryImages"
                :name="selected.name"
                :image-label="t('تصاویر {name}', { name: selected.name })"
                :index-label="t('تصویر {current} از {total}')"
                :error-label="t('تصویر در دسترس نیست')"
                :empty-label="t('تصویر در دسترس نیست')"
                :direction="direction"
              />
            </div>
            <div class="ac-detail-copy">
              <span class="ac-eyebrow">{{ t("یک انتخاب خوش‌طعم") }}</span>
              <h2>{{ selected.name }}</h2>
              <p class="ac-detail-description">
                {{ selected.description || selected.short_description }}
              </p>
              <strong class="ac-detail-price">{{
                money(
                  selected.variations?.find((v) => v.id === Number(variation))
                    ?.price ?? selected.price,
                  boot.currency_symbol,
                )
              }}</strong>
              <label v-if="selected.type === 'variable'"
                >{{ t("انتخاب نوع")
                }}<select v-model="variation">
                  <option :value="0">{{ t("انتخاب کنید") }}</option>
                  <option
                    v-for="v in selected.variations"
                    :key="v.id"
                    :value="v.id"
                    :disabled="!v.available"
                  >
                    {{ v.name }} — {{ money(v.price, boot.currency_symbol) }}
                  </option>
                </select></label
              >
              <template v-if="enabled">
                <div v-if="selected.available" class="ac-detail-quantity">
                  <span>{{ t("تعداد") }}</span>
                  <div class="ac-stepper">
                    <button
                      :disabled="quantity <= 1"
                      @click="quantity = Math.max(1, quantity - 1)"
                      :aria-label="t('کاهش {name}', { name: selected.name })"
                    >
                      <MenuIcon name="minus" /></button
                    ><span>{{ number(quantity) }}</span
                    ><button
                      :disabled="quantity >= 99"
                      @click="quantity = Math.min(99, quantity + 1)"
                      :aria-label="t('افزایش {name}', { name: selected.name })"
                    >
                      <MenuIcon name="plus" />
                    </button>
                  </div>
                </div>
                <button
                  class="ac-primary"
                  :disabled="
                    !selected.available ||
                    (selected.type === 'variable' &&
                      (!variation ||
                        !selected.variations?.find(
                          (v) => v.id === Number(variation),
                        )?.available))
                  "
                  @click="add"
                >
                  <MenuIcon name="bag" />{{
                    t(
                      selected.available
                        ? "افزودن به انتخاب‌ها"
                        : "در حال حاضر ناموجود",
                    )
                  }}
                </button>
              </template>
            </div>
          </template>
          <template v-else>
            <span class="ac-eyebrow">{{ t("کمی تا یک حال خوب") }}</span>
            <h2>{{ t("انتخاب‌های شما") }}</h2>
            <p v-if="!count">{{ t("سبد شما خالی است.") }}</p>
            <div v-for="i in state.cart" :key="i.key" class="ac-cart-row">
              <div>
                <strong>{{ label(i) }}</strong>
                <small>{{ money(i.price, boot.currency_symbol) }}</small>
              </div>
              <div class="ac-stepper">
                <button
                  @click="change(i, -1)"
                  :aria-label="t('کاهش {name}', { name: label(i) })"
                >
                  <MenuIcon name="minus" />
                </button>
                <span>{{ number(i.quantity) }}</span>
                <button
                  @click="change(i, 1)"
                  :aria-label="t('افزایش {name}', { name: label(i) })"
                >
                  <MenuIcon name="plus" />
                </button>
              </div>
            </div>
            <label
              >{{ t("نوع سفارش")
              }}<select v-model="channel">
                <option
                  v-if="boot.context?.can_order && boot.ordering.dine_in"
                  value="table"
                >
                  {{ t("سرو در {table}", { table: boot.context.label }) }}
                </option>
                <option v-if="boot.ordering.pickup" value="pickup">
                  {{ t("تحویل حضوری") }}
                </option>
                <option v-if="boot.ordering.delivery" value="delivery">
                  {{ t("ارسال با پیک") }}
                </option>
              </select>
            </label>
            <label
              >{{ t("نام شما (اختیاری)")
              }}<input v-model="name" maxlength="80" />
            </label>
            <label
              >{{ t("توضیحات سفارش")
              }}<textarea
                v-model="note"
                rows="2"
                maxlength="500"
                :placeholder="t('مثلاً بدون شکر…')"
              ></textarea>
            </label>
            <div class="ac-total">
              <span>{{ t("جمع انتخاب‌ها") }}</span>
              <strong>{{ money(total, boot.currency_symbol) }}</strong>
            </div>
            <p class="ac-hint">
              {{
                t(
                  channel === "table"
                    ? "پس از تأیید کارکنان، سفارش آماده می‌شود. پرداخت با صندوق."
                    : "هزینه ارسال، مالیات و پرداخت در صفحه امن فروشگاه محاسبه می‌شود.",
                )
              }}
            </p>
            <button
              class="ac-primary"
              :disabled="busy || !count || !enabled"
              @click="submit"
            >
              {{
                t(
                  busy
                    ? "در حال ثبت…"
                    : channel === "table"
                      ? "ارسال برای تأیید"
                      : "ادامه و پرداخت",
                )
              }}
            </button>
          </template>
        </section>
      </div>
    </template>
  </div>
</template>
