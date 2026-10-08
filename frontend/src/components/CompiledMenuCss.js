import { h } from "vue";
// Vue text children use textContent: the CSS can never become HTML markup.
export default {
  props: { css: { type: String, default: "" } },
  setup: (props) => () =>
    props.css
      ? h("style", { "data-admincafe-custom-css": "" }, props.css)
      : null,
};
