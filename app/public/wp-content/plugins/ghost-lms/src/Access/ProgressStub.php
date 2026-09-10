<?php
/**
 * Temporary course progress provider.
 *
 * @package GhostLMS\Access
 */

declare(strict_types=1);

namespace GhostLMS\Access;

final class ProgressStub
{
    /**
     * Return the current course progress percentage.
     *
     * @todo Replace with real calculation in the lesson-progress spec (Phase 4).
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @return int
     */
    public static function get_course_progress_percent(int $user_id, int $course_id): int
    {
        return 0;
    }
}