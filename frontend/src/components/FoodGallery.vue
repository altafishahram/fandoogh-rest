<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import {
  galleryGestureIntent,
  galleryIndex,
  galleryIndexLabel,
  galleryKeyIndex,
  gallerySwipeIndex,
  normalizeGalleryImages,
} from "../gallery.js";

const props = defineProps({
  images: { type: Array, default: () => [] },
  name: { type: String, default: "" },
  imageLabel: { type: String, default: "" },
  indexLabel: { type: String, default: "{current} / {total}" },
  errorLabel: { type: String, default: "" },
  emptyLabel: { type: String, default: "" },
  direction: { type: String, default: "ltr" },
});
const emit = defineEmits(["change"]);
const images = computed(() => normalizeGalleryImages(props.images));
const orderedImages = computed(() =>
  props.direction === "rtl" ? [...images.value].reverse() : images.value,
);
const index = ref(0);
const failed = ref(new Set());
const viewport = ref(null);
const dragX = ref(0);
const dragging = ref(false);
const instant = ref(false);
let gesture = null;
const physicalIndex = computed(() =>
  props.direction === "rtl"
    ? images.value.length - 1 - index.value
    : index.value,
);
const trackStyle = computed(() => ({
  transform: `translateX(calc(${-physicalIndex.value * 100}% + ${dragX.value}px))`,
}));
const currentLabel = computed(() =>
  galleryIndexLabel(props.indexLabel, index.value, images.value.length),
);
const announcement = ref("");

function select(next, keyboard = false) {
  instant.value = keyboard;
  const clamped = galleryIndex(next, images.value.length);
  if (clamped === index.value) return;
  index.value = clamped;
  announcement.value = currentLabel.value;
  emit("change", clamped);
}
function keydown(event) {
  if (images.value.length < 2 || event.altKey || event.ctrlKey || event.metaKey)
    return;
  const next = galleryKeyIndex(
    event.key,
    index.value,
    images.value.length,
    props.direction,
  );
  if (next === null) return;
  event.preventDefault();
  select(next, true);
}
function resetGesture() {
  const pointerId = gesture?.id;
  gesture = null;
  dragging.value = false;
  dragX.value = 0;
  if (
    pointerId !== undefined &&
    viewport.value?.hasPointerCapture?.(pointerId)
  ) {
    viewport.value.releasePointerCapture(pointerId);
  }
}
function pointerdown(event) {
  if (!event.isPrimary) {
    resetGesture();
    return;
  }
  if (
    gesture ||
    images.value.length < 2 ||
    (event.pointerType === "mouse" && event.button !== 0)
  )
    return;
  instant.value = false;
  gesture = {
    id: event.pointerId,
    x: event.clientX,
    y: event.clientY,
    dx: 0,
    dy: 0,
    intent: "pending",
  };
  // Mouse drags must survive leaving the image, without capturing touch scrolling.
  if (event.pointerType === "mouse")
    viewport.value.setPointerCapture(event.pointerId);
}
function pointermove(event) {
  if (!gesture || event.pointerId !== gesture.id) return;
  gesture.dx = event.clientX - gesture.x;
  gesture.dy = event.clientY - gesture.y;
  if (gesture.intent === "pending")
    gesture.intent = galleryGestureIntent(gesture.dx, gesture.dy);
  if (gesture.intent === "vertical") {
    resetGesture();
    return;
  }
  if (gesture.intent !== "horizontal") return;
  dragging.value = true;
  if (!viewport.value.hasPointerCapture(event.pointerId))
    viewport.value.setPointerCapture(event.pointerId);
  const atEdge =
    (physicalIndex.value === 0 && gesture.dx > 0) ||
    (physicalIndex.value === images.value.length - 1 && gesture.dx < 0);
  dragX.value = gesture.dx * (atEdge ? 0.25 : 1);
}
function pointerup(event) {
  if (!gesture || event.pointerId !== gesture.id) return;
  const active = gesture;
  const dx = event.clientX - active.x;
  const dy = event.clientY - active.y;
  resetGesture();
  if (active.intent === "horizontal")
    select(
      gallerySwipeIndex(
        dx,
        dy,
        viewport.value.clientWidth,
        index.value,
        images.value.length,
        props.direction,
      ),
    );
}
function imageError(src) {
  failed.value = new Set([...failed.value, src]);
}
watch(images, (next, previous) => {
  const currentSrc = previous?.[index.value]?.src;
  const preserved = next.findIndex((image) => image.src === currentSrc);
  index.value = preserved >= 0 ? preserved : 0;
  failed.value = new Set(
    [...failed.value].filter((src) => next.some((image) => image.src === src)),
  );
  announcement.value = "";
  resetGesture();
});
watch(() => props.direction, resetGesture);
onBeforeUnmount(resetGesture);
</script>

<template>
  <div
    class="food-gallery"
    :dir="direction"
    role="region"
    :aria-label="imageLabel || name"
    :tabindex="images.length > 1 ? 0 : undefined"
    @keydown="keydown"
  >
    <div
      ref="viewport"
      class="food-gallery__viewport"
      :class="{ 'food-gallery__viewport--interactive': images.length > 1 }"
      @pointerdown="pointerdown"
      @pointermove="pointermove"
      @pointerup="pointerup"
      @pointercancel="resetGesture"
      @lostpointercapture="resetGesture"
      @dragstart.prevent
    >
      <div
        v-if="images.length"
        class="food-gallery__track"
        :class="{ 'food-gallery__track--instant': dragging || instant }"
        :style="trackStyle"
      >
        <div
          v-for="image in orderedImages"
          :key="image.src"
          class="food-gallery__slide"
          :aria-hidden="image.src !== images[index]?.src"
        >
          <img
            v-if="!failed.has(image.src)"
            :src="image.src"
            :alt="image.alt || name"
            :width="image.width || undefined"
            :height="image.height || undefined"
            :loading="image.src === images[index]?.src ? 'eager' : 'lazy'"
            decoding="async"
            draggable="false"
            @error="imageError(image.src)"
          />
          <div
            v-else
            class="food-gallery__fallback"
            role="img"
            :aria-label="errorLabel || name"
          >
            <span>{{ errorLabel }}</span>
          </div>
        </div>
      </div>
      <div
        v-else
        class="food-gallery__fallback"
        role="img"
        :aria-label="emptyLabel || name"
      >
        <span>{{ emptyLabel }}</span>
      </div>
    </div>
    <div
      v-if="images.length > 1"
      class="food-gallery__indicators"
      :aria-label="imageLabel || name"
    >
      <button
        v-for="(image, imageIndex) in images"
        :key="image.src"
        type="button"
        class="food-gallery__dot"
        :class="{ 'food-gallery__dot--active': imageIndex === index }"
        :aria-label="galleryIndexLabel(indexLabel, imageIndex, images.length)"
        :aria-pressed="imageIndex === index"
        @click="select(imageIndex, $event.detail === 0)"
      >
        <span aria-hidden="true"></span>
      </button>
    </div>
    <span class="food-gallery__sr" aria-live="polite" aria-atomic="true">{{
      announcement
    }}</span>
  </div>
</template>

<style scoped>
.food-gallery {
  width: 100%;
  min-width: 0;
}
.food-gallery:focus-visible {
  outline: 2px solid var(--menu-accent, currentColor);
  outline-offset: 4px;
  border-radius: 12px;
}
.food-gallery__viewport {
  width: 100%;
  aspect-ratio: 4 / 3;
  overflow: hidden;
  border-radius: inherit;
  background: var(--gallery-background, #f1eee8);
  touch-action: pan-y pinch-zoom;
}
.food-gallery__viewport--interactive {
  cursor: grab;
}
.food-gallery__viewport--interactive:active {
  cursor: grabbing;
}
.food-gallery__track {
  display: flex;
  direction: ltr;
  height: 100%;
  transition: transform 200ms cubic-bezier(0.23, 1, 0.32, 1);
}
.food-gallery__track--instant {
  transition: none;
}
.food-gallery__slide {
  flex: 0 0 100%;
  min-width: 0;
  height: 100%;
}
.food-gallery__slide img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
  user-select: none;
  -webkit-user-select: none;
}
.food-gallery__fallback {
  height: 100%;
  display: grid;
  place-items: center;
  padding: 24px;
  box-sizing: border-box;
  text-align: center;
  color: var(--gallery-fallback-color, #655e52);
  font-size: 0.875rem;
}
.food-gallery__indicators {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
  padding: 2px 8px;
}
.food-gallery__dot {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  flex: 0 0 44px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: inherit;
  cursor: pointer;
}
.food-gallery__dot span {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  opacity: 0.4;
  transition:
    opacity 120ms ease,
    transform 120ms ease;
}
.food-gallery__dot--active span {
  opacity: 1;
  transform: scale(1.5);
}
.food-gallery__dot:active span {
  transform: scale(0.95);
}
.food-gallery__dot:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: -6px;
}
.food-gallery__sr {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
  border: 0;
}
@media (prefers-reduced-motion: reduce) {
  .food-gallery__track,
  .food-gallery__dot span {
    transition: none;
  }
}
</style>
