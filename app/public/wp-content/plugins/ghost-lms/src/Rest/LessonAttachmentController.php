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
                'args' => ['id' => ['sanitize_callback' => 'absint']],
            ]
        );
    }

    public function permission_callback(WP_REST_Request $request): bool|WP_Error
    {
        $course_id = LessonPlayerController::get_course_id_for_lesson(absint($request->get_param('id')));
        if ($course_id <= 0 || ! CourseAccess::current_user_can_view($course_id)) {
            return new WP_Error('ghost_lms_forbidden', __('You do not have access to this attachment.', 'ghost-lms'), ['status' => 403]);
        }

        return true;
    }

    public function download(WP_REST_Request $request): void
    {
        $lesson_id = absint($request->get_param('id'));
        $attachment_id = absint(get_post_meta($lesson_id, '_glms_attachment_id', true));
        $url = $attachment_id > 0 ? wp_get_attachment_url($attachment_id) : false;

        if (! is_string($url) || '' === $url) {
            wp_die(esc_html__('The requested attachment is unavailable.', 'ghost-lms'), '', ['response' => 404]);
        }

        wp_safe_redirect($url);
        exit;
    }
}