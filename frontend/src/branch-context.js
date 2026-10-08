// A checkout can replace a different branch's server cart only after explicit consent.
export async function branchCheckout(client, payload, confirmReplacement) {
  try {
    return await client.request("/checkout", "POST", payload);
  } catch (error) {
    if (error.code !== "cart_branch_conflict") throw error;
    if (!(await confirmReplacement())) return null;
    return client.request("/checkout", "POST", {
      ...payload,
      replace_cart: true,
    });
  }
}
