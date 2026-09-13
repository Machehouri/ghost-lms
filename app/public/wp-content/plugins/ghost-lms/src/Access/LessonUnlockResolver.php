<?php
/**
 * Resolve lesson visibility for optional sequential progression rules.
 *
 * @package GhostLMS\Access
 */

declare(strict_types=1);

namespace GhostLMS\Access;

use GhostLMS\Curriculum\CurriculumRepository;
use GhostLMS\Database\LessonProgressRepository;

final class LessonUnlockResolver
{
    /**
     * Determine whether a lesson is unlocked for the given user.
     *
     * @param int $user_id WordPress user ID.
     * @param int $lesson_id Lesson post ID.
     * @return bool
     */
    public static function is_unlocked(int $user_id, int $lesson_id): bool
    {
        if ($lesson_id <= 0) {
            return false;
        }

        $course_id = self::get_course_id_for_lesson($lesson_id);
        if ($course_id <= 0) {
            return true;
        }

        $course = get_post($course_id);
        if (! $course || 'glms_course' !== $course->post_type) {
            return true;
        }

        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        if ((int) $course->post_author === $user_id) {
            return true;
        }

        if ('1' !== (string) get_post_meta($course_id, '_glms_sequential_progression', true)) {
            return true;
        }

        $ordered_lesson_ids = self::ordered_lesson_ids($course_id);
        if ([] === $ordered_lesson_ids) {
            return true;
        }

        $first_incomplete_lesson_id = self::get_first_incomplete_lesson_id($user_id, $course_id);
        if ($first_incomplete_lesson_id <= 0) {
            return true;
        }

        $current_position = array_search($lesson_id, $ordered_lesson_ids, true);
        $first_incomplete_position = array_search($first_incomplete_lesson_id, $ordered_lesson_ids, true);

        if (false === $current_position || false === $first_incomplete_position) {
            return true;
        }

        return $current_position <= $first_incomplete_position;
    }

    /**
     * Return the first incomplete lesson id for a course in the curriculum order.
     *
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @return int
     */
    public static function get_first_incomplete_lesson_id(int $user_id, int $course_id): int
    {
        if ($course_id <= 0) {
            return 0;
        }

        if ('1' !== (string) get_post_meta($course_id, '_glms_sequential_progression', true)) {
            return 0;
        }

        $completed_ids = LessonProgressRepository::get_completed_lesson_ids($user_id, $course_id);

        foreach (self::ordered_lesson_ids($course_id) as $lesson_id) {
            if (! in_array($lesson_id, $completed_ids, true)) {
                return (int) $lesson_id;
            }
        }

        return 0;
    }

    /**
     * Return the ordered lesson ids for a course curriculum.
     *
     * @param int $course_id Course post ID.
     * @return array<int, int>
     */
    private static function ordered_lesson_ids(int $course_id): array
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

    /**
     * Resolve the course id for a lesson from the lesson's metadata.
     *
     * @param int $lesson_id Lesson post ID.
     * @return int
     */
    private static function get_course_id_for_lesson(int $lesson_id): int
    {
        $course_ids = array_map('absint', (array) get_post_meta($lesson_id, '_glms_course_ids', true));

        return (int) ($course_ids[0] ?? 0);
    }
}
