<script>
import CompiledMenuCss from "./components/CompiledMenuCss.js";
import LanguageGate from "./components/LanguageGate.vue";
import CountryFlag from "./components/CountryFlag.vue";
import { useMenu } from "./useMenu.js";
export default {
  props: ["config", "mountRoot"],
  components: { LanguageGate, CountryFlag, CompiledMenuCss },
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
      <label class="ac-language-switch"
        ><CountryFlag
          :code="language"
          :label="availableLanguages.find((l) => l.code === language)?.name"
        />{{ t("زبان منو")
        }}<select
          :value="language"
          @change="switchLanguage($event.target.value)"
          :aria-label="t('زبان منو')"
        >
          <option v-for="l in availableLanguages" :key="l.code" :value="l.code">
            {{ l.name }}
          </option>
        </select></label
      >
      <p v-if="boot.branch?.name || config.branchName" class="ac-hint">
        {{ t("شعبه") }}: {{ boot.branch?.name || config.branchName }}
      </p>
      <div v-if="config.demo" class="ac-demo">
        {{ t("پیش‌نمایش نمایشی • سفارش و پرداخت واقعی انجام نمی‌شود")
        }}<a href="/panel.html">{{ t("پنل مدیریت ←") }}</a>
      </div>
      <header
        v-if="!['categories', 'products', 'cart'].includes(config.component)"
        class="ac-header"
      >
        <div class="ac-brand">
          <img
            v-if="boot.settings.logo"
            :src="boot.settings.logo"
            :alt="boot.settings.restaurant_name"
            class="ac-logo"
          /><span v-else class="ac-logo">{{ t("آ") }}</span>
          <div>
            <strong>{{ boot.settings.restaurant_name || t("کافه") }}</strong>
            <small>{{ t("قهوه، گفتگو، لحظه‌های خوب") }}</small>
          </div>
        </div>
        <span class="ac-pill">{{ boot.context?.label || t("خوش آمدید") }}</span>
      </header>
      <div role="alert" class="ac-alert" v-if="error">
        {{ t(error) }}
        <button @click="error = ''" :aria-label="t('بستن خطا')">×</button>
      </div>
      <p v-if="loading" class="ac-loading" role="status" aria-live="polite">
        {{ t("منوی تازه در حال آماده شدن است…") }}
      </p>
      <template v-else>
        <section
          v-if="config.component === 'menu' || !config.component"
          class="ac-hero"
        >
          <div>
            <span class="ac-eyebrow">{{ t("با عشق آماده می‌کنیم") }}</span>
            <h1>{{ boot.settings.tagline || t("یک مکث کوچک، یک حال خوب") }}</h1>
            <p>
              {{ t("از عطر اولین قهوه تا شیرینی آخرین لقمه.") }}<br />{{
                t("انتخاب امروز شما چیست؟")
              }}
            </p>
            <div class="ac-hero-meta">
              <span>{{ t("✦ دانه‌های تازه") }}</span>
              <span>
                {{
                  t("◷ {count} دقیقه آماده‌سازی", {
                    count: number(boot.settings.preparation_minutes || 20),
                  })
                }}</span
              >
            </div>
          </div>
          <img
            v-if="boot.settings.cover"
            :src="boot.settings.cover"
            :alt="t('فضای کافه')"
            class="ac-cover"
          />
          <div v-else class="ac-coffee-art">
            <div class="ac-saucer">
              <div class="ac-cup">
                <div class="ac-foam">❧</div>
              </div>
            </div>
            <span class="ac-bean">◒</span>
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
            <label class="ac-search">
              <span>⌕</span>
              <input
                v-model="search"
                :placeholder="t('جستجو در منو…')"
                :aria-label="t('جستجوی محصولات')"
              />
            </label>
          </div>
          <nav
            v-if="config.component !== 'products'"
            class="ac-categories"
            :aria-label="t('دسته\u200cبندی منو')"
          >
            <button :class="{ active: !category }" @click="category = 0">
              {{ t("✦ همه") }}
            </button>
            <button
              v-for="c in boot.categories"
              :key="c.id"
              :class="{ active: category === c.id }"
              @click="category = c.id"
            >
              {{ c.icon || "◉" }} {{ c.name }}
            </button>
          </nav>
          <div
            v-if="config.component !== 'categories'"
            class="ac-products"
            :class="{ 'ac-list': boot.settings.layout === 'list' }"
          >
            <button
              v-for="p in products"
              :key="p.id"
              class="ac-product"
              @click="open(p)"
            >
              <div
                class="ac-product-art"
                :class="'ac-art-' + (p.art || 'latte')"
              >
                <img
                  v-if="p.image"
                  :src="p.image"
                  :alt="p.name"
                  loading="lazy"
                />
                <template v-else>
                  <span class="ac-art-shape">{{
                    p.category_ids.includes(3)
                      ? "◒"
                      : p.category_ids.includes(4)
                        ? "☀"
                        : "☕"
                  }}</span>
                  <span class="ac-art-lines"> </span>
                </template>
                <span v-if="!p.available" class="ac-stock">{{
                  t("ناموجود")
                }}</span>
              </div>
              <div class="ac-product-copy">
                <h3>{{ p.name }}</h3>
                <p>{{ p.short_description || p.description }}</p>
                <div class="ac-product-bottom">
                  <strong>{{ money(p.price, boot.currency_symbol) }}</strong>
                  <span class="ac-plus">{{
                    enabled && p.available ? "+" : "↗"
                  }}</span>
                </div>
              </div>
            </button>
            <p v-if="!products.length" class="ac-empty">
              {{ t("محصولی با این انتخاب پیدا نشد.") }}
            </p>
          </div>
        </section>
        <section v-if="tracking" class="ac-tracking" aria-live="polite">
          <span>✓</span>
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
          @click="cartOpen = true"
        >
          <span>{{ t("{count} انتخاب شما", { count: number(count) }) }}</span>
          <strong>{{ money(total, boot.currency_symbol) }} ←</strong>
        </button>
        <section
          v-if="config.component === 'cart' && enabled"
          class="ac-embedded-cart"
        >
          <h2>{{ t("سبد شما") }}</h2>
          <p v-if="!count">{{ t("هنوز چیزی انتخاب نکرده‌اید.") }}</p>
          <button v-else class="ac-primary" @click="cartOpen = true">
            {{ t("مشاهده {count} انتخاب", { count: number(count) }) }}
          </button>
        </section>
      </template>
      <div v-if="selected || cartOpen" class="ac-overlay" @click.self="close">
        <section
          class="ac-dialog"
          :class="selected ? 'ac-food-dialog' : 'ac-cart-dialog'"
          role="dialog"
          aria-modal="true"
          :aria-label="t(selected ? 'جزئیات محصول' : 'سبد سفارش')"
          @keydown.tab="trap"
        >
          <button class="ac-close" @click="close" :aria-label="t('بستن')">
            ×
          </button>
          <template v-if="selected">
            <span class="ac-eyebrow">{{ t("یک انتخاب خوش‌طعم") }}</span>
            <h2>{{ selected.name }}</h2>
            <p>{{ selected.description }}</p>
            <strong>{{ money(selected.price, boot.currency_symbol) }}</strong>
            <label v-if="selected.type === 'variable'"
              >{{ t("انتخاب نوع")
              }}<select v-model="variation">
                <option :value="0">{{ t("انتخاب کنید") }}</option>
                <option
                  v-for="v in selected.variations"
                  :value="v.id"
                  :disabled="!v.available"
                >
                  {{ v.name }} — {{ money(v.price, boot.currency_symbol) }}
                </option>
              </select>
            </label>
            <button
              v-if="enabled"
              class="ac-primary"
              :disabled="!selected.available"
              @click="add"
            >
              {{
                t(
                  selected.available
                    ? "افزودن به انتخاب‌ها"
                    : "در حال حاضر ناموجود",
                )
              }}
            </button>
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
                  −
                </button>
                <span>{{ number(i.quantity) }}</span>
                <button
                  @click="change(i, 1)"
                  :aria-label="t('افزایش {name}', { name: label(i) })"
                >
                  +
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
