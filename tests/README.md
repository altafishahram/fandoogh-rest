# Automated tests

Run the automated unit suite from the repository root:

```bash
phpunit --configuration phpunit.xml.dist
```

The current suite uses a small WordPress/WooCommerce test double. It verifies the plugin's own permission decisions and branch-inventory persistence rules without pretending to boot a live WordPress installation.

Coverage currently includes:

- Branch inventory insert and partial updates.
- Price and stock updates preserving unrelated branch fields.
- Branch isolation for the same product.
- Inventory update action dispatch.
- Kitchen staff, restaurant manager, shop manager, and administrator permission boundaries.
- Static guardrails for global modal and theme-toggle IDs emitted by the Elementor food-menu widget.
- Real Elementor rendering of two Food Menu widget instances on one WordPress page, including per-instance theme controls and absence of duplicate global IDs.

These are unit/regression tests, not a substitute for end-to-end testing against a real WordPress + WooCommerce + Elementor installation. Real HPOS/legacy order storage, Elementor editor rendering, and front-end interaction tests must be run in a staging site before claiming full integration compatibility.
