# 09 — Public Course Detail Page

Read `context/AGENTS.md` and all context files before starting.
Depends on: 06-module-lesson-tree, 08-public-course-catalog.

## Goal
A single course page: description, full curriculum outline (module/lesson
titles only, no content or playback), instructor info, and a CTA area. No
purchase flow yet — the CTA is a placeholder that later specs (10+) will wire
up.

## Design decisions
- Same pattern as spec 08: a dynamic block (`ghost-lms/course-detail`) plus a
  `template_include` filter so the native `glms_course` single permalink works
  automatically, while still being usable as an insertable block elsewhere.
- Curriculum outline shows **titles only** — module names as accordion headers,
  lesson titles listed underneath. No lock/unlock icons and no "preview" links
  at this stage: enrollment/access-gating doesn't exist until Phase 3–4, so
  every lesson is presented identically regardless of viewer. Don't
  anticipate access states here — that's explicitly out of scope for this unit.
- CTA area: reads the (not-yet-existing) `_glms_woo_product_id` meta key. If
  absent, render a disabled "Coming soon" button. This unit does **not**
  implement purchase logic — it only builds the conditional slot so spec 10
  can swap in a real "Enroll"/"Add to cart" button without touching this
  template again.
- Instructor bio pulls from the course's post author (`display_name` + user
  `description`) — no new "instructor profile" data structure for v1.

## Implementation
1. `src/Blocks/CourseDetailBlock.php` — dynamic block, `render_callback` uses
   the current post (on the single template) or a `course_id` block attribute
   (when inserted standalone into another page).
2. Render sections, in order: hero (featured image, title, short description
   from post excerpt), curriculum outline (via
   `CurriculumRepository::get()` from spec 06 — reuse it, don't re-query
   postmeta directly), instructor bio, CTA area with the conditional slot
   described above.
3. `template_include` filter for `is_singular('glms_course')`, same mechanism
   as spec 08's archive handling.
4. Reuses the `.glms-root` tokens and card visual language already established
   in spec 08 for consistency between catalog and detail pages.

## Verification checklist
- [ ] Visiting a published course's permalink renders title, description, full curriculum outline in the correct saved order, and instructor info.
- [ ] Curriculum outline exactly matches the order saved in the builder (spec 06) — no independent ordering logic re-derived here.
- [ ] CTA area shows a disabled "Coming soon" state for every course right now (since no course has a linked product yet) and is structured so a later spec can drop in a real button without restructuring the template.
- [ ] Draft/unpublished courses 404 for logged-out and non-owner users (confirm default WordPress behavior isn't accidentally overridden by the `template_include` filter).
- [ ] No lesson content, video, or attachment info is exposed on this page — titles only.