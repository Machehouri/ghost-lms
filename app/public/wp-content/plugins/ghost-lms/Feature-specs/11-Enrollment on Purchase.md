# 11 — Enrollment on Purchase

Read `context/AGENTS.md` and all context files before starting.
Depends on: 03-database-migrations, 10-course-product-linking.

## Goal
Wire WooCommerce's order lifecycle to real enrollment records: a completed
order grants access, a refunded or cancelled order revokes it. This is the
concrete implementation of the architecture invariant that WooCommerce order
status is the single source of truth for enrollment.

## Design decisions
- Hook on `woocommerce_order_status_completed` — deliberately not `processing`,
  even though WooCommerce often auto-transitions virtual-product orders
  straight to `completed`. Hooking the explicit `completed` status keeps the
  behavior correct regardless of a given store's payment-gateway/auto-complete
  configuration, rather than relying on an assumption about when that
  transition happens.
- For each line item on the completed order, resolve `_glms_course_id` from
  the product; if present, create or reactivate an enrollment for
  `$order->get_user_id()` against that course. An order can contain a mix of
  course and non-course products — only line items with a linked course
  produce enrollments.
- **Idempotency**: WooCommerce can fire status-transition hooks more than once
  for the same order in some configurations. Before inserting, check for an
  existing enrollment with the same `(user_id, course_id)` — the unique index
  from `03-database-migrations.md` backs this — and reactivate rather than
  duplicate if one exists (covers the repurchase-after-refund case too).
- **Guest checkout edge case**: if `$order->get_user_id()` resolves to `0` (no
  associated WordPress account), don't silently drop the purchase. Add a
  visible order note flagging that no enrollment could be created, so an
  admin can manually reconcile. Document this as a known v1 limitation in the
  progress tracker — it assumes accounts are required for course purchases,
  which is worth confirming as a WooCommerce checkout setting on this store.
- Refund/cancellation: hook `woocommerce_order_status_refunded` and
  `woocommerce_order_status_cancelled`; look up enrollments by `order_id` and
  set `status = revoked`. The row is never deleted — progress data tied to it
  (Phase 4+) needs to survive even if access is revoked, in case of a later
  re-purchase or dispute resolution.
- Fire `do_action('ghost_lms_enrollment_created', $user_id, $course_id,
  $order_id)` after a successful enrollment, per the `ghost_lms_` custom-hook
  convention in `03-code-standards.md` — this gives later units (email
  notifications, cohort auto-assignment) a place to hook in without modifying
  this file.
- All of this lives in `src/Woo/` only, per the architecture boundary that no
  WooCommerce hooks exist outside that directory.

## Implementation
1. `src/Database/EnrollmentRepository.php`:
   - `create_or_reactivate(int $user_id, int $course_id, int $order_id): void`
   - `revoke_by_order(int $order_id): void`
   - `is_enrolled(int $user_id, int $course_id): bool`
   - `get_active_for_user(int $user_id): array`
2. `src/Woo/EnrollmentHooks.php`:
   - `on_order_completed( $order_id )` — iterates line items, resolves linked
     courses, calls the repository, fires the custom action, adds an order
     note on the guest-checkout edge case.
   - `on_order_refunded_or_cancelled( $order_id )` — calls
     `revoke_by_order()`.
3. Register both hooks in `Core\Plugin::boot()`.

## Verification checklist
- [ ] Completing a WooCommerce order for a linked course product creates an active enrollment for the purchasing user.
- [ ] Refunding or cancelling that order revokes the enrollment (status changes, row is not deleted).
- [ ] Re-purchasing after a revoke reactivates the same enrollment row with the new order ID, not a duplicate row.
- [ ] An order with a mix of course and non-course line items only enrolls the course items.
- [ ] Simulate the same status-transition hook firing twice — confirm no duplicate enrollment is created.
- [ ] A guest-checkout order (no resolvable user) adds a visible order note instead of silently failing.
- [ ] `ghost_lms_enrollment_created` fires exactly once per genuinely new enrollment.