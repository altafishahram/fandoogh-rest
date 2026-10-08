export function money(value, symbol = "تومان") {
  return `${new Intl.NumberFormat("fa-IR", { maximumFractionDigits: 2 }).format(Number(value) || 0)} ${symbol}`;
}
export function sortCategory(products, ids = []) {
  const rank = new Map(ids.map((id, index) => [Number(id), index]));
  return [...products].sort(
    (a, b) => (rank.get(a.id) ?? Infinity) - (rank.get(b.id) ?? Infinity),
  );
}
export function filterProducts(
  products,
  category,
  search = "",
  categories = [],
) {
  const q = search.trim().toLocaleLowerCase("fa");
  const names = new Map(categories.map((c) => [Number(c.id), c.name || ""]));
  return products.filter(
    (p) =>
      (!category || p.category_ids?.includes(Number(category))) &&
      (!q ||
        `${p.name} ${p.description} ${(p.category_ids || []).map((id) => names.get(Number(id)) || "").join(" ")}`
          .toLocaleLowerCase("fa")
          .includes(q)),
  );
}
export function orderItems(cart) {
  return cart.map((i) => ({
    product_id: i.product_id,
    ...(i.variation_id ? { variation_id: i.variation_id } : {}),
    quantity: i.quantity,
  }));
}
export function canOrder(bootstrap, config, channel) {
  return (
    config.viewMode !== "menu" &&
    bootstrap.context?.mode !== "menu" &&
    !bootstrap.ordering?.paused &&
    (channel === "table"
      ? !!bootstrap.context?.can_order && bootstrap.ordering.dine_in
      : !!bootstrap.ordering?.[channel])
  );
}
export function cartRemovals(serverItems, localItems, menuProducts) {
  const wanted = new Set(
    localItems.map((i) => Number(i.variation_id || i.product_id)),
  );
  const menuIds = new Set(
    menuProducts.flatMap((p) => [
      Number(p.id),
      ...(p.variations || []).map((v) => Number(v.id)),
    ]),
  );
  return serverItems
    .filter((i) => menuIds.has(Number(i.id)) && !wanted.has(Number(i.id)))
    .map((i) => i.key);
}
export function reorder(ids, from, to) {
  const copy = [...ids];
  const [item] = copy.splice(from, 1);
  copy.splice(to, 0, item);
  return copy;
}
