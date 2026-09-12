# 12 — "My Courses" Dashboard

Read `context/AGENTS.md` and all context files before starting.
Depends on: 08-public-course-catalog, 11-enrollment-on-purchase.

## Goal
A student-facing page listing their actively enrolled courses. Progress display
is a placeholder in this unit — real per-lesson progress doesn't exist until
Phase 4 — but the seam for it is built now so nothing here needs rewriting
later.

## Design decisions
- Two surfaces, one source of truth: a `ghost-lms/my-courses` dynamic block
  (droppable on any page, same pattern as the catalog block) **and** a "My
  Courses" tab registered inside WooCommerce's native My Account menu — most
  WooCommerce-integrated students will expect their courses there. Both render
  through the same underlying function so there's exactly one markup path to
  maintain.
- **Progress placeholder, explicit seam**: implement
  `src/Access/ProgressStub.php` with `get_course_progress_percent(int $user_id,
  int $course_id): int` that always returns `0` right now, clearly docblocked
  as `@todo replace with real calculation in the lesson-progress spec
  (Phase 4)`. The dashboard UI calls this function, not a hardcoded `0` inline
  — so Phase 4 only has to swap the function's internals, not touch this
  template.
- Only **active** enrollments are listed in v1 — a revoked/refunded course
  disappears from the dashboard rather than showing in a grayed-out "past
  access" section. Simpler for now; note the possible future improvement in
  the progress tracker rather than building it speculatively.
- "Continue" button on each course card links to the course detail page (spec
  09) for now, since the lesson player doesn't exist until Phase 4 — this is a
  known, temporary link target, not a placeholder to leave broken.
- Logged-out visitors are redirected to login on both surfaces (standard
  WordPress/WooCommerce account-required behavior), not shown an empty
  dashboard.

## Implementation
1. `src/Access/ProgressStub.php` — the stub function described above.
2. `src/Blocks/MyCoursesBlock.php` — dynamic block; queries
   `EnrollmentRepository::get_active_for_user( get_current_user_id() )`,
   renders one card per course reusing the catalog card partial/styles from
   spec 08 where it makes sense, with a 0%-filled progress bar (via the stub)
   and a "Continue" button linking to the course detail page.
3. `src/Woo/MyAccountIntegration.php` — registers a "My Courses" endpoint via
   `woocommerce_account_menu_items` + the corresponding rewrite endpoint +
   content callback, calling the same render function as the block.
4. Empty state (no active enrollments): a message plus a "Browse courses"
   button linking to the catalog (spec 08).

## Verification checklist
- [ ] A student with an active enrollment sees that course listed with a 0% progress bar and a working "Continue" button.
- [ ] A student with no enrollments sees the empty state with a working link to the catalog.
- [ ] The same course list renders identically via the block and via WooCommerce → My Account → My Courses.
- [ ] A revoked/refunded enrollment does not appear.
- [ ] Logged-out visitor hitting either surface is redirected to login, not shown an empty dashboard.