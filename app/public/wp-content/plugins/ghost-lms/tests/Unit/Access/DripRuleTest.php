<?php
/**
 * Tests for drip-rule gating.
 *
 * @package GhostLMS\Tests
 */

declare(strict_types=1);

namespace GhostLMS\Tests\Unit\Access;

use GhostLMS\Access\DripRule;
use GhostLMS\Access\DripRuleValidator;
use GhostLMS\Access\LessonUnlockResolver;
use GhostLMS\Database\EnrollmentRepository;
use GhostLMS\Database\LessonProgressRepository;
use GhostLMS\Rest\LessonDripRuleController;
use WP_Error;
use WP_REST_Request;
use WP_UnitTestCase;

final class DripRuleTest extends WP_UnitTestCase
{
    private int $course_id;

    private int $student_id;

    private int $instructor_id;

    private int $lesson_1_id;

    private int $lesson_2_id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student_id = self::factory()->user->create(['role' => 'subscriber']);
        $this->instructor_id = self::factory()->user->create(['role' => 'glms_instructor']);

        $this->course_id = self::factory()->post->create(
            [
                'post_type' => 'glms_course',
                'post_status' => 'publish',
                'post_author' => $this->instructor_id,
                'post_title' => 'Drip course',
            ]
        );

        $this->lesson_1_id = self::factory()->post->create(
            [
                'post_type' => 'glms_lesson',
                'post_status' => 'publish',
                'post_title' => 'Lesson 1',
            ]
        );
        $this->lesson_2_id = self::factory()->post->create(
            [
                'post_type' => 'glms_lesson',
                'post_status' => 'publish',
                'post_title' => 'Lesson 2',
            ]
        );

        update_post_meta(
            $this->course_id,
            '_glms_curriculum',
            [
                [
                    'module_id' => 'module-1',
                    'title' => 'Module 1',
                    'order' => 0,
                    'lessons' => [
                        ['lesson_id' => $this->lesson_1_id, 'order' => 0],
                        ['lesson_id' => $this->lesson_2_id, 'order' => 1],
                    ],
                ],
            ]
        );

        update_post_meta($this->lesson_1_id, '_glms_course_ids', [$this->course_id]);
        update_post_meta($this->lesson_2_id, '_glms_course_ids', [$this->course_id]);
    }

    public function test_fixed_date_drip_rule_enforces_site_time(): void
    {
        update_post_meta($this->lesson_2_id, '_glms_drip_rule', wp_json_encode(['type' => 'fixed_date', 'date' => gmdate('Y-m-d\TH:i:s', time() + 60)]));
        EnrollmentRepository::create_or_reactivate($this->student_id, $this->course_id, 0);

        self::assertFalse(LessonUnlockResolver::is_unlocked($this->student_id, $this->lesson_2_id));
    }

    public function test_days_after_enrollment_rule_uses_real_enrollment_timestamp(): void
    {
        global $wpdb;

        EnrollmentRepository::create_or_reactivate($this->student_id, $this->course_id, 0);
        $wpdb->update(
            $wpdb->prefix . 'glms_enrollments',
            ['enrolled_at' => gmdate('Y-m-d H:i:s', time() - (7 * DAY_IN_SECONDS) - 60)],
            ['user_id' => $this->student_id, 'course_id' => $this->course_id],
            ['%s'],
            ['%d', '%d']
        );
        update_post_meta($this->lesson_2_id, '_glms_drip_rule', wp_json_encode(['type' => 'days_after_enrollment', 'days' => 7]));

        self::assertTrue(LessonUnlockResolver::is_unlocked($this->student_id, $this->lesson_2_id));
    }

    public function test_prerequisite_rule_requires_completed_lesson(): void
    {
        update_post_meta($this->lesson_2_id, '_glms_drip_rule', wp_json_encode(['type' => 'prerequisite', 'lesson_id' => $this->lesson_1_id]));

        self::assertFalse(LessonUnlockResolver::is_unlocked($this->student_id, $this->lesson_2_id));

        LessonProgressRepository::mark_complete($this->student_id, $this->lesson_1_id, $this->course_id);
        self::assertTrue(LessonUnlockResolver::is_unlocked($this->student_id, $this->lesson_2_id));
    }

    public function test_drip_rule_describes_itself(): void
    {
        $rule = DripRule::from_value(wp_json_encode(['type' => 'fixed_date', 'date' => '2027-01-01T00:00:00']));

        self::assertStringContainsString('Unlocks', $rule->describe());
    }

    public function test_prerequisite_must_appear_earlier_in_curriculum(): void
    {
        $result = DripRuleValidator::validate(
            $this->lesson_1_id,
            wp_json_encode(['type' => 'prerequisite', 'lesson_id' => $this->lesson_2_id])
        );

        self::assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * Verify that an instructor can save and clear a lesson drip rule.
     *
     * @return void
     */
    public function test_drip_rule_controller_can_save_and_clear_a_rule(): void
    {
        wp_set_current_user($this->instructor_id);
        $controller = new LessonDripRuleController();

        $save_request = new WP_REST_Request('PUT', '/ghost-lms/v1/lessons/' . $this->lesson_2_id . '/drip-rule');
        $save_request->set_param('id', $this->lesson_2_id);
        $save_request->set_body(wp_json_encode(['type' => 'days_after_enrollment', 'days' => 3]));
        $save_response = $controller->save($save_request);

        self::assertSame(200, $save_response->get_status());
        self::assertSame('days_after_enrollment', DripRule::from_lesson($this->lesson_2_id)->get_type());

        $clear_request = new WP_REST_Request('PUT', '/ghost-lms/v1/lessons/' . $this->lesson_2_id . '/drip-rule');
        $clear_request->set_param('id', $this->lesson_2_id);
        $clear_request->set_body('null');
        $clear_response = $controller->save($clear_request);

        self::assertSame(200, $clear_response->get_status());
        self::assertNull(DripRule::from_lesson($this->lesson_2_id));
    }

    /**
     * Verify that a non-owner cannot manage a lesson drip rule.
     *
     * @return void
     */
    public function test_non_owner_cannot_manage_a_drip_rule(): void
    {
        wp_set_current_user($this->student_id);
        $request = new WP_REST_Request('PUT', '/ghost-lms/v1/lessons/' . $this->lesson_2_id . '/drip-rule');
        $request->set_param('id', $this->lesson_2_id);
        $controller = new LessonDripRuleController();

        self::assertInstanceOf(WP_Error::class, $controller->permission_callback($request));
    }
}
