<?php
/**
 * REST endpoint for course progress.
 *
 * @package GhostLMS\Rest
 */

declare(strict_types=1);

namespace GhostLMS\Rest;

use GhostLMS\Access\Capabilities;
use GhostLMS\Access\CourseAccess;
use GhostLMS\Database\LessonProgressRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class CourseProgressController
{
    /**
     * Register the course progress route.
     *
     * @return void
     */
    public function register(): void
    {
        register_rest_route(
            'ghost-lms/v1',
            '/courses/(?P<id>\d+)/progress',
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_progress'],
                'permission_callback' => [$this, 'permission_callback'],
                'args' => [
                    'id' => ['sanitize_callback' => 'absint'],
                    'user_id' => ['sanitize_callback' => 'absint'],
                ],
            ]
        );
    }

    /**
     * Check whether the current user can view the requested progress.
     *
     * @param WP_REST_Request $request REST request.
     * @return bool|WP_Error
     */
    public function permission_callback(WP_REST_Request $request): bool|WP_Error
    {
        if (! is_user_logged_in()) {
            return new WP_Error('ghost_lms_login_required', __('You must be logged in to view course progress.', 'ghost-lms'), ['status' => 401]);
        }

        $course_id = absint($request->get_param('id'));
        $course = get_post($course_id);
        if (! $course || 'glms_course' !== $course->post_type) {
            return new WP_Error('ghost_lms_invalid_course', __('The requested course does not exist.', 'ghost-lms'), ['status' => 404]);
        }

        $requested_user_id = absint($request->get_param('user_id'));
        if ($requested_user_id > 0 && ! Capabilities::current_user_can_manage_course($course_id)) {
            return new WP_Error('ghost_lms_forbidden', __('You cannot view another user\'s progress for this course.', 'ghost-lms'), ['status' => 403]);
        }

        if (0 === $requested_user_id && ! CourseAccess::current_user_can_view($course_id)) {
            return new WP_Error('ghost_lms_forbidden', __('You do not have access to this course.', 'ghost-lms'), ['status' => 403]);
        }

        return true;
    }

    /**
     * Return progress for the current user or an authorized requested user.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response
     */
    public function get_progress(WP_REST_Request $request): WP_REST_Response
    {
        $course_id = absint($request->get_param('id'));
        $user_id = absint($request->get_param('user_id')) ?: get_current_user_id();

        return new WP_REST_Response(
            [
                'completed_lesson_ids' => LessonProgressRepository::get_completed_lesson_ids($user_id, $course_id),
                'percent_complete' => LessonProgressRepository::get_percent_complete($user_id, $course_id),
                'completed_at' => LessonProgressRepository::get_completed_at($user_id, $course_id),
            ],
            200
        );
    }
}