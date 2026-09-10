<?php
/**
 * REST endpoint for checking course-content access.
 *
 * @package GhostLMS\Rest
 */

declare(strict_types=1);

namespace GhostLMS\Rest;

use GhostLMS\Access\CourseAccess;
use WP_REST_Request;
use WP_REST_Response;

final class AccessCheckController
{
    /**
     * Register the access-check route.
     *
     * @return void
     */
    public function register(): void
    {
        register_rest_route(
            'ghost-lms/v1',
            '/courses/(?P<id>\d+)/access-check',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'check_access'],
                'permission_callback' => [CourseAccess::class, 'rest_permission_callback'],
                'args'                => [
                    'id' => [
                        'sanitize_callback' => 'absint',
                        'validate_callback' => static fn (mixed $value): bool => absint($value) > 0,
                    ],
                ],
            ]
        );
    }

    /**
     * Return the current user's access result for a course.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response
     */
    public function check_access(WP_REST_Request $request): WP_REST_Response
    {
        $course_id = absint($request->get_param('id'));

        return new WP_REST_Response(
            [
                'can_view' => CourseAccess::current_user_can_view($course_id),
            ],
            200
        );
    }
}
