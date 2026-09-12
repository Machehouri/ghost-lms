# 05 — Course Builder Admin Page Shell

Read `context/AGENTS.md` and all context files before starting.
Depends on: 01-plugin-bootstrap, 02-custom-post-types, 04-roles-capabilities.

## Goal
Create the skeleton admin screen the course builder will live in: a React app
mounted in `wp-admin`, authenticated against the REST API, loading one course's
basic data and showing a clean empty state. No curriculum CRUD yet (that's 06) —
this unit only has to prove the wiring end to end.

## Design decisions
- Not a top-level menu item — reached via an **"Edit Curriculum"** link added to
  the `glms_course` edit screen (a simple meta box with a button), so the
  entry point is always tied to a specific course. URL pattern:
  `admin.php?page=glms-course-builder&course_id={id}`.
- The page is registered via `add_submenu_page` under the "Ghost LMS" parent menu
  from spec 02, but hidden from the visible menu (WordPress convention: register
  with a `null` or duplicate parent slug trick, or register normally and hide via
  `remove_submenu_page` on `admin_menu` — either is fine, pick one and note it in
  the progress tracker) since it's not meant to be navigated to directly.
- Built with `@wordpress/scripts` (matches `03-code-standards.md`); entry point
  `assets/admin-builder/src/index.js`, mounts a React root into a single `<div
  id="glms-course-builder-root">`.
- REST auth uses `@wordpress/api-fetch`, which handles the WP REST nonce
  automatically when initialized with `apiFetch.use(apiFetch.createNonceMiddleware(nonce))`
  — nonce and course ID passed from PHP via `wp_localize_script` (or
  `wp_add_inline_script`), not hardcoded or refetched.
- For this unit, fetch the course title via the **core** REST endpoint
  (`/wp/v2/glms_course/{id}`, already available since spec 02 set
  `show_in_rest => true`) — no custom endpoint needed yet. The custom curriculum
  endpoint arrives in spec 06.
- Access control: page load checks
  `Capabilities::current_user_can_manage_course($course_id)` server-side before
  rendering anything — an instructor hitting another instructor's course_id via
  URL gets a permission error, not a builder UI that silently 403s on every
  fetch.

## Implementation
1. `package.json` + `@wordpress/scripts` build config (`npm run start` / `npm run build`) for `assets/admin-builder/`.
2. `assets/admin-builder/src/index.js` — mounts `<App courseId={window.glmsBuilder.courseId} />`.
3. `assets/admin-builder/src/App.jsx` — fetches the course via `apiFetch({ path: '/wp/v2/glms_course/' + courseId })`, shows a loading spinner, then the course title in a header bar and an empty-state card ("No modules yet — add your first module" — button is a visual placeholder for now, wired up in 06).
4. `src/Admin/CourseBuilderPage.php` — registers the hidden submenu page; server-side capability check (redirect/`wp_die` with a clear message if it fails); enqueues the built JS/CSS only on this specific admin page; passes `courseId`, REST root URL, and nonce via `wp_localize_script('glms-course-builder', 'glmsBuilder', [...])`.
5. `src/Admin/CourseEditMetaBox.php` — adds the "Edit Curriculum" meta box to the `glms_course` edit screen with a button linking to the builder page for that course.

## Verification checklist
- [ ] Clicking "Edit Curriculum" from a course's edit screen opens the builder page with that course's real title loaded via REST.
- [ ] Page shows a clean loading state then a clean empty state — no console errors, no flash of unstyled content.
- [ ] `npm run build` produces production assets; the plugin loads the built (not dev) bundle when `SCRIPT_DEBUG` is off.
- [ ] An instructor attempting to load another instructor's `course_id` via direct URL gets a permission error, not the builder UI.
- [ ] No PHP notices/warnings on this admin screen.