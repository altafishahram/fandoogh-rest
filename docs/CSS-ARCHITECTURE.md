# CSS Architecture

## Stylesheet responsibilities

- `frontend-menu-base.css`: shared storefront foundations, menu layout, cards and modal primitives.
- `frontend-menu-widgets.css`: secondary storefront widgets and standalone controls.
- `frontend-menu-modern.css`: the single supported Modern menu presentation, catalog presentation and shared storefront-specific overrides.
- `category-sheet.css`: isolated category-sheet component; loaded after the storefront base handle.
- `portal-pwa.css`: standalone management PWA. It intentionally keeps its own `--rmm-*` token namespace rather than inheriting storefront theme colors.
- `TokenSchema.php` and `CssCompiler.php`: source of truth for editable Design System tokens and generated component mappings.

The three storefront layers are enqueued in dependency order. The public `rmm-frontend-menu` style handle remains the final Modern presentation layer, so existing widgets, third-party integrations, and `wp_add_inline_style()` calls retain their original handle and output behavior. The Elementor menu widget exposes only the Modern Grid preset; legacy saved preset values render through Modern Grid. The generated Design System stylesheet is loaded after that handle; when the generated file is unavailable, its fallback CSS is attached to the same final layer.

## Authoring rules

1. Keep selectors inside the plugin namespace (`.rmm-*`, `body.rmm-*`, or the component's explicit theme root). Do not style generic WooCommerce, Elementor, or document selectors.
2. Use `--rmm-ds-*` tokens for storefront components and the existing `--rmm-*` PWA tokens for the management portal. Add editable tokens through `TokenSchema.php` and their component mappings through `CssCompiler.php`.
3. Put static presentation in CSS classes, not HTML `style` attributes. Inline styles are reserved for state-driven visibility or genuinely computed runtime values.
4. Keep component styles in their owning stylesheet and preserve dependency order when adding a new layer.
5. Avoid adding `!important`. Retain existing declarations only where a documented third-party cascade conflict requires them; reduce legacy usage in small, tested changes rather than deleting overrides wholesale.
6. Add responsive rules at the component boundary and verify narrow screens, tablet widths, and desktop layouts before changing layout behavior.

## Regression protection

`CssArchitectureTest.php` checks stylesheet registration/order, static inline-style removal, extracted class coverage, CSS block balance, token usage, and existing `!important` budgets. The budget checks prevent new overrides from increasing the legacy debt while allowing it to be reduced incrementally.
