# 04 — Roles & Capabilities

Read `context/AGENTS.md` and all six context files before starting.
Depends on: 01-plugin-bootstrap.

## Goal
Register the `glms_instructor` role and the full set of custom capabilities used
throughout the plugin, plus a central helper for checking them. This unlocks
correct `capability_type` wiring on the CPTs from spec 02 (retro-apply once this
lands) and is a prerequisite for every access-control decision in later units.

## Design decisions
- Capabilities are custom strings, not repurposed core ones (`edit_posts` etc.) — so an instructor's access is scoped to Ghost LMS content only, never accidentally to unrelated post types.
- Three logical capability groups:
  - **Content management**: `edit_glms_courses`, `edit_others_glms_courses`, `publish_glms_courses`, `delete_glms_courses` (standard WP CPT capability pattern, applied per-CPT for course/lesson/quiz/question).
  - **Own-content scoping**: instructors get `edit_glms_courses` + `publish_glms_courses` but **not** `edit_others_glms_courses` — enforced via `map_meta_cap` so an instructor can only edit/delete their own courses, never another instructor's.
  - **Operational**: `glms_manage_enrollments`, `glms_view_reports` (own-scope for instructor, all-scope for admin — the scoping itself happens in the report-query layer built in Phase 10, this unit just defines the capability).
- Role setup: `glms_instructor` role created on activation with the content-management + operational capabilities above (own-scope only). The `administrator` role gets all Ghost LMS capabilities added directly (not a new role) so existing admins don't lose access.
- Central helper: `GhostLMS\Access\Capabilities::current_user_can_manage_course(int $course_id): bool` — true for admins, true for the instructor who authored the course, false otherwise. Every later unit's "can this user edit this course" check goes through this one function.
- Role/capability registration and removal both live in `Activator`/`Deactivator` — capabilities are removed on **uninstall**, not on mere deactivation (a site owner deactivating temporarily shouldn't lose instructor permissions).

## Implementation
1. `src/Access/Capabilities.php`:
   - Constants for every capability string (avoid magic strings elsewhere in the codebase).
   - `register_role()` — creates `glms_instructor` with the capability set above, called from `Activator::activate()`.
   - `add_admin_capabilities()` — adds all Ghost LMS capabilities to the `administrator` role, called from `Activator::activate()`.
   - `current_user_can_manage_course(int $course_id): bool` helper.
   - `remove_role_and_capabilities()` — called only from `uninstall.php`, strips capabilities from `administrator` and removes the `glms_instructor` role entirely.
2. `map_meta_cap` filter for the four CPTs' capability types, so `edit_glms_course` (singular, meta cap) correctly resolves to `edit_others_glms_courses` vs `edit_glms_courses` based on post ownership — this is what actually enforces "instructors edit only their own courses."
3. Update the CPT registrations from spec 02 to reference these real capability constants (they were stubbed as strings there).
4. Simple admin screen (can live under the "Ghost LMS" menu from spec 02) to promote an existing WP user to `glms_instructor` — minimal UI, just a user picker + button, no bulk management yet.

## Verification checklist
- [ ] A user assigned `glms_instructor` can create/edit/delete their own courses/lessons/quizzes/questions but gets a WP permission error attempting to edit another instructor's course directly by URL (`post.php?post=X&action=edit`).
- [ ] An `administrator` can edit any course regardless of author.
- [ ] `Capabilities::current_user_can_manage_course()` returns correct results for admin / owning-instructor / other-instructor / logged-out in a manual test.
- [ ] Deactivating and reactivating the plugin does not remove the `glms_instructor` role or its assigned users; only uninstall does.
- [ ] Promote-to-instructor admin screen correctly adds the role to a selected user.