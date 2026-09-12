# 08 — Public Course Catalog

Read `context/AGENTS.md` and all context files before starting.
Depends on: 02-custom-post-types.

## Goal
A public-facing catalog listing published courses as cards, filterable by
category, usable both as the automatic `glms_course` archive and as a block
droppable into any page. No purchase or enrollment logic yet — this is browsing
only.

## Design decisions
- Implemented as a **dynamic Gutenberg block** (`ghost-lms/course-catalog`, PHP
  `render_callback`) rather than only a hardcoded archive template — a site
  owner can then drop the catalog into any page, not just the CPT's default
  archive URL, matching the theme-agnostic approach in `05-ui-context.md`.
- A `template_include` filter still makes `/courses/` (the default
  `glms_course` archive URL) work out of the box by rendering the same block
  output, so nobody's forced into the block editor just to get a working
  catalog page.
- Category filtering uses a plain URL query var (`?glms_category={slug}`) and a
  standard `tax_query` — no JS required for base functionality, which keeps it
  compatible with page caching. An optional JS enhancement for instant
  client-side filtering can be added later; it is **not** required for this
  unit's "done."
- Course cards show price pulled from the linked WooCommerce product — but that
  link doesn't exist until spec 10. For this unit, any course without a linked
  product shows a "Coming soon" badge instead of a price, and the card
  structure is built so spec 10+ can drop real pricing in without restructuring
  the template.

## Implementation
1. `src/Blocks/CourseCatalogBlock.php` — registers the block (`block.json` +
   PHP `render_callback`), queries published `glms_course` posts
   (`posts_per_page` as a block attribute, default 12), applies `tax_query`
   against `glms_course_category` when `glms_category` is present in the URL.
2. `templates/course-catalog/card.php` — the card partial per
   `05-ui-context.md`'s course-card conventions: thumbnail, title, instructor
   display name (post author), excerpt, price-or-"Coming soon" badge. Wrapped
   in `.glms-root`.
3. Category filter rendered as plain `<a href="?glms_category={slug}">` links
   above the grid — bookmarkable, cacheable, no JS dependency.
4. `template_include` filter: on `is_post_type_archive('glms_course')`, render
   the block's output instead of falling through to the active theme's default
   archive template.
5. Minimal block-editor JS (`edit.js`) exposing just the "posts per page"
   attribute control — the block is primarily server-rendered, not an editor-
   side React app.

## Verification checklist
- [ ] `/courses/` renders a grid of published courses with no visual conflicts, tested against a default theme (e.g. Twenty Twenty-Four).
- [ ] Category filter links correctly narrow the results and are shareable/bookmarkable URLs.
- [ ] Draft/scheduled/private courses never appear in the catalog.
- [ ] A course with no featured image and no linked WooCommerce product still renders cleanly (fallback image, "Coming soon" badge).
- [ ] The block can be inserted into any regular page via the block editor, independent of the automatic archive.