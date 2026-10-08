import { test } from "node:test";
import assert from "node:assert/strict";
import {
  canOrder,
  filterProducts,
  orderItems,
  reorder,
  sortCategory,
  cartRemovals,
} from "../src/domain.js";
test("pure-menu table suppresses every channel while general menu retains online ordering", () => {
  const ordering = { dine_in: true, pickup: true, delivery: true };
  for (const channel of ["table", "pickup", "delivery"])
    assert.equal(
      canOrder(
        { ordering, context: { mode: "menu", can_order: false } },
        {},
        channel,
      ),
      false,
    );
  assert.equal(canOrder({ ordering, context: null }, {}, "pickup"), true);
  assert.equal(canOrder({ ordering, context: null }, {}, "delivery"), true);
});
test("cart reconciliation removes absent menu parents and variants but preserves unrelated store products", () => {
  const products = [
    { id: 1, variations: [] },
    { id: 2, variations: [{ id: 21 }, { id: 22 }] },
  ];
  const server = [
    { id: 1, key: "coffee" },
    { id: 21, key: "small" },
    { id: 22, key: "large" },
    { id: 99, key: "unrelated" },
  ];
  const local = [{ product_id: 2, variation_id: 22, quantity: 1 }];
  assert.deepEqual(cartRemovals(server, local, products), ["coffee", "small"]);
  assert.deepEqual(cartRemovals(server, [], products), [
    "coffee",
    "small",
    "large",
  ]);
});
test("category-name search intersects active category and includes matching products without name match", () => {
  const products = [
    { id: 1, name: "لاته", description: "شیر", category_ids: [2] },
    { id: 2, name: "کیک", description: "وانیل", category_ids: [3] },
  ];
  const categories = [
    { id: 2, name: "نوشیدنی گرم" },
    { id: 3, name: "دسر" },
  ];
  assert.deepEqual(
    filterProducts(products, 0, "گرم", categories).map((p) => p.id),
    [1],
  );
  assert.deepEqual(filterProducts(products, 3, "گرم", categories), []);
});
test("category ordering preserves unknown products and never changes source list", () => {
  const products = [{ id: 1 }, { id: 2 }, { id: 3 }];
  assert.deepEqual(
    sortCategory(products, [3, 1]).map((p) => p.id),
    [3, 1, 2],
  );
  assert.deepEqual(
    products.map((p) => p.id),
    [1, 2, 3],
  );
});
test("table authorization and pause are authoritative", () => {
  const b = { ordering: { dine_in: true }, context: { can_order: false } };
  assert.equal(canOrder(b, {}, "table"), false);
  b.context.can_order = true;
  assert.equal(canOrder(b, {}, "table"), true);
  b.ordering.paused = true;
  assert.equal(canOrder(b, {}, "table"), false);
  assert.equal(
    canOrder(
      { ...b, ordering: { pickup: true } },
      { viewMode: "menu" },
      "pickup",
    ),
    false,
  );
});
test("order payload never sends prices or totals", () =>
  assert.deepEqual(
    orderItems([{ product_id: 4, variation_id: 9, quantity: 2, price: 999 }]),
    [{ product_id: 4, variation_id: 9, quantity: 2 }],
  ));
test("Persian search and category intersect", () =>
  assert.equal(
    filterProducts(
      [{ name: "لاته", description: "قهوه", category_ids: [2] }],
      2,
      "قهوه",
    ).length,
    1,
  ));
test("reorder preserves all identifiers", () =>
  assert.deepEqual(reorder([1, 2, 3], 0, 2), [2, 3, 1]));
