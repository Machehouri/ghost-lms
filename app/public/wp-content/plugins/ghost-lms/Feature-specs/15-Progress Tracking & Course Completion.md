# 15 — Progress Tracking & Course Completion

Read `context/AGENTS.md` and all context files before starting.
Depends on: 03-database-migrations, 12-my-courses-dashboard, 14-lesson-player.

## Goal
Turn per-lesson completion into a real course-level percentage, replace the
Phase 3 `ProgressStub` everywhere it's used, and fire a completion event other
phases (certificates, in Phase 7) can hook into.

## Design decisions
- **Percent complete** = (completed lessons for this user+course) ÷ (total
  lessons currently in that course's curriculum) × 100, rounded to the nearest
  integer. The denominator comes from `CurriculumRepository::get()` — live
  curriculum state, not a stale count — so a deleted lesson never leaves
  progress stuck below 100% or shows an impossible value over 100%.
- **Course completion** = percent reaches 100. On that transition, fire
  `do_action('ghost_lms_course_completed', $user_id, $course_id)` — Phase 7's
  certificate generation will hook into this without needing to modify this
  file. Guard against firing more than once: only fire on the transition
  *into* 100%, checked against the state immediately before this particular
  lesson completion, not on every recalculation afterward.
- REST surface: `GET /ghost-lms/v1/courses/{id}/progress` → `{
  completed_lesson_ids: [...], percent_complete: int, completed_at:
  string|null }` for the current user. Also accepts a `user_id` query param
  for instructor/admin use (viewing a specific student's progress) —
  access-gated to instructor/admin only when that param is used; a student
  requesting another student's `user_id` gets 403.

## Implementation
1. Expand `LessonProgressRepository` (from spec 14):
   - `get_percent_complete(int $user_id, int $course_id): int`
   - `is_course_complete(int $user_id, int $course_id): bool`
   - `get_completed_at(int $user_id, int $course_id): ?string`
2. Update `mark_complete()` to recalculate percent after recording the lesson
   and fire `ghost_lms_course_completed` on the 0%→100% (not-yet-100%-to-100%)
   transition only.
3. `src/Rest/CourseProgressController.php` — the `GET .../progress` route
   described above, including the instructor/admin `user_id` override with
   its own permission check.
4. Replace `src/Access/ProgressStub.php`'s usage in `MyCoursesBlock.php`
   (spec 12) with a real call to `get_percent_complete()`; delete the stub
   file once nothing references it.
5. Optionally surface the aggregate course % at the top of the lesson-player
   sidebar (spec 14) alongside the existing per-lesson checkmarks — include if
   straightforward, not required if it complicates the layout.

## Verification checklist
- [ ] Completing every lesson in a course brings `percent_complete` to exactly 100 and fires `ghost_lms_course_completed` exactly once.
- [ ] Deleting a lesson from the curriculum after some students have completed it recalculates percentages correctly (denominator shrinks; no value exceeds 100%).
- [ ] "My Courses" dashboard now shows real, correct progress percentages instead of the 0% stub.
- [ ] An instructor/admin can fetch another user's progress via `user_id`; a student attempting the same on another student's ID gets a 403.
- [ ] `ProgressStub.php` is deleted with no remaining references anywhere in the codebase.