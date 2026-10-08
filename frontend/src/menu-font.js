const documents = new WeakMap();
export function loadMenuFont(settings, targetDocument) {
  const url = settings.custom_font_url;
  const Font = targetDocument?.defaultView?.FontFace;
  if (!url || !Font || !targetDocument.fonts) return Promise.resolve();
  let cache = documents.get(targetDocument);
  if (!cache) {
    cache = new Map();
    documents.set(targetDocument, cache);
  }
  const family = settings.font_family || "AdminCafeCustom";
  const key = `${family}:${url}`;
  if (!cache.has(key)) {
    const font = new Font(family, `url(${JSON.stringify(url)})`);
    const loading = font.load().then(() => {
      targetDocument.fonts.add(font);
    });
    cache.set(key, loading);
  }
  return cache.get(key);
}
