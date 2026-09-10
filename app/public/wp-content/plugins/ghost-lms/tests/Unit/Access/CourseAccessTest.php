<?php
/**
 * Tests for the central course-content access layer.
 *
 * @package GhostLMS\Tests
 */

declare(strict_types=1);

namespace GhostLMS\Tests\Unit\Access;

use GhostLMS\Access\CourseAccess;
use GhostLMS\Database\EnrollmentRepository;
use GhostLMS\Rest\AccessCheckController;
use WP_REST_Request;
use WP_UnitTestCase;

final class CourseAccessTest extends WP_UnitTestCase
{
    private int $course_id;

    private int $student_id;

    private int $instructor_id;

    private int $other_instructor_id;

    private int $administrator_id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course_id = self::factory()->post->create(
            [
                'post_type' => 'glms_course',
                'post_author' => self::factory()->user->create(['role' => 'subscriber']),
                'post_status' => 'publish',
            ]
        );
        $this->student_id = self::factory()->user->create(['role' => 'subscriber']);
        $this->instructor_id = self::factory()->user->create(['role' => 'glms_instructor']);
        $this->other_instructor_id = self::factory()->user->create(['role' => 'glms_instructor']);
        $this->administrator_id = self::factory()->user->create(['role' => 'administrator']);

        wp_update_post(
            [
                'ID' => $this->course_id,
                'post_author' => $this->instructor_id,
            ]
        );
    }

    public function test_active_enrollment_grants_access(): void
    {
        EnrollmentRepository::create_or_reactivate($this->student_id, $this->course_id, 0);

        self::assertTrue(CourseAccess::can_view_course_content($this->student_id, $this->course_id));
    }

    public function test_owning_instructor_grants_access(): void
    {
        self::assertTrue(CourseAccess::can_view_course_content($this->instructor_id, $this->course_id));
    }

    public function test_administrator_grants_access(): void
    {
        self::assertTrue(CourseAccess::can_view_course_content($this->administrator_id, $this->course_id));
    }

    public function test_logged_in_non_enrolled_user_is_denied(): void
    {
        self::assertFalse(CourseAccess::can_view_course_content($this->student_id, $this->course_id));
    }

    public function test_expired_active_enrollment_is_denied(): void
    {
        global $wpdb;

        EnrollmentRepository::create_or_reactivate($this->student_id, $this->course_id, 0);
        $wpdb->update(
            $wpdb->prefix . 'glms_enrollments',
            ['expires_at' => gmdate('Y-m-d H:i:s', time() - HOUR_IN_SECONDS)],
            [
                'user_id' => $this->student_id,
                'course_id' => $this->course_id,
            ],
            ['%s'],
            ['%d', '%d']
        );

        self::assertFalse(CourseAccess::can_view_course_content($this->student_id, $this->course_id));
    }

    public function test_different_instructor_is_denied(): void
    {
        self::assertFalse(CourseAccess::can_view_course_content($this->other_instructor_id, $this->course_id));
    }

    public function test_logged_out_visitor_is_denied(): void
    {
        self::assertFalse(CourseAccess::can_view_course_content(0, $this->course_id));
    }

    public function test_access_check_route_matches_direct_result_for_enrolled_user(): void
    {
        EnrollmentRepository::create_or_reactivate($this->student_id, $this->course_id, 0);
        wp_set_current_user($this->student_id);

        $response = $this->dispatch_access_check_request();

        self::assertSame(['can_view' => true], $response->get_data());
    }

    public function test_access_check_route_matches_direct_result_for_non_enrolled_user(): void
    {
        wp_set_current_user($this->student_id);

        $response = $this->dispatch_access_check_request();

        self::assertSame(['can_view' => false], $response->get_data());
    }

    private function dispatch_access_check_request(): \WP_REST_Response
    {
        $controller = new AccessCheckController();
        $controller->register();

        $request = new WP_REST_Request('GET', '/ghost-lms/v1/courses/' . $this->course_id . '/access-check');

        return rest_get_server()->dispatch($request);
    }
}
