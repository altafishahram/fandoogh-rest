import "@fontsource/vazirmatn/arabic-400.css";
import "@fontsource/vazirmatn/arabic-700.css";
import { createApp } from "vue";
import Panel from "./Panel.vue";
import "./style.css";
for (const root of document.querySelectorAll('[data-admincafe-app="panel"]')) {
  const config = JSON.parse(root.dataset.admincafeConfig || "{}");
  createApp(Panel, { config, mountRoot: root }).mount(root);
}
