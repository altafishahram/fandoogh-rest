export default {
  content: ["./src/**/*.{js,vue}", "./*.html"],
  prefix: "ac-",
  important: ".admincafe-root",
  corePlugins: { preflight: false },
  theme: { extend: { colors: { copper: "#c87545" } } },
  plugins: [],
};
