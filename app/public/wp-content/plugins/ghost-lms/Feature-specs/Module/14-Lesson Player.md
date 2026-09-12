# 14 — Lesson Player

Read `context/AGENTS.md` and all context files before starting.
Depends on: 06-module-lesson-tree, 07-lesson-content-editor, 13-access-control-layer.

## Goal
The page where an enrolled student actually consumes a lesson: a curriculum
sidebar, a content pane rendering the lesson's text/video/attachment, and a
working "Mark Complete" action. Every lesson is accessible to any enrolled
student in this unit — sequencing/locking rules are spec 16, not this one.

## Design decisions
- Lessons have no public permalink (`glms_lesson` is `public => false`, per
  spec 02), so the player isn't the lesson's own single template. It's a
  dedicated route via a custom rewrite rule: `/learn/{course-slug}/{lesson-id}/`
  → `index.php?glms_learn_course={slug}&glms_learn_lesson={id}`, handled on
  `template_redirect` rather than the normal template hierarchy.
- Access gate on every load: `CourseAccess::can_view_course_content()` (spec
  13). Non-enrolled logged-in users are redirected to the course detail page
  with a friendly "Enroll to access this content" message. Logged-out visitors
  are redirected to login.
- Sidebar renders the full curriculum via `CurriculumRepository::get()` (spec
  06), with a checkmark on lessons already completed by this user — this unit
  introduces a first slice of progress tracking (per-lesson status lookups)
  purely to drive these checkmarks; the full aggregate/percent calculation is
  spec 15.
- Content pane renders the lesson's `post_content` (already safe — it's the
  instructor's own block-editor content), the video embed (via
  `_glms_video_url` + `wp_oembed_get()`), and an attachment download link.
- **Attachment downloads go through a gated proxy route**, not a raw media
  library URL — `GET /ghost-lms/v1/lessons/{id}/attachment` checks access
  first, then redirects to the real file. Note: this gates the *link
  surfaced by the player*; it doesn't prevent someone who already has the raw
  uploads URL (WordPress media files sit in the public uploads directory by
  default) from accessing it directly. Flag this as an accepted v1 limitation
  in the progress tracker, not something this unit solves — true
  file-level protection would require moving attachments out of the default
  public uploads path.
- Standard theme header/footer still wrap the player (two-column layout:
  sidebar + content) rather than hijacking full page chrome — matches the
  theme-agnostic principle in `05-ui-context.md`. A distraction-free
  full-screen mode is a possible future enhancement, not required here.
- "Mark Complete" is a small vanilla-JS `fetch`/`apiFetch` call — no React on
  the public frontend, per the architecture boundary (React is admin-builder
  only).

## Implementation
1. `src/Database/LessonProgressRepository.php` (first slice — expanded in
   spec 15): `mark_complete(int $user_id, int $lesson_id, int $course_id):
   void`, `is_complete(int $user_id, int $lesson_id): bool`,
   `get_completed_lesson_ids(int $user_id, int $course_id): array`.
2. Rewrite rule + query vars for `/learn/{course-slug}/{lesson-id}/`,
   registered on `init`, flushed on plugin activation (extend spec 01's
   `Activator`, or note in the progress tracker that a manual permalink flush
   is needed for existing installs).
3. `src/Frontend/LessonPlayerController.php` — hooked on `template_redirect`;
   resolves course/lesson from the query vars, runs the spec 13 access check,
   redirects on failure, otherwise renders the player template directly
   (sidebar + content pane) and calls `exit`.
4. `src/Rest/LessonProgressController.php` — `POST
   /ghost-lms/v1/lessons/{id}/complete`, access-checked, calls
   `LessonProgressRepository::mark_complete()`.
5. `src/Rest/LessonAttachmentController.php` — `GET
   /ghost-lms/v1/lessons/{id}/attachment`, access-checked, redirects to
   `wp_get_attachment_url()`.
6. Sidebar/content pane markup + a small vanilla JS file wiring the "Mark
   Complete" button to the REST route and updating the DOM (checkmark,
   button state) without a full page reload.

## Verification checklist
- [ ] An enrolled student visiting `/learn/{course-slug}/{lesson-id}/` sees the sidebar and lesson content; video embed renders; attachment link downloads the correct file.
- [ ] A non-enrolled logged-in user is redirected to the course detail page with a message, not shown lesson content.
- [ ] A logged-out visitor is redirected to login.
- [ ] Clicking "Mark Complete" persists to `glms_lesson_progress` and updates the sidebar checkmark without a full page reload.
- [ ] The attachment download route enforces the same access check as the lesson content itself (a non-enrolled user hitting the route directly gets denied).
- [ ] The owning instructor and an administrator can view any lesson regardless of their own enrollment status.