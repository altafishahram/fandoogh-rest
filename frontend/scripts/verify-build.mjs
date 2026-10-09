import assert from "node:assert/strict";
import { readFile, readdir, access } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import { resolve, sep } from "node:path";

// WordPress enqueues one stylesheet for both Vue entry points.
const assets = fileURLToPath(new URL("../../assets/", import.meta.url));
const files = await readdir(assets);
assert.deepEqual(
  files.filter((name) => name.endsWith(".css")),
  ["fandoogh-rest.css"],
);
const css = await readFile(resolve(assets, "fandoogh-rest.css"), "utf8");
for (const selector of [".ac-menu", ".ac-panel", ".food-gallery"]) {
  assert.ok(css.includes(selector), `Missing bundled styles: ${selector}`);
}
let fonts = 0;
for (const match of css.matchAll(/url\(([^)]+)\)/g)) {
  const url = match[1].replace(/^["']|["']$/g, "").trim();
  if (url.startsWith("data:")) continue;
  assert.ok(
    !/^(?:\/|[a-z]+:)/i.test(url),
    `Asset must be plugin-relative: ${url}`,
  );
  const path = resolve(assets, decodeURIComponent(url.split(/[?#]/)[0]));
  assert.ok(
    path.startsWith(assets.endsWith(sep) ? assets : assets + sep),
    `Asset escapes bundle: ${url}`,
  );
  await access(path);
  if (/\.woff2?$/.test(path)) fonts++;
}
assert.ok(fonts > 0, "Missing bundled fonts");
for (const entry of ["menu.js", "panel.js"])
  await access(resolve(assets, entry));
console.log("Menu/panel CSS and plugin-relative font assets verified.");
