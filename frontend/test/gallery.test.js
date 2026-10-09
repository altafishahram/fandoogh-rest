import { test } from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import * as Vue from "vue";
import { parse, compileScript, compileTemplate } from "@vue/compiler-sfc";
import * as helpers from "../src/gallery.js";
import {
  galleryGestureIntent,
  galleryIndexLabel,
  galleryKeyIndex,
  gallerySwipeIndex,
  normalizeGalleryImages,
} from "../src/gallery.js";

test("gallery retains real image metadata and removes invalid or duplicate URLs without mutating input", () => {
  const images = [
    {
      id: 7,
      src: " /media/food.webp ",
      alt: "Coffee",
      width: 800,
      height: 600,
    },
    { src: "/media/food.webp" },
    null,
    { src: "" },
    { src: "javascript:alert(1)" },
    { src: "data:text/html,broken" },
    { src: "/media/second.webp" },
  ];
  assert.deepEqual(normalizeGalleryImages(images), [
    { id: 7, src: "/media/food.webp", alt: "Coffee", width: 800, height: 600 },
    { src: "/media/second.webp", alt: "" },
  ]);
  assert.equal(images[0].src, " /media/food.webp ");
  assert.deepEqual(normalizeGalleryImages(undefined), []);
});

test("keyboard follows physical image positions in both reading directions, with bounded endpoints", () => {
  assert.equal(galleryKeyIndex("ArrowRight", 0, 3), 1);
  assert.equal(galleryKeyIndex("ArrowLeft", 0, 3), 0);
  assert.equal(galleryKeyIndex("ArrowLeft", 0, 3, "rtl"), 1);
  assert.equal(galleryKeyIndex("ArrowRight", 0, 3, "rtl"), 0);
  assert.equal(galleryKeyIndex("Home", 2, 3, "rtl"), 0);
  assert.equal(galleryKeyIndex("End", 0, 3, "rtl"), 2);
  assert.equal(galleryKeyIndex("ArrowRight", 0, 0), 0);
  assert.equal(galleryKeyIndex("Tab", 1, 3), null);
});

test("gesture intent protects vertical scrolling and ignores tiny movements", () => {
  assert.equal(galleryGestureIntent(7, 3), "pending");
  assert.equal(galleryGestureIntent(9, 30), "vertical");
  assert.equal(galleryGestureIntent(30, 27), "vertical");
  assert.equal(galleryGestureIntent(30, 5), "horizontal");
});

test("swipes respect threshold, vertical dominance, reading direction and gallery boundaries", () => {
  assert.equal(gallerySwipeIndex(-80, 5, 400, 0, 3), 1);
  assert.equal(gallerySwipeIndex(80, 5, 400, 0, 3, "rtl"), 1);
  assert.equal(gallerySwipeIndex(-20, 0, 400, 1, 3), 1);
  assert.equal(gallerySwipeIndex(-100, 100, 400, 1, 3), 1);
  assert.equal(gallerySwipeIndex(-100, 0, 400, 2, 3), 2);
  assert.equal(gallerySwipeIndex(-100, 0, 400, 0, 1), 0);
});

test("accessible image count uses supplied localized wording", () => {
  assert.equal(
    galleryIndexLabel("تصویر {current} از {total}", 1, 3),
    "تصویر 2 از 3",
  );
  assert.equal(
    galleryIndexLabel("Photo {current} of {total}", 0, 1),
    "Photo 1 of 1",
  );
});

const source = await readFile(
  new URL("../src/components/FoodGallery.vue", import.meta.url),
  "utf8",
);
const { descriptor } = parse(source);
const setupGallery = new Function(
  "Vue",
  "helpers",
  "defineProps",
  "defineEmits",
  "const {computed,onBeforeUnmount,ref,watch}=Vue; const {galleryGestureIntent,galleryIndex,galleryIndexLabel,galleryKeyIndex,gallerySwipeIndex,normalizeGalleryImages}=helpers;" +
    descriptor.scriptSetup.content.replace(/import[^;]+;/g, "") +
    "; return {index,viewport,dragX,dragging,pointerdown,pointermove,pointerup,resetGesture,keydown,announcement,failed,imageError};",
);

function mountGallery(direction = "ltr") {
  const props = Vue.reactive({
    images: [
      { src: "/one.webp" },
      { src: "/two.webp" },
      { src: "/three.webp" },
    ],
    direction,
    indexLabel: "تصویر {current} از {total}",
  });
  const events = [];
  let state;
  const renderer = Vue.createRenderer({
    createComment: () => ({}),
    insert() {},
    remove() {},
    parentNode: () => null,
    nextSibling: () => null,
  });
  const app = renderer.createApp({
    setup() {
      state = setupGallery(
        Vue,
        helpers,
        () => props,
        () =>
          (...event) =>
            events.push(event),
      );
      return () => null;
    },
  });
  app.mount({});
  const captured = new Set();
  state.viewport.value = {
    clientWidth: 400,
    hasPointerCapture: (id) => captured.has(id),
    setPointerCapture: (id) => captured.add(id),
    releasePointerCapture: (id) => captured.delete(id),
  };
  const pointer = (x, y = 0, extra = {}) => ({
    pointerId: 1,
    isPrimary: true,
    pointerType: "mouse",
    button: 0,
    clientX: x,
    clientY: y,
    ...extra,
  });
  return { state, app, props, events, captured, pointer };
}

test("Vue gallery compiles with direct image selection controls only", () => {
  const script = compileScript(descriptor, { id: "gallery-test" });
  const template = compileTemplate({
    source: descriptor.template.content,
    filename: "FoodGallery.vue",
    id: "gallery-test",
    compilerOptions: { bindingMetadata: script.bindings },
  });
  assert.deepEqual(template.errors, []);
});

test("real gallery handlers drag, announce changes and cancel cleanly", () => {
  const { state, app, events, captured, pointer } = mountGallery();
  state.pointerdown(pointer(200));
  assert.equal(captured.has(1), true);
  state.pointermove(pointer(195));
  assert.equal(state.dragging.value, false);
  state.pointermove(pointer(100));
  assert.equal(state.dragging.value, true);
  state.pointerup(pointer(100));
  assert.equal(state.index.value, 1);
  assert.equal(state.announcement.value, "تصویر 2 از 3");
  assert.deepEqual(events, [["change", 1]]);
  assert.equal(state.dragX.value, 0);
  assert.equal(captured.size, 0);
  state.pointerdown(pointer(200));
  state.pointermove(pointer(100));
  state.resetGesture();
  state.pointerup(pointer(100));
  assert.equal(state.index.value, 1);
  assert.equal(captured.size, 0);
  app.unmount();
});

test("real touch handlers leave vertical scrolling and multi-touch to browser", () => {
  const { state, app, pointer, captured } = mountGallery();
  state.pointerdown(pointer(100, 100, { pointerType: "touch" }));
  assert.equal(captured.size, 0);
  state.pointermove(pointer(105, 180, { pointerType: "touch" }));
  state.pointerup(pointer(0, 180, { pointerType: "touch" }));
  assert.equal(state.index.value, 0);
  state.pointerdown(pointer(100));
  state.pointerdown(
    pointer(100, 0, { pointerId: 2, isPrimary: false, pointerType: "touch" }),
  );
  assert.equal(captured.size, 0);
  state.pointerup(pointer(0));
  assert.equal(state.index.value, 0);
  app.unmount();
});

test("real RTL keyboard and drag agree, and removed active images reset safely", async () => {
  const { state, app, props, pointer } = mountGallery("rtl");
  let prevented = false;
  state.keydown({
    key: "ArrowLeft",
    preventDefault() {
      prevented = true;
    },
  });
  assert.equal(prevented, true);
  assert.equal(state.index.value, 1);
  state.pointerdown(pointer(100));
  state.pointermove(pointer(200));
  state.pointerup(pointer(200));
  assert.equal(state.index.value, 2);
  state.imageError("/three.webp");
  props.images = [{ src: "/one.webp" }];
  await Vue.nextTick();
  assert.equal(state.index.value, 0);
  assert.equal(state.failed.value.size, 0);
  app.unmount();
});
