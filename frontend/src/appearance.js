export const menuThemes = [
  {
    id: "cafe",
    name: "فندوق",
    description: "روشن، گرم و زیتونی",
    accent: "#4e5c36",
    background: "#f7f6f0",
    category_background: "#eef0e7",
  },
  {
    id: "minimal",
    name: "مینیمال",
    description: "روشن و ساده",
    accent: "#2563eb",
    background: "#f8fafc",
    category_background: "#e2e8f0",
  },
  {
    id: "midnight",
    name: "نیمه‌شب",
    description: "تیره و آرام",
    accent: "#d99964",
    background: "#141b25",
    category_background: "#293649",
  },
  {
    id: "garden",
    name: "باغ",
    description: "سبز و طبیعی",
    accent: "#467454",
    background: "#f3f6ef",
    category_background: "#dfe9da",
  },
  {
    id: "bistro",
    name: "بیسترو",
    description: "پررنگ و اشتهابرانگیز",
    accent: "#b8392f",
    background: "#fff4e7",
    category_background: "#f7dfc4",
  },
];
export const cssBlocks = [
  { key: "general", label: "کل منو", selector: ".ac-product h3", full: true },
  { key: "card", label: "کارت غذا", selector: ".ac-product" },
  { key: "detail", label: "پنجره جزئیات غذا", selector: ".ac-food-dialog" },
  {
    key: "language",
    label: "پنجره انتخاب زبان",
    selector: ".ac-language-dialog",
  },
  {
    key: "categories",
    label: "دکمه‌های دسته‌بندی",
    selector: ".ac-categories button",
  },
  {
    key: "buttons",
    label: "دکمه‌های سفارش",
    selector: ".ac-primary, .ac-plus, .ac-cart-float",
  },
  { key: "cart", label: "پنجره سبد سفارش", selector: ".ac-cart-dialog" },
];
export const themeId = (settings = {}) =>
  menuThemes.some((t) => t.id === settings.menu_theme)
    ? settings.menu_theme
    : "cafe";
export const emptyCustomCss = () =>
  Object.fromEntries(cssBlocks.map(({ key }) => [key, ""]));
export function appearanceStyle(settings = {}) {
  return {
    "--ac-accent": settings.accent,
    "--ac-bg": settings.background,
    "--ac-category": settings.category_background,
    "--ac-font": settings.font_family,
    ...Object.fromEntries(
      ["title", "description", "price"].flatMap((key) =>
        ["mobile", "desktop"].flatMap((device) => [
          [
            `--ac-${key}-${device}`,
            (settings[`${key}_size_${device}`] ||
              {
                title: device === "mobile" ? 18 : 22,
                description: device === "mobile" ? 13 : 14,
                price: device === "mobile" ? 16 : 18,
              }[key]) + "px",
          ],
          [
            `--ac-${key}-weight-${device}`,
            settings[`${key}_weight_${device}`] || settings[`${key}_weight`],
          ],
        ]),
      ),
    ),
  };
}
