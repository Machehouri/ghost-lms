<?php
/**
 * Tests for the lesson unlock sequencing rules.
 *
 * @package GhostLMS\Tests
 */

declare(strict_types=1);

namespace GhostLMS\Tests\Unit\Access;

use GhostLMS\Access\LessonUnlockResolver;
use GhostLMS\Database\LessonProgressRepository;
use WP_UnitTestCase;

final class LessonUnlockResolverTest extends WP_UnitTestCase
{
    private int $course_id;

    private int $student_id;

    private int $instructor_id;

    private int $admin_id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor_id = self::factory()->user->create(['role' => 'glms_instructor']);
        $this->student_id = self::factory()->user->create(['role' => 'subscriber']);
        $this->admin_id = self::factory()->user->create(['role' => 'administrator']);

        $this->course_id = self::factory()->post->create(
            [
                'post_type' => 'glms_course',
                'post_author' => $this->instructor_id,
                'post_status' => 'publish',
                'post_title' => 'Sequenced Course',
            ]
        );

        $lesson_1 = self::factory()->post->create(
            [
                'post_type' => 'glms_lesson',
                'post_status' => 'publish',
                'post_title' => 'Lesson 1',
            ]
        );
        $lesson_2 = self::factory()->post->create(
            [
                'post_type' => 'glms_lesson',
                'post_status' => 'publish',
                'post_title' => 'Lesson 2',
            ]
        );
        $lesson_3 = self::factory()->post->create(
            [
                'post_type' => 'glms_lesson',
                'post_status' => 'publish',
                'post_title' => 'Lesson 3',
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
                        ['lesson_id' => $lesson_1, 'order' => 0],
                        ['lesson_id' => $lesson_2, 'order' => 1],
                        ['lesson_id' => $lesson_3, 'order' => 2],
                    ],
                ],
            ]
        );
    }

    public function test_sequence_disabled_unlocks_every_lesson_for_students(): void
    {
        LessonProgressRepository::mark_complete($this->student_id, $this->get_lesson_id(1), $this->course_id);

        self::assertTrue(LessonUnlockResolver::is_unlocked($this->student_id, $this->get_lesson_id(3)));
    }

    public function test_sequence_enabled_blocks_lesson_after_first_incomplete(): void
    {
        update_post_meta($this->course_id, '_glms_sequential_progression', '1');
        LessonProgressRepository::mark_complete($this->student_id, $this->get_lesson_id(1), $this->course_id);

        self::assertTrue(LessonUnlockResolver::is_unlocked($this->student_id, $this->get_lesson_id(1)));
        self::assertTrue(LessonUnlockResolver::is_unlocked($this->student_id, $this->get_lesson_id(2)));
        self::assertFalse(LessonUnlockResolver::is_unlocked($this->student_id, $this->get_lesson_id(3)));
    }

    public function test_instructor_and_admin_are_not_blocked_by_sequence_rules(): void
    {
        update_post_meta($this->course_id, '_glms_sequential_progression', '1');
        LessonProgressRepository::mark_complete($this->student_id, $this->get_lesson_id(1), $this->course_id);

        self::assertTrue(LessonUnlockResolver::is_unlocked($this->instructor_id, $this->get_lesson_id(3)));
        self::assertTrue(LessonUnlockResolver::is_unlocked($this->admin_id, $this->get_lesson_id(3)));
    }

    private function get_lesson_id(int $order): int
    {
        $curriculum = get_post_meta($this->course_id, '_glms_curriculum', true);
        $lessons = $curriculum[0]['lessons'] ?? [];

        return absint($lessons[$order - 1]['lesson_id'] ?? 0);
    }
}
