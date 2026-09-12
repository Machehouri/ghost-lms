<?php
/**
 * Gated lesson attachment download endpoint.
 *
 * @package GhostLMS\Rest
 */

declare(strict_types=1);

namespace GhostLMS\Rest;

use GhostLMS\Access\CourseAccess;
use GhostLMS\Frontend\LessonPlayerController;
use WP_Error;
use WP_REST_Request;

final class LessonAttachmentController
{
    public function register(): void
    {
        register_rest_route(
            'ghost-lms/v1',
            '/lessons/(?P<id>\d+)/attachment',
            [
                'methods' => 'GET',
                'callback' => [$this, 'download'],
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
        $lesson_id = absint($request->get_param('id'));
        $course_id = absint($request->get_param('course_id'));
        $course = get_post($course_id);
        $lesson = get_post($lesson_id);
        $can_manage_course = $course_id > 0 && \GhostLMS\Access\Capabilities::current_user_can_manage_course($course_id);
        if (! $course || 'glms_course' !== $course->post_type || ! $lesson || 'glms_lesson' !== $lesson->post_type || ! LessonPlayerController::lesson_belongs_to_course($lesson_id, $course_id) || (! $can_manage_course && ('publish' !== $course->post_status || 'publish' !== $lesson->post_status)) || ! CourseAccess::current_user_can_view($course_id)) {
            return new WP_Error('ghost_lms_forbidden', __('You do not have access to this attachment.', 'ghost-lms'), ['status' => 403]);
        }

        return true;
    }

    public function download(WP_REST_Request $request): void
    {
        $lesson_id = absint($request->get_param('id'));
        $course_id = absint($request->get_param('course_id'));
        $course = get_post($course_id);
        $lesson = get_post($lesson_id);
        $can_manage_course = $course_id > 0 && \GhostLMS\Access\Capabilities::current_user_can_manage_course($course_id);
        if (! $course || 'glms_course' !== $course->post_type || ! $lesson || 'glms_lesson' !== $lesson->post_type || ! LessonPlayerController::lesson_belongs_to_course($lesson_id, $course_id) || (! $can_manage_course && ('publish' !== $course->post_status || 'publish' !== $lesson->post_status))) {
            wp_die(esc_html__('You do not have access to this attachment.', 'ghost-lms'), '', ['response' => 403]);
        }

        $attachment_id = absint(get_post_meta($lesson_id, '_glms_attachment_id', true));
        $url = $attachment_id > 0 ? wp_get_attachment_url($attachment_id) : false;

        if (! is_string($url) || '' === $url) {
            wp_die(esc_html__('The requested attachment is unavailable.', 'ghost-lms'), '', ['response' => 404]);
        }

        wp_safe_redirect($url);
        exit;
    }
}