# 03 — Database Migrations

Read `context/AGENTS.md` and all six context files before starting.
Depends on: 01-plugin-bootstrap.

## Goal
Create all `glms_*` custom tables listed in `02-architecture.md` via a versioned
migration system driven off `dbDelta`, run on activation and safely re-run on
version bumps. No repository classes or business logic yet — just correct schema.

## Design decisions
- Single migration entry point: `GhostLMS\Database\Migrator::maybe_migrate()`, called on `plugins_loaded` (not only on activation) so upgrading the plugin file without a fresh activation still applies new schema — compares a stored option `ghost_lms_db_version` against `GHOST_LMS_DB_VERSION` (separate constant from `GHOST_LMS_VERSION`, since plugin version and schema version don't always change together).
- All table definitions in one place: `Database/Schema.php`, returns an array of `[table_name => CREATE TABLE SQL]` for `dbDelta`.
- Every table has `id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`, uses `$wpdb->prefix`, `DEFAULT CHARSET` from `$wpdb->get_charset_collate()`.
- Foreign keys are **not** enforced at the MySQL level (matches WP core convention — `wp_postmeta` etc. don't use FK constraints either); referential integrity is enforced in the repository layer built in later units. This is a deliberate, documented tradeoff — log it in the progress tracker.
- Indexes: every table indexes its most common lookup pattern (e.g. `glms_enrollments` indexes `(user_id, course_id)` and `course_id` separately for instructor-side "who's enrolled in X" queries).

## Implementation
Create these tables exactly as specified (columns beyond `id`/timestamps are the
contract other units build against — don't add or omit columns without updating
`02-architecture.md`):

1. `glms_enrollments`: `user_id`, `course_id`, `order_id` (nullable, WooCommerce order), `status` (enum-like varchar: `active`, `revoked`, `expired`), `enrolled_at` (datetime), `expires_at` (datetime, nullable). Index: `(user_id, course_id)` unique, `course_id`.
2. `glms_lesson_progress`: `user_id`, `lesson_id`, `course_id`, `status` (`not_started`, `in_progress`, `completed`), `completed_at` (datetime, nullable). Index: `(user_id, lesson_id)` unique, `(user_id, course_id)`.
3. `glms_quiz_attempts`: `user_id`, `quiz_id`, `score` (float, nullable until graded), `passed` (tinyint, nullable), `started_at`, `submitted_at` (nullable), `answers` (longtext, JSON-encoded). Index: `(user_id, quiz_id)`, `quiz_id`.
4. `glms_cohorts`: `course_id`, `name` (varchar), `start_date` (date), `capacity` (int, nullable = unlimited). Index: `course_id`.
5. `glms_cohort_members`: `cohort_id`, `user_id`, `joined_at` (datetime). Index: `(cohort_id, user_id)` unique.
6. `glms_certificates`: `user_id`, `course_id`, `issued_at` (datetime), `file_path` (varchar, relative to uploads dir), `verification_code` (varchar, unique). Index: `(user_id, course_id)` unique, `verification_code` unique.
7. `glms_forum_posts`: `course_id`, `lesson_id` (nullable), `user_id`, `parent_id` (nullable, self-referential, one level only per `05-ui-context.md`), `body` (longtext), `status` (`published`, `pending`, `deleted`), `created_at`. Index: `course_id`, `(course_id, lesson_id)`.

Build:
1. `Database/Schema.php` — returns the SQL array above.
2. `Database/Migrator.php` — `maybe_migrate()`: compares versions, calls `dbDelta()` per table if outdated, updates the `ghost_lms_db_version` option.
3. Wire `Migrator::maybe_migrate()` into both `Activator::activate()` (from spec 01) and a `plugins_loaded` hook for upgrade-path coverage.
4. `uninstall.php` — add (but leave commented out / behind a settings toggle checked in a later unit) the `DROP TABLE` statements for full cleanup — don't destructively drop data by default.

## Verification checklist
- [ ] Fresh activation creates all 7 tables with correct columns/indexes (verify via `DESCRIBE` or phpMyAdmin).
- [ ] Bumping `GHOST_LMS_DB_VERSION` and reloading any admin page (without reactivating) triggers the migration and updates the stored version option.
- [ ] Running activation twice in a row doesn't error or duplicate tables (idempotent, since `dbDelta` handles this, but verify it actually does with this schema).
- [ ] All table/column names match this spec exactly — flag any deviation before implementing, don't silently rename.