# 16 — Sequencing & Prerequisite Rules

Read `context/AGENTS.md` and all context files before starting.
Depends on: 06-module-lesson-tree, 14-lesson-player, 15-progress-tracking.

## Goal
An optional per-course toggle that locks lesson N+1 until lesson N is
complete, enforced server-side — not bypassable by guessing the next lesson's
URL.

## Design decisions
- Course-level setting, not per-lesson: `_glms_sequential_progression`
  (boolean meta). A simple checkbox meta box on the course edit screen — this
  is a single course-wide flag, so it doesn't belong in the React curriculum
  builder (which stays scoped to structure, not settings).
- "Locked" is defined by walking the curriculum in its saved order (module
  order, then lesson order within module, from `_glms_curriculum`) and finding
  the first incomplete lesson — everything after it is locked; everything up
  to and including it is unlocked.
- **Shared shape with drip content (Phase 5)**: both sequencing and drip rules
  answer the same underlying question — "is this lesson unlocked for this
  user right now?" Rather than building two separate gating mechanisms, this
  unit introduces `src/Access/LessonUnlockResolver.php` with an
  `is_unlocked(int $user_id, int $lesson_id): bool` method. Phase 5 will
  *extend* this resolver (both sequencing and drip conditions must pass) —
  not replace it or duplicate its logic.
- Enforcement happens in two places: the lesson-player controller (server-side
  redirect if locked — this is the real gate) and the sidebar UI (lock icon,
  non-clickable — this is just the visible reflection of the same check, never
  the only thing standing between a student and locked content).

## Implementation
1. `src/Access/LessonUnlockResolver.php` — `is_unlocked()`: returns `true`
   immediately if `_glms_sequential_progression` is off for the course (any
   lesson the student has base access to, per spec 13, is unlocked); if on,
   walks the curriculum via `CurriculumRepository::get()` plus completion
   state via `LessonProgressRepository::get_completed_lesson_ids()`, and
   returns `false` for any lesson past the first incomplete one.
2. Course edit screen: checkbox meta box "Require lessons to be completed in
   order," saving `_glms_sequential_progression`.
3. Update `src/Frontend/LessonPlayerController.php` (spec 14) to call
   `LessonUnlockResolver::is_unlocked()` in addition to the existing
   `CourseAccess` check; on a locked hit, redirect to the first incomplete
   lesson with an explanatory message rather than a generic error.
4. Update the sidebar template (spec 14) to render a lock icon and
   `aria-disabled`, no working link, on locked entries — visual state only,
   the redirect above is what actually enforces it.

## Verification checklist
- [ ] With the setting off (default), all lessons remain accessible in any order — existing spec 14 behavior is unchanged.
- [ ] With it on, a student cannot open lesson 3 before completing lesson 2, even by typing the URL directly — verified via a direct request, not just by checking the UI hides the link.
- [ ] Sidebar shows lock icons on not-yet-reachable lessons and updates immediately as prior lessons are completed.
- [ ] The owning instructor and an administrator can access any lesson regardless of this setting, consistent with spec 13's base access exemption.
- [ ] Turning the setting off immediately unlocks all lessons for every student on their next page load — no stale locked state.