<?php
/**
 * REST endpoint for lesson completion.
 *
 * @package GhostLMS\Rest
 */

declare(strict_types=1);

namespace GhostLMS\Rest;

use GhostLMS\Access\CourseAccess;
use GhostLMS\Database\LessonProgressRepository;
use GhostLMS\Frontend\LessonPlayerController;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class LessonProgressController
{
    public function register(): void
    {
        register_rest_route(
            'ghost-lms/v1',
            '/lessons/(?P<id>\d+)/complete',
            [
                'methods' => 'POST',
                'callback' => [$this, 'complete'],
                'permission_callback' => [$this, 'permission_callback'],
                'args' => [
                    'id' => ['sanitize_callback' => 'absint'],
                    'course_id' => ['sanitize_callback' => 'absint'],
                ],
            ]
        );
    }

    public function permission_callback(WP_REST_Request $request): bool|WP_Error
    {
        if (! is_user_logged_in()) {
            return new WP_Error('ghost_lms_login_required', __('You must be logged in to complete a lesson.', 'ghost-lms'), ['status' => 401]);
        }

        $lesson_id = absint($request->get_param('id'));
        $course_id = absint($request->get_param('course_id'));
        if ($course_id <= 0 || ! LessonPlayerController::lesson_belongs_to_course($lesson_id, $course_id) || ! CourseAccess::current_user_can_view($course_id)) {
            return new WP_Error('ghost_lms_forbidden', __('You do not have access to this lesson.', 'ghost-lms'), ['status' => 403]);
        }

        return true;
    }

    public function complete(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $lesson_id = absint($request->get_param('id'));
        $course_id = absint($request->get_param('course_id'));
        if (! LessonProgressRepository::mark_complete(get_current_user_id(), $lesson_id, $course_id)) {
            return new WP_Error('ghost_lms_progress_save_failed', __('The lesson completion could not be saved.', 'ghost-lms'), ['status' => 500]);
        }

        return new WP_REST_Response(['completed' => true], 200);
    }
}