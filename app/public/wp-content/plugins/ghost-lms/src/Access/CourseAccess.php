<?php
/**
 * Central course-content access checks.
 *
 * @package GhostLMS\Access
 */

declare(strict_types=1);

namespace GhostLMS\Access;

use GhostLMS\Access\Capabilities;
use GhostLMS\Database\EnrollmentRepository;
use WP_Error;
use WP_REST_Request;

final class CourseAccess
{
    /**
     * Determine whether a user can view course content.
     *
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @return bool
     */
    public static function can_view_course_content(int $user_id, int $course_id): bool
    {
        if ($user_id <= 0 || $course_id <= 0) {
            return false;
        }

        $course = get_post($course_id);

        if (! $course || 'glms_course' !== $course->post_type) {
            return false;
        }

        if ('publish' === $course->post_status && EnrollmentRepository::is_enrolled($user_id, $course_id)) {
            return true;
        }

        if ($user_id === get_current_user_id() && Capabilities::current_user_can_manage_course($course_id)) {
            return true;
        }

        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        return (int) $course->post_author === $user_id;
    }

    /**
     * Validate a course request for the access-check REST route.
     *
     * The route is intentionally readable by any visitor so it can return a
     * false result instead of turning a normal access miss into a REST 403.
     *
     * @param WP_REST_Request $request REST request.
     * @return bool|WP_Error
     */
    public static function rest_permission_callback(WP_REST_Request $request): bool|WP_Error
    {
        $course_id = absint($request->get_param('id'));

        if ($course_id <= 0 || 'glms_course' !== get_post_type($course_id)) {
            return new WP_Error(
                'ghost_lms_invalid_course',
                __('The requested course does not exist.', 'ghost-lms'),
                ['status' => 404]
            );
        }

        return true;
    }

    /**
     * Determine whether the current user can view course content.
     *
     * @param int $course_id Course post ID.
     * @return bool
     */
    public static function current_user_can_view(int $course_id): bool
    {
        return self::can_view_course_content(get_current_user_id(), $course_id);
    }
}
