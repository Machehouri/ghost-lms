# 10 — Course-Product Linking

Read `context/AGENTS.md` and all context files before starting.
Depends on: 02-custom-post-types, 09-public-course-detail.

## Goal
Let an instructor link a course to a WooCommerce product — either an existing
simple product or a newly created one — and have the course detail page (spec
09) show a real price and working "Add to Cart" button instead of "Coming
soon."

## Design decisions
- Two flows, both landing on the same linked state: **link an existing** simple
  product (search-select using WooCommerce's own product search) or **create
  new** (a small form — name prefilled from the course title, price field —
  that creates a `WC_Product_Simple`, sets it `virtual = true` since there's no
  shipping, and publishes it).
- Only `simple` product type is allowed, matching the out-of-scope note in
  `01-project-overview.md` (no bundles/subscriptions in v1) — reject any other
  product type at the linking step, not just silently ignore it.
- **1:1 enforced both ways**: a product already linked to a different course
  cannot be linked again — check for an existing `_glms_course_id` meta value
  collision before allowing the link.
- Store the link bidirectionally: `_glms_woo_product_id` on the course post
  (the meta key spec 09 already anticipated) and `_glms_course_id` on the
  product post — so spec 11's order-processing hook can resolve course-from-
  product without a reverse lookup query.
- Unlinking clears both meta keys but never deletes the underlying WooCommerce
  product — that's a separate, deliberate action outside this plugin's scope.

## Implementation
1. `src/Woo/ProductLinkMetaBox.php` — meta box on the `glms_course` edit
   screen with the two flows (link existing / create new) described above.
2. Link-existing: reuse WooCommerce's built-in product search AJAX
   (`woocommerce_json_search_products`) for the select field; on submit,
   validate product type and collision, then set both meta keys.
3. Create-new: on submit, instantiate `new WC_Product_Simple()`, set
   name/price/`virtual(true)`/status `publish`, save, then set both meta keys
   exactly as the link-existing flow does (single code path for the actual
   linking, two entry points into it).
4. Unlink action: clears `_glms_woo_product_id` and the corresponding
   `_glms_course_id`.
5. Update `src/Blocks/CourseDetailBlock.php` (spec 09) — the CTA slot now
   checks for `_glms_woo_product_id`; if present, render WooCommerce's actual
   price HTML (`wc_price()`) and an "Add to Cart" form
   (`woocommerce_template_loop_add_to_cart` or an equivalent minimal form
   posting to the standard WC add-to-cart action) instead of the "Coming soon"
   placeholder.

## Verification checklist
- [ ] Instructor can link an existing simple WooCommerce product to a course from the course edit screen.
- [ ] Instructor can create a new simple product with prefilled name/price directly from the course edit screen.
- [ ] Linking a product already linked to a different course is blocked with a clear admin-facing error.
- [ ] Linking a non-simple product type (variable, grouped, etc.) is blocked.
- [ ] Course detail page now shows real price and a working "Add to Cart" button for any linked course — verify the item actually adds to the WooCommerce cart.
- [ ] Unlinking clears both meta keys without deleting the WooCommerce product.