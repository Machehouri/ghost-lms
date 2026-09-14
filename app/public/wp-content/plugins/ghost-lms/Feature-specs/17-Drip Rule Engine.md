# 17 — Drip Rule Engine

Read `context/AGENTS.md` and all context files before starting.
Depends on: 16-sequencing-prerequisites, 11-enrollment-on-purchase.

## Goal
Server-side-enforced drip content: a lesson can be locked until a fixed date,
until N days after the student's enrollment, or until another specific lesson
is completed. This extends `LessonUnlockResolver` from spec 16 — it does not
replace or duplicate it.

## Design decisions
- Three rule types, stored as a single JSON blob in lesson postmeta
  (`_glms_drip_rule`), one rule per lesson:
  - `{"type": "fixed_date", "date": "2027-01-01T00:00:00"}`
  - `{"type": "days_after_enrollment", "days": 7}`
  - `{"type": "prerequisite", "lesson_id": 142}`
  - Absent/null = no drip restriction (still subject to base access and
    sequencing).
- `prerequisite` is deliberately different from spec 16's sequencing setting:
  sequencing is an all-or-nothing course-wide flag requiring strict order;
  `prerequisite` is an explicit per-lesson dependency on *any* earlier lesson
  (not necessarily the immediately preceding one), and works independently of
  whether sequential progression is even turned on.
- `LessonUnlockResolver::is_unlocked()` (spec 16) is extended so that unlocked
  now requires **all three** to pass: base access (spec 13) AND the
  sequencing check (spec 16) AND the drip-rule check (this spec).
- **Time is server-authoritative**: `fixed_date` and `days_after_enrollment`
  are evaluated against the site's configured timezone (`current_time()`),
  never the visiting browser's local time — a locked lesson can't be unlocked
  by changing the device's clock.
- `days_after_enrollment` is computed from the student's actual
  `enrolled_at` timestamp in `glms_enrollments` (spec 03/11), not from account
  creation date or any other proxy.
- **Validation at save time, not just at evaluation time**: a `prerequisite`
  rule referencing a lesson in a different course is rejected outright. A
  `prerequisite` chain that would create a cycle (lesson A requires B, B
  requires A, directly or transitively) is detected and rejected before
  saving — this check has to walk the full chain, not just the immediate
  reference.

## Implementation
1. `src/Access/DripRule.php` — parses the rule JSON; `is_satisfied(int
   $user_id, int $course_id, int $lesson_id): bool` implementing all three
   types; a companion `describe(): string` method returning a human-readable
   unlock condition (e.g. "Unlocks Jan 1, 2027", "Unlocks 7 days after
   enrollment", "Complete 'Intro to APIs' first") used by the player UI.
2. Extend `EnrollmentRepository` (spec 11) with `get_enrolled_at(int $user_id,
   int $course_id): ?string` if not already present.
3. Extend `LessonUnlockResolver::is_unlocked()` (spec 16) to also call
   `DripRule::is_satisfied()` for the lesson's rule, if one exists.
4. `src/Access/DripRuleValidator.php` — cross-course reference check and
   cycle detection (walk the prerequisite chain from the lesson being saved;
   reject if it revisits a lesson already in the chain). Used by the save
   handler built in spec 18.
5. Update the lesson-player redirect messaging (spec 14/16) to show
   `DripRule::describe()` when a lesson is locked specifically by a drip
   rule, distinct from the generic sequencing-lock message.

## Verification checklist
- [ ] `fixed_date` rules lock/unlock correctly relative to the site's configured timezone, not the visiting browser's clock.
- [ ] `days_after_enrollment` computes correctly from the student's real enrollment date — verify with a test enrollment backdated in the database.
- [ ] `prerequisite` correctly requires the referenced lesson's completion, and works even when the course-wide sequencing setting (spec 16) is off.
- [ ] Saving a `prerequisite` rule referencing a lesson in a different course is rejected.
- [ ] Saving a rule that would create a circular prerequisite chain (direct or transitive) is rejected with a clear error.
- [ ] The owning instructor and an administrator bypass all drip rules, consistent with spec 13's base access exemption.
- [ ] Lesson-player messaging correctly explains *why* a lesson is locked, per rule type.