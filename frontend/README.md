# Fandoogh Rest UI

Vue 3 SFCs, Vite and scoped, prefixed Tailwind utilities. All handwritten styles are restricted to `.admincafe-root`; Tailwind preflight is disabled. Vazirmatn Arabic font files are bundled locally under the SIL Open Font License (`FONT-LICENSE.txt`).

Run `npm ci`, `npm test`, `npm run build`. Build creates `../assets/menu.js`, `panel.js`, `fandoogh-rest.css`, shared chunks and local fonts. `npm run dev` serves the explicit demo menu at `/` and demo management at `/panel.html`. Demo data is activated only by boolean `config.demo === true`; production failures are always surfaced.

Entry files only mount components. `Menu.vue` / `useMenu.js` handle the public menu and table workflow. `Panel.vue` / `usePanel.js` coordinate management, with page SFCs in `pages/`. `api.js` is the REST boundary; `domain.js` holds independently tested rules. `state.js` shares category/cart state between builder roots, with table-specific cart storage. `i18n.js` lists Persian source messages and reads translations from `config.strings`.

Pickup/delivery select the server checkout channel, synchronize quantities through the Woo Store API, then redirect to native checkout. Payment gateways remain entirely in WooCommerce. Table requests reuse their request ID after network failure and are prepared only after staff approval. Management polling is limited to notification and order refresh; the panel-scoped service worker never caches requests or customer data. Browser subscription starts only from an explicit user click.

Real API success paths were checked against the disposable WordPress/WooCommerce HPOS site, alongside 58 integration checks; see `../docs/VALIDATION.md`. Native gateway/payment, HTTPS Web Push and the target site's database/theme/plugin combination still require staging verification. The explicit demo alone does not verify payment processing or push delivery.
