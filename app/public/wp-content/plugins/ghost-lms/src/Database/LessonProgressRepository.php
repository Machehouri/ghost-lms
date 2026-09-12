<?php
/**
 * Repository for lesson progress records.
 *
 * @package GhostLMS\Database
 */

declare(strict_types=1);

namespace GhostLMS\Database;

final class LessonProgressRepository
{
    /**
     * Mark a lesson complete for a user and course.
     *
     * @param int $user_id WordPress user ID.
     * @param int $lesson_id Lesson post ID.
     * @param int $course_id Course post ID.
     * @return void
     */
    public static function mark_complete(int $user_id, int $lesson_id, int $course_id): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'glms_lesson_progress';
        $wpdb->replace(
            $table,
            [
                'user_id' => $user_id,
                'lesson_id' => $lesson_id,
                'course_id' => $course_id,
                'status' => 'completed',
                'completed_at' => current_time('mysql', true),
            ],
            ['%d', '%d', '%d', '%s', '%s']
        );
    }

    /**
     * Determine whether a user completed a lesson.
     *
     * @param int $user_id WordPress user ID.
     * @param int $lesson_id Lesson post ID.
     * @return bool
     */
    public static function is_complete(int $user_id, int $lesson_id): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'glms_lesson_progress';
        $status = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT status FROM {$table} WHERE user_id = %d AND lesson_id = %d LIMIT 1",
                $user_id,
                $lesson_id
            )
        );

        return 'completed' === $status;
    }

    /**
     * Return completed lesson IDs for a course.
     *
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @return array<int, int>
     */
    public static function get_completed_lesson_ids(int $user_id, int $course_id): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'glms_lesson_progress';
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT lesson_id FROM {$table} WHERE user_id = %d AND course_id = %d AND status = %s",
                $user_id,
                $course_id,
                'completed'
            )
        );

        return array_map('absint', $ids);
    }
}