# 18 — Drip Rule Builder UI

Read `context/AGENTS.md` and all context files before starting.
Depends on: 17-drip-rule-engine, 06-module-lesson-tree.

## Goal
Let an instructor set, change, or clear a drip rule per lesson from inside the
course builder, and let students see a clear, human-readable reason when a
lesson is locked.

## Design decisions
- Lives inside the existing React course builder (spec 06) — each lesson row
  gets a small drip-status badge; clicking it opens an inline panel to choose
  the rule type (none / fixed date / days after enrollment / prerequisite
  lesson) and its parameters.
- The prerequisite lesson picker is scoped client-side to lessons already
  known to be in the same course (curriculum data the builder already has
  loaded) — this prevents most invalid cross-course selections before they
  even reach the server, though the server-side validator from spec 17
  remains the actual enforcement point.
- Dedicated REST route rather than folding this into the curriculum
  read/write routes from spec 06 — drip rules are lesson-level metadata, not
  part of the module/lesson structure or ordering, so they don't belong in
  the same payload.
- Server-side validation errors (cross-course reference, circular dependency)
  must surface back into the UI as a specific, readable message — not a
  generic "save failed."

## Implementation
1. `src/Rest/LessonDripRuleController.php` — `PUT
   /ghost-lms/v1/lessons/{id}/drip-rule`, payload is the rule JSON (or `null`
   to clear). Permission-checked via
   `Capabilities::current_user_can_manage_course()`. Runs
   `DripRuleValidator` (spec 17) before saving; returns a structured error
   (not just a generic 400) when validation fails, so the UI can display it
   verbatim.
2. React: `DripRuleEditor.jsx` — popover/panel component attached to each
   lesson row in `LessonList.jsx` (spec 06). Radio-select for rule type, with
   the matching sub-field shown conditionally (date picker / number input /
   lesson dropdown). Save calls the REST route with optimistic UI update;
   on a validation error response, rolls back and shows the server's message
   inline rather than a generic failure toast.
3. Lesson row badge reflecting the current rule at a glance (distinct icon
   per type: none / calendar / clock / link-to-lesson).
4. Update the frontend lesson-player sidebar template (spec 14) to render
   `DripRule::describe()`'s output as subtext under locked lesson entries.

## Verification checklist
- [ ] Instructor can set, change, and clear a drip rule for any lesson in their course.
- [ ] The prerequisite dropdown only offers lessons from the same course.
- [ ] Attempting to save a rule that would create a circular dependency shows the server's specific error message inline and does not save.
- [ ] Attempting to save a rule referencing a lesson in a different course is rejected with a clear message.
- [ ] Lesson row badges in the builder accurately reflect the currently saved rule.
- [ ] Student-facing sidebar shows the correct human-readable unlock condition for each locked lesson, matching its actual rule type.
- [ ] Build passes with no console or PHP errors; non-owning instructor gets 403 on the drip-rule route for a course they don't own.