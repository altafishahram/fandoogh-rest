import vue from "@vitejs/plugin-vue";
import { defineConfig } from "vite";
import { resolve } from "node:path";
export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: "../assets",
    emptyOutDir: true,
    rollupOptions: {
      input: { menu: resolve("src/menu.js"), panel: resolve("src/panel.js") },
      output: {
        entryFileNames: "[name].js",
        chunkFileNames: "chunks/[name]-[hash].js",
        assetFileNames: (a) =>
          a.name?.endsWith(".css") ? "admincafe.css" : "[name]-[hash][extname]",
      },
    },
  },
});
