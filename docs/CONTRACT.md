# Fandoogh Rest implementation contract

This file coordinates module ownership. All PHP is namespaced `FandooghRest`, PSR-4 under `src/`. Minimum PHP 8.2; WordPress 6.5; WooCommerce 9.0. No food/order CPT: WooCommerce is the only product/order store, via CRUD APIs, compatible with HPOS.

## Module boundaries

PHP modules own domain behavior; Core/Plugin.php composes them. Frontend presentation belongs in frontend/, and shared contracts remain versioned here. Coordinate changes to API shapes, persisted identifiers and checkout behavior across the affected modules.

Register module hooks via public `register(): void`. REST namespace `admincafe/v1`; WordPress JSON errors. PHP root constants FANDOOGH_REST_VERSION, FANDOOGH_REST_FILE, FANDOOGH_REST_PATH, FANDOOGH_REST_URL. All admin routes require login, X-WP-Nonce and fine-grained capabilities. No WC consumer secrets in browser. Public POST requires same-origin validation, WC session / guest CSRF token from fresh bootstrap, throttling, idempotency for ordering.

## Shared Core APIs

- `Core\Settings::defaults(): array`, `::all(): array`, `::get(string $key, mixed $default=null): mixed`, `::update(array $input): array|\WP_Error`, `::publicSettings(): array`, `::menuUrl(): string`, `::panelUrl(): string`.
- Settings keys: restaurant_name, tagline, logo_id, cover_id, menu_slug (`menu`), panel_slug (`cafe-panel`), menu_page_id (0 = standalone), dine_in_enabled (false), pickup_enabled (false), delivery_enabled (false), ordering_paused (false), table_default_mode (`menu` or `order`), accent (`#c87545`), category_background (`#f3ede5`), background (`#faf8f5`), font_family (`Vazirmatn`), custom_font_url, currency_label (empty = WC label), title_size_mobile (18), title_size_desktop (22), description_size_mobile (13), description_size_desktop (14), price_size_mobile (16), price_size_desktop (18), title_weight (700), description_weight (400), price_weight (700), layout (`grid` or `list`), restaurant_phone, restaurant_address, hours_text, preparation_minutes (20), notification_sound (true), messages {unavailable, closed, order_received}, menu_category_ids (empty = all), qr_color (`#242424`), qr_size (512).
- `currency_code` follows the real WooCommerce currency option and may be updated through validated settings; Core/Currency adds `IRT` (Toman). No currency change converts monetary numbers. Management bootstrap includes `currencies:[{code,name}]`.
- `Core\Access` registers `admincafe_manager`, `admincafe_staff`, `admincafe_kitchen`, `admincafe_cashier` roles and caps: `admincafe_manage_menu`, `admincafe_manage_orders`, `admincafe_manage_settings`, `admincafe_manage_tables`, `admincafe_view_reports`, `admincafe_manage_staff`, `admincafe_receive_notifications`. `::can(string $cap): bool`. Managers/admin have all; staff orders/tables/notifications; kitchen orders/notifications; cashier orders/reports/notifications.
- `Core\Security::publicPermission(\WP_REST_Request $request): bool|\WP_Error`, `::throttle(string $bucket,int $limit,int $seconds): bool|\WP_Error` implemented by root. Public writes send X-AdminCafe-Token (session token supplied by bootstrap).

## Menu and management APIs

- `Menu\Catalog::menu(): array` => `{products:[], categories:[]}`. Product public schema: id, type, name, description (plain text), short_description (plain text), price (decimal string), regular_price, sale_price, currency, currency_symbol, image (URL), image_id, available (bool), sku, category_ids[], order (int), variations[] {id,name,price,available,attributes}. Product metadata `_admincafe_visible` defaults yes. Category: id,name,parent,image,icon,order. Only published visible products in selected categories.
- `::saveProduct(array $input,int $id=0): array|WP_Error`; `::product(int $id): array|WP_Error`; saves WC CRUD, supported simple products and existing variations; menus include variable choices.
- `Tables\Tables::all(): array`, `::findByToken(string $token): ?array`, `::context(string $token): array|WP_Error` => `{id,label,token,mode,can_order}`. Tokens random opaque, stable QR URL `/cafe-qr/{token}`. Table schema id,label,mode (`inherit/menu/order`),enabled,token,url. Store option `admincafe_tables` (no customer data).
- GET /manage/bootstrap => {settings,capabilities,user:{id,name},menu_url,panel_url,pages:[{id,title,url}],checkout_url,currency_symbol,push:{public_key,available,reason},roles:[]}. Root may extend push info via filter `admincafe_management_bootstrap`.
- GET/POST /manage/settings => full settings
- GET/POST /manage/products ; GET/PATCH/DELETE /manage/products/{id} ; GET/POST /manage/categories ; PATCH/DELETE /manage/categories/{id}
- POST /manage/reorder => {category_id,ids:[]}; category-specific order stored term meta `_admincafe_product_order` (map/list). Preserve order in menu category frontend.
- GET/POST /manage/tables ; PATCH/DELETE /manage/tables/{id}
- GET /manage/reports => {revenue,order_count,pending_count,top_products:[],daily:[],currency_symbol} filter days (default 7), only AdminCafe orders; paid revenue excludes refunded amounts, WC query APIs.
- GET/POST /manage/staff ; PATCH/DELETE /manage/staff/{id}; manager users can only assign AdminCafe operational roles and cannot modify admins/themselves critically; POST /manage/media multipart file => {id,url} with size/mime validation.

## Commerce APIs

- GET /bootstrap?table={token} => {settings (public),products,categories,context (null or table),ordering:{dine_in,pickup,delivery,paused},csrf_token,menu_url,checkout_url,currency_symbol,wc_store_api_url,wc_nonce}. No cache. Root handles through Ordering or companion.
- POST /orders/table => {table_token,items:[{product_id,variation_id?,quantity}],name?,note?,request_id}. Reply {id,number,stage,total,currency_symbol,tracking_token}. Table/global server switches are authoritative. Resolve prices on server; validate products/variants/stock; no client totals. Status `on-hold` (offline settlement); `_admincafe_stage=awaiting_approval`; `_admincafe_channel=table`; table token/label metadata. Staff approval must precede preparation. Stage separate from financial status.
- POST /checkout => {branch_id:int,channel:`pickup`|`delivery`,replace_cart?:bool} persists channel in WC session (do not take totals), returns {url}; customer's Woo cart is filled through WC Store API by frontend. Woo checkout calculates delivery/taxes/payment. Delivery disabled cannot bypass via Store API/classic checkout. Pickup must disable shipping only for this cart context, not mark products globally virtual. UI routes to native WC checkout for gateway compatibility.
- GET /orders/track?token=... => limited {number,stage,payment_status}, no names/phone/address; hashed tracking token.
- GET /manage/orders?stage=&channel=&page= => {orders:[],total,pages}; GET /manage/orders/{id}; POST /manage/orders (manual cashier order, channel `counter`, items/name/phone/note)
- PATCH /manage/orders/{id} => {stage?:`awaiting_approval|accepted|preparing|ready|delivered|cancelled`,payment_action?:`settle|refund`}. Valid stage transitions. Refund requires manager capability, paid cancellation doesn't silently claim refund; use WC refunds. Kitchen can't settle/refund.
- Woo orders carry `_admincafe_channel`, `_admincafe_stage`; Woo financial status independent. Woo online order only created event after real checkout submission (not draft). Optional payment events distinguish new vs paid.
- New order fires `do_action('admincafe_order_created',$order_id)` after persisted order. Stage updates fire `do_action('admincafe_order_stage_changed',$order_id,$stage)`. Notification dedupe also implemented root.

## Root APIs: Import / Notifications

- POST /manage/import/preview multipart `file` CSV/XLSX => {token,columns:[],rows:[],total}; POST /manage/import/apply {token,mapping:{name,description,price,sku,category,status},mode:`upsert`|`create`} => {created,updated,skipped,errors:[]}. File bounded (5MB/2000 rows), formulas ignored, safe XML. Empty/unavailable price marks out of stock and preserves existing price. Zero not blank. Map by SKU first. Preview expires and is bound to both user and branch.
- GET /manage/notifications?after=event_id => {events:[],unread,push:{public_key,available,reason}}. Event {id,order_id,title,body,channel,created_at,read}. POST /manage/notifications/read {ids:[]}.
- PATCH /manage/push/devices/{id} => {channels:[]} updates channel preferences only on a device owned by the current authenticated user; explicit empty means no channels.
- POST /manage/push/subscribe {subscription:{endpoint,keys:{p256dh,auth}},label,channels:[]} ; DELETE /manage/push/subscribe {endpoint}; GET /manage/push/devices; POST /manage/push/test. WebPush library bundled (PHP 8.2+ ext openssl,curl,mbstring). HTTPS+user gesture. Restrict push endpoint hosts to known browser push services; no arbitrary SSRF. Per-user device subscriptions and cap validation at delivery. Action Scheduler queue retries; notification list persists if push unavailable. Manifest/SW only scoped to panel and never caches API or customer data.

## Appearance (1.3.0)

General settings add `menu_theme` (`cafe`, `minimal`, `midnight`, `garden`, `bistro`), `custom_css_enabled` (boolean, default true) and `custom_css` (map with `general`, `card`, `detail`, `language`, `categories`, `buttons`, `cart` string fields). Missing fields preserve stored drafts; unknown fields/types, unsupported CSS or byte limits fail atomically. General CSS contains relative selector rules; other fields contain declarations only.

Customer settings exclude editable `custom_css`, returning compiled `menu_custom_css`. Initial `Assets::config()` includes an `appearance` whitelist for the language gate before product bootstrap. Compiled customer selectors are scoped to `.admincafe-root[data-admincafe-app="menu"][data-fandoogh-branch="ID"] .ac-menu`; the preview scope is `.admincafe-root .ac-menu.ac-appearance-preview` inside an isolated same-origin iframe. The frontend uses one shared token helper for palette/font/typography and applies the selected preset to a menu theme attribute.

`POST /manage/appearance/preview` requires `admincafe_manage_settings` and `X-WP-Nonce`. Input is `{menu_theme, custom_css_enabled, custom_css}` and output is `{menu_theme, custom_css_enabled, menu_custom_css}`. It validates draft CSS and returns only preview-scoped rules without changing persisted settings or Woo currency. Editor input is never directly injected into the page. Only server-compiled style text is rendered, with HTML/resource/escaped CSS rejected by the compiler. See `docs/APPEARANCE.md` for the supported grammar.

## Automatic content translation (1.2.0)

All `/manage/translation/*` routes require `admincafe_manage_settings` and a valid `X-WP-Nonce`. Configuration is separate from general restaurant settings and public bootstrap responses.

- `GET /manage/translation/settings`: redacted `{enabled,configured,provider,credential_source,daily_character_limit,target_languages,version}`. Credential source is `none|stored|constant`; no key or key suffix is returned.
- `POST /manage/translation/settings`: strict optional `{enabled:bool,daily_character_limit:int(1..10000000),target_languages:[en|zh|tr],api_key:string,remove_key:bool}`. Blank/omitted key preserves the credential. Removing a key requires disabling the service; the UI submits both changes together. The server constant takes precedence and cannot be replaced through this endpoint.
- `GET /manage/translation/status`: `{counts:{queued,running,completed,failed,skipped},jobs:[],problems:[],usage:{date,characters,limit},settings:{...redacted}}`. Safe errors identify configuration, temporary failure, daily quota or content-length issues; raw provider messages are discarded.
- `POST /manage/translation/run {}`: starts a bounded paginated scan of published menu products/variations, selected categories and restaurant display text; returns `{queued,scan_queued}` immediately.
- `POST /manage/translation/retry {ids?:int[]}`: retries at most 100 failed jobs per call; stale source/configuration receives a fresh job identity. Omitting IDs processes a bounded batch.

The provider interface is `Translation/Provider::translate(array $texts,string $target): array|WP_Error`; implementations return results in source order. The `admincafe_translation_provider` filter replaces the default adapter for extensions/tests. `admincafe_translation_manual_input(scope,id,patch)` records protected manual intent after validated product/category/settings writes. Public menu/cart/checkout read the existing locale maps; they never invoke the translation service. Job/usage timestamps use UTC.

## Frontend rendering

PHP outputs `<div class="admincafe-root" data-admincafe-app="menu|panel" data-admincafe-config="{escaped JSON}"></div>`. Config {apiBase,nonce,menuUrl,panelUrl,assetUrl,branchId,branchSlug,branchName,tableToken,component:`menu|categories|products|cart`,categoryId?,viewMode?:`auto|menu`,locale:`fa-IR`,demo?:bool}. Mount roots with the same API, branch and table share menu/cart state; different branches or tables are isolated. GET /bootstrap fresh guest tokens; REST management X-WP-Nonce. Built entries `assets/menu.js`, `assets/panel.js`, `assets/fandoogh-rest.css`; local font assets (Vazirmatn if available license bundled). CSS scoped to .admincafe-root, no global Tailwind reset pollution. Bundle QR generator for browser SVG/PNG download. Builder shortcode blocks and Elementor widget wrapper same app entry. Public app persisted table selection localStorage, Woo cart server-backed through Store API; no API consumer keys. Demo preview with explicit mock API adapter, production never fabricates orders.

Default module security: escape PHP output, validate and whitelist REST input, sanitized image uploads only; no external image sideloading; capabilities always server-side; guest rate-limit shared-IP-conscious; guests cannot access management endpoints; idempotency tokens cannot be replayed across tables/sessions. Inline prices and drag-drop call real management endpoints. Accessibility: RTL, keyboard menus/dialogs, focus restoration, descriptive labels, empty/loading/error states.

## Branding compatibility and branches

Version 1.3.1 uses the FandooghRest namespace, fandoogh-rest text domain and fandoogh-rest.php entry point. Existing lowercase admincafe identifiers remain compatibility contracts for persisted data, routes, roles, callbacks, CSS and saved integrations. Legacy PHP namespaces/constants and shortcodes retain compatibility bridges. See docs/RELEASE-1.3.1.md for branding migration and docs/MULTIBRANCH-PLAN.md for the 1.4.0 implementation and limits.


## Multi-branch context (1.4.0)

`Branches\Branches::current()` is request-local. `::runFor(int,callable)` restores the previous context even on exceptions. REST branch selection accepts a canonical positive `branch_id` in query/JSON or `X-Fandoogh-Branch`; conflicting selections fail. Management requires both branch membership and the existing capability/nonce. Public QR requests derive ownership from the stored table, not customer labels. Nested REST calls restore their caller's context.

- Product/category/media/order metadata: `_fandoogh_branch_id`; variations inherit their parent. Unassigned legacy records belong to branch 1. New objects are stamped. Product queries filter ownership before pagination.
- Branch registry: private option `fandoogh_branches`; user assignments: `_fandoogh_branch_ids`. Only `manage_options` users may create/rename/disable branches or broaden assignments. Disabled branches reject public menus/new orders while retaining authenticated history.
- `GET/POST /manage/branches`, `PATCH /manage/branches/{id}`: creation/update input `{name,slug,enabled,phone?,address?}`. Public summary `{id,name,slug,enabled,menu_url,phone,address}`. No deletion API.
- Management bootstrap adds `branch_id`, assigned `branches` and `can_manage_branches`. Branch staff may update local settings; shared `menu_slug`, `panel_slug`, `currency_code` and provider configuration require central management.
- `[fandoogh_rest_branches]` lists enabled branches. Menu builder components accept `branch` as ID or slug. Default `/menu/` remains valid; additional routes use `/menu/{slug}/`. Per-branch builder destinations use `branch_id` on their page URL.
- Native Woo cart items/session and checkout drafts persist branch identity. Classic/Store API operations revalidate ownership. `/checkout` returns `cart_branch_conflict` (409) for a populated foreign cart; only explicit `replace_cart:true` clears it. Draft ownership is checked before Store API hydrates lines. Existing orders keep immutable branch name/address snapshots.
- Tables, orders, reports, events, read acknowledgments, import tokens and translation jobs enforce branch boundaries. Push recipients are checked at enqueue and delivery. Provider credentials/quota, payment gateways, native tax/shipping configuration and currency remain shared.

Upgrade refreshes rewrites and preserves legacy IDs/tokens/settings. A downgrade after multi-branch writes requires restoring a matching database backup; older code does not enforce branch isolation.
