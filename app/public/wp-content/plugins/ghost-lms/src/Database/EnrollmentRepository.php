<?php
/**
 * Repository for course enrollment records.
 *
 * @package GhostLMS\Database
 */

declare(strict_types=1);

namespace GhostLMS\Database;

final class EnrollmentRepository
{
    /**
     * Create or reactivate an enrollment.
     *
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @param int $order_id WooCommerce order ID.
     * @return void
     */
    public static function create_or_reactivate(int $user_id, int $course_id, int $order_id): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'glms_enrollments';
        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND course_id = %d LIMIT 1",
                $user_id,
                $course_id
            )
        );

        $data = [
            'user_id' => $user_id,
            'course_id' => $course_id,
            'order_id' => $order_id > 0 ? $order_id : null,
            'status' => 'active',
            'enrolled_at' => current_time('mysql', true),
            'expires_at' => null,
        ];

        if ($existing_id) {
            $wpdb->update($table, $data, ['id' => (int) $existing_id], ['%d', '%d', '%d', '%s', '%s', '%s'], ['%d']);
            return;
        }

        $wpdb->insert($table, $data, ['%d', '%d', '%d', '%s', '%s', '%s']);
    }

    /**
     * Revoke all enrollments belonging to an order.
     *
     * @param int $order_id WooCommerce order ID.
     * @return void
     */
    public static function revoke_by_order(int $order_id): void
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'glms_enrollments',
            ['status' => 'revoked'],
            ['order_id' => $order_id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Determine whether a user currently has access to a course.
     *
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @return bool
     */
    public static function is_enrolled(int $user_id, int $course_id): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'glms_enrollments';
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND course_id = %d AND status = %s",
                $user_id,
                $course_id,
                'active'
            )
        );

        return (int) $count > 0;
    }

    /**
     * Return active, non-expired enrollments for a user.
     *
     * @param int $user_id WordPress user ID.
     * @return array<int, object>
     */
    public static function get_active_for_user(int $user_id): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'glms_enrollments';
        $now = current_time('mysql', true);

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d AND status = %s AND (expires_at IS NULL OR expires_at > %s) ORDER BY enrolled_at DESC",
                $user_id,
                'active',
                $now
            )
        );
    }
}