# 06 — Module/Lesson Tree UI

Read `context/AGENTS.md` and all context files before starting.
Depends on: 05-course-builder-shell.

## Goal
Full CRUD for a course's curriculum structure inside the builder: create/rename/
delete modules, create/rename/delete lessons within a module, and drag-reorder
both modules and lessons (including moving a lesson between modules). No lesson
*content* editing yet (that's 07) — lessons here are just titled containers.

## Design decisions — curriculum data model (locks in the open question from `02-architecture.md`)
Modules are **not** a post type or a custom table — they're an ordered JSON
structure stored in a single postmeta key on the course: `_glms_curriculum`.

```json
[
  {
    "module_id": "mod_a1b2c3",
    "title": "Getting Started",
    "order": 0,
    "lessons": [
      { "lesson_id": 142, "order": 0 },
      { "lesson_id": 143, "order": 1 }
    ]
  }
]
```

- `module_id` is a short generated string (not a post ID) — modules are purely
  organizational, they don't need their own revisions, authorship, or queries.
- `lesson_id` refers to a real `glms_lesson` post (spec 02), which keeps
  `post_parent = course_id` regardless of which module it's currently grouped
  under — the module grouping is a display/organizational concern living only in
  this meta field, not a change to the post's own parent relationship.
- This is a deliberate simplicity tradeoff over a `glms_module` custom table: no
  extra joins for the common case (rendering a course's curriculum), at the cost
  of the meta blob needing to stay in sync with real lesson posts (handled by
  routing all mutations through one repository, below). Log this decision in the
  progress tracker.

## Implementation
1. `src/Curriculum/CurriculumRepository.php` — the single place that reads/writes `_glms_curriculum`:
   - `get(int $course_id): array` — returns the structure with lesson titles/status resolved (joins in lesson post data for convenience, doesn't mutate the stored meta).
   - `save(int $course_id, array $curriculum): void` — validates shape, writes meta.
   - `add_module()`, `rename_module()`, `delete_module()`, `add_lesson()`, `delete_lesson()`, `reorder()` — convenience methods used by the REST controller, all going through `get()`/`save()` internally so there's one code path for consistency.
   - `delete_lesson()` also trashes the underlying `glms_lesson` post (`wp_trash_post`, not permanent delete) and removes it from the curriculum structure in the same call.
2. `src/Rest/CurriculumController.php` — routes under `ghost-lms/v1/courses/{id}/curriculum`:
   - `GET` — full curriculum tree.
   - `PUT` — bulk replace (used for drag-reorder and rename operations sent as one payload from the UI).
   - `POST /modules` — create a module.
   - `DELETE /modules/{module_id}` — delete a module and everything in it (confirm this is intended — trash all contained lessons).
   - `POST /lessons` — create a lesson (creates the `glms_lesson` draft post *and* appends it to a module in one call).
   - `DELETE /lessons/{lesson_id}` — per the repository method above.
   - Every route's permission callback uses `Capabilities::current_user_can_manage_course()`.
3. React: `ModuleList.jsx` (add/rename/delete module, native HTML5 drag handle to reorder modules), `LessonList.jsx` nested per module (add/rename/delete lesson, drag to reorder within a module or drop into a different module).
4. Reorder/move actions update local state immediately (optimistic UI) and debounce a `PUT` to persist; on request failure, roll back to the last known-good state and show an inline error — never leave the UI showing a state that didn't actually save.
5. Empty-state placeholder from spec 05 is replaced with the real "Add module" button.

## Verification checklist
- [ ] Create, rename, and delete a module.
- [ ] Create, rename, and delete a lesson inside a module.
- [ ] Drag-reorder modules, and drag-reorder lessons both within a module and across modules — reload the page and confirm the order persisted.
- [ ] Deleting a lesson trashes the underlying post (verify in Trash, not gone) and removes it from the curriculum meta in the same action.
- [ ] Deleting a module trashes all lessons it contained.
- [ ] A failed save (simulate by killing the network tab mid-drag) rolls the UI back and surfaces an error instead of silently losing the change.
- [ ] Non-owning instructor gets 403 on every curriculum route for a course they don't own.