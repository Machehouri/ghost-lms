<?php
/**
 * Repository for lesson progress records.
 *
 * @package GhostLMS\Database
 */

declare(strict_types=1);

namespace GhostLMS\Database;

use GhostLMS\Curriculum\CurriculumRepository;

final class LessonProgressRepository
{
    /**
     * Mark a lesson complete for a user and course.
     *
     * @param int $user_id WordPress user ID.
     * @param int $lesson_id Lesson post ID.
     * @param int $course_id Course post ID.
      * @return bool Whether the progress record was persisted.
     */
    public static function mark_complete(int $user_id, int $lesson_id, int $course_id): bool
    {
        $was_complete = self::is_course_complete($user_id, $course_id);
        global $wpdb;

        $table = $wpdb->prefix . 'glms_lesson_progress';
        $saved = false !== $wpdb->replace(
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

        if ($saved && ! $was_complete && self::is_course_complete($user_id, $course_id)) {
            do_action('ghost_lms_course_completed', $user_id, $course_id);
        }

        return $saved;
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

    /**
     * Return the current course progress percentage.
     *
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @return int
     */
    public static function get_percent_complete(int $user_id, int $course_id): int
    {
        $lesson_ids = self::get_curriculum_lesson_ids($course_id);
        if ([] === $lesson_ids) {
            return 0;
        }

        $completed_ids = array_intersect($lesson_ids, self::get_completed_lesson_ids($user_id, $course_id));

        return (int) round((count($completed_ids) / count($lesson_ids)) * 100);
    }

    /**
     * Determine whether all current curriculum lessons are complete.
     *
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @return bool
     */
    public static function is_course_complete(int $user_id, int $course_id): bool
    {
        return 100 === self::get_percent_complete($user_id, $course_id);
    }

    /**
     * Return the completion timestamp when the course is complete.
     *
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @return string|null
     */
    public static function get_completed_at(int $user_id, int $course_id): ?string
    {
        if (! self::is_course_complete($user_id, $course_id)) {
            return null;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'glms_lesson_progress';
        $completed_at = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(completed_at) FROM {$table} WHERE user_id = %d AND course_id = %d AND status = %s",
                $user_id,
                $course_id,
                'completed'
            )
        );

        return is_string($completed_at) && '' !== $completed_at ? $completed_at : null;
    }

    /**
     * Return unique lesson IDs currently in a course curriculum.
     *
     * @param int $course_id Course post ID.
     * @return array<int, int>
     */
    private static function get_curriculum_lesson_ids(int $course_id): array
    {
        $lesson_ids = [];

        foreach (CurriculumRepository::get($course_id) as $module) {
            foreach ($module['lessons'] ?? [] as $lesson) {
                if (is_array($lesson) && isset($lesson['lesson_id'])) {
                    $lesson_ids[] = absint($lesson['lesson_id']);
                }
            }
        }

        return array_values(array_unique(array_filter($lesson_ids)));
    }
}