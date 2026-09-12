# 02 — Custom Post Types & Taxonomies

Read `context/AGENTS.md` and all six context files before starting.
Depends on: 01-plugin-bootstrap.

## Goal
Register the four content CPTs (`glms_course`, `glms_lesson`, `glms_quiz`,
`glms_question`) and a `glms_course_category` taxonomy. No custom fields, no
builder UI, no frontend templates yet — just correctly registered, hierarchically
sane content types visible in `wp-admin`.

## Design decisions
- All CPT/taxonomy registration lives in `src/PostTypes/`, one class per type, each implementing a shared `Registrable` interface with a `register(): void` method, all invoked from `Core\Plugin` on `init`.
- CPTs are **not** `public` in the WP-admin-menu sense individually — they're grouped under a single top-level "Ghost LMS" admin menu (added in this unit as an empty parent menu; later units add submenu pages) rather than each CPT cluttering the main sidebar.
- `glms_course`: public, hierarchical false, has archive (`courses`), supports title/editor/thumbnail/excerpt.
- `glms_lesson`: public false (never has its own public permalink — lessons are only ever viewed inside the course player context built in a later unit), supports title/editor.
- `glms_quiz`: public false, supports title only (question content lives in `glms_question`).
- `glms_question`: public false, supports title only (the "title" is the question text).
- Relationships (course→module/lesson, quiz→question) are **not** modeled via `post_parent` alone — `post_parent` handles course↔lesson, but ordering and "module" grouping needs a `menu_order` + a `glms_module` term or repeatable field, finalized in the course-builder unit (Phase 2). This unit just needs `menu_order` support enabled (`page-attributes`) on `glms_lesson` and `glms_question` so later units have something to reorder.
- `glms_course_category` taxonomy: hierarchical (like categories, not tags), attached to `glms_course` only.

## Implementation
1. `src/PostTypes/Registrable.php` — interface.
2. `src/PostTypes/CourseType.php`, `LessonType.php`, `QuizType.php`, `QuestionType.php` — each registers one CPT per the decisions above, with proper labels (translatable, `ghost-lms` text domain), `show_in_rest => true` on all four (needed for REST access in later units), `capability_type` mapped to custom capabilities (`edit_glms_courses` etc. — coordinate with spec 04 which defines these; for this unit it's fine to reference the capability strings even though the role/capability registration itself happens in spec 04).
3. `src/PostTypes/CourseCategoryTaxonomy.php` — registers `glms_course_category`.
4. `src/Admin/Menu.php` — adds a single top-level "Ghost LMS" menu page (placeholder content: "Welcome to Ghost LMS" for now) so there's a visible home for future submenus.
5. Wire all of the above into `Core\Plugin::boot()` on `init`.

## Verification checklist
- [ ] All four post types and the taxonomy appear correctly in `wp-admin` under the "Ghost LMS" menu (not scattered across the main sidebar).
- [ ] Creating a `glms_course` post works normally (title, editor, featured image, excerpt, category taxonomy picker).
- [ ] `glms_lesson` posts can be created and assigned a `post_parent` course manually (no UI for this yet beyond the default WP parent-page-style dropdown — that's expected and fine for this unit).
- [ ] Flushing permalinks, `/courses/` archive renders the default archive template with no fatal errors (styling comes later).
- [ ] No PHP notices/warnings on any admin screen for these post types.