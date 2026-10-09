import "@fontsource/vazirmatn/arabic-400.css";
import "@fontsource/vazirmatn/arabic-700.css";
import { createApp } from "vue";
import Menu from "./Menu.vue";
import "./style.css";
import "./menu-design.css";
for (const root of document.querySelectorAll('[data-admincafe-app="menu"]')) {
  const config = JSON.parse(root.dataset.admincafeConfig || "{}");
  createApp(Menu, { config, mountRoot: root }).mount(root);
}
