/** Keep backend image order while removing empty, unsafe, and repeated sources. */
export function normalizeGalleryImages(images) {
  const seen = new Set();
  return (Array.isArray(images) ? images : []).flatMap((image) => {
    if (!image || typeof image.src !== "string") return [];
    const src = image.src.trim();
    if (!src || /^(?:javascript|vbscript|data):/i.test(src) || seen.has(src))
      return [];
    seen.add(src);
    return [
      { ...image, src, alt: typeof image.alt === "string" ? image.alt : "" },
    ];
  });
}

export function galleryIndex(index, count) {
  return Math.max(0, Math.min(Math.max(0, count - 1), index));
}

export function galleryKeyIndex(key, index, count, direction = "ltr") {
  if (key === "Home") return 0;
  if (key === "End") return Math.max(0, count - 1);
  if (key !== "ArrowLeft" && key !== "ArrowRight") return null;
  const physicalStep = key === "ArrowRight" ? 1 : -1;
  return galleryIndex(
    index + physicalStep * (direction === "rtl" ? -1 : 1),
    count,
  );
}

export function galleryGestureIntent(dx, dy) {
  if (Math.max(Math.abs(dx), Math.abs(dy)) < 8) return "pending";
  return Math.abs(dx) > Math.abs(dy) * 1.2 ? "horizontal" : "vertical";
}

export function gallerySwipeIndex(
  dx,
  dy,
  width,
  index,
  count,
  direction = "ltr",
) {
  const threshold = Math.max(32, Math.min(72, width * 0.16));
  if (Math.abs(dx) < threshold || Math.abs(dx) <= Math.abs(dy) * 1.2)
    return index;
  const step = (dx < 0 ? 1 : -1) * (direction === "rtl" ? -1 : 1);
  return galleryIndex(index + step, count);
}

export function galleryIndexLabel(template, index, count) {
  return String(template || "")
    .replaceAll("{current}", String(index + 1))
    .replaceAll("{total}", String(count));
}
