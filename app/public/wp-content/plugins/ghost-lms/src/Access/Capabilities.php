<?php
/**
 * Capability and role registration for Ghost LMS.
 *
 * @package GhostLMS\Access
 */

declare(strict_types=1);

namespace GhostLMS\Access;

final class Capabilities
{
    public const ROLE_INSTRUCTOR = 'glms_instructor';

    public const COURSE_TYPE = 'glms_course';
    public const LESSON_TYPE = 'glms_lesson';
    public const QUIZ_TYPE = 'glms_quiz';
    public const QUESTION_TYPE = 'glms_question';

    public const EDIT_GLMS_COURSE = 'edit_glms_course';
    public const READ_GLMS_COURSE = 'read_glms_course';
    public const DELETE_GLMS_COURSE = 'delete_glms_course';
    public const EDIT_GLMS_COURSES = 'edit_glms_courses';
    public const EDIT_OTHERS_GLMS_COURSES = 'edit_others_glms_courses';
    public const PUBLISH_GLMS_COURSES = 'publish_glms_courses';
    public const READ_PRIVATE_GLMS_COURSES = 'read_private_glms_courses';
    public const DELETE_GLMS_COURSES = 'delete_glms_courses';
    public const DELETE_PRIVATE_GLMS_COURSES = 'delete_private_glms_courses';
    public const DELETE_PUBLISHED_GLMS_COURSES = 'delete_published_glms_courses';
    public const DELETE_OTHERS_GLMS_COURSES = 'delete_others_glms_courses';
    public const EDIT_PRIVATE_GLMS_COURSES = 'edit_private_glms_courses';
    public const EDIT_PUBLISHED_GLMS_COURSES = 'edit_published_glms_courses';

    public const EDIT_GLMS_LESSON = 'edit_glms_lesson';
    public const READ_GLMS_LESSON = 'read_glms_lesson';
    public const DELETE_GLMS_LESSON = 'delete_glms_lesson';
    public const EDIT_GLMS_LESSONS = 'edit_glms_lessons';
    public const EDIT_OTHERS_GLMS_LESSONS = 'edit_others_glms_lessons';
    public const PUBLISH_GLMS_LESSONS = 'publish_glms_lessons';
    public const READ_PRIVATE_GLMS_LESSONS = 'read_private_glms_lessons';
    public const DELETE_GLMS_LESSONS = 'delete_glms_lessons';
    public const DELETE_PRIVATE_GLMS_LESSONS = 'delete_private_glms_lessons';
    public const DELETE_PUBLISHED_GLMS_LESSONS = 'delete_published_glms_lessons';
    public const DELETE_OTHERS_GLMS_LESSONS = 'delete_others_glms_lessons';
    public const EDIT_PRIVATE_GLMS_LESSONS = 'edit_private_glms_lessons';
    public const EDIT_PUBLISHED_GLMS_LESSONS = 'edit_published_glms_lessons';

    public const EDIT_GLMS_QUIZ = 'edit_glms_quiz';
    public const READ_GLMS_QUIZ = 'read_glms_quiz';
    public const DELETE_GLMS_QUIZ = 'delete_glms_quiz';
    public const EDIT_GLMS_QUIZZES = 'edit_glms_quizzes';
    public const EDIT_OTHERS_GLMS_QUIZZES = 'edit_others_glms_quizzes';
    public const PUBLISH_GLMS_QUIZZES = 'publish_glms_quizzes';
    public const READ_PRIVATE_GLMS_QUIZZES = 'read_private_glms_quizzes';
    public const DELETE_GLMS_QUIZZES = 'delete_glms_quizzes';
    public const DELETE_PRIVATE_GLMS_QUIZZES = 'delete_private_glms_quizzes';
    public const DELETE_PUBLISHED_GLMS_QUIZZES = 'delete_published_glms_quizzes';
    public const DELETE_OTHERS_GLMS_QUIZZES = 'delete_others_glms_quizzes';
    public const EDIT_PRIVATE_GLMS_QUIZZES = 'edit_private_glms_quizzes';
    public const EDIT_PUBLISHED_GLMS_QUIZZES = 'edit_published_glms_quizzes';

    public const EDIT_GLMS_QUESTION = 'edit_glms_question';
    public const READ_GLMS_QUESTION = 'read_glms_question';
    public const DELETE_GLMS_QUESTION = 'delete_glms_question';
    public const EDIT_GLMS_QUESTIONS = 'edit_glms_questions';
    public const EDIT_OTHERS_GLMS_QUESTIONS = 'edit_others_glms_questions';
    public const PUBLISH_GLMS_QUESTIONS = 'publish_glms_questions';
    public const READ_PRIVATE_GLMS_QUESTIONS = 'read_private_glms_questions';
    public const DELETE_GLMS_QUESTIONS = 'delete_glms_questions';
    public const DELETE_PRIVATE_GLMS_QUESTIONS = 'delete_private_glms_questions';
    public const DELETE_PUBLISHED_GLMS_QUESTIONS = 'delete_published_glms_questions';
    public const DELETE_OTHERS_GLMS_QUESTIONS = 'delete_others_glms_questions';
    public const EDIT_PRIVATE_GLMS_QUESTIONS = 'edit_private_glms_questions';
    public const EDIT_PUBLISHED_GLMS_QUESTIONS = 'edit_published_glms_questions';

    public const GLMS_MANAGE_ENROLLMENTS = 'glms_manage_enrollments';
    public const GLMS_VIEW_REPORTS = 'glms_view_reports';

    /**
     * Register WordPress hooks for ownership-aware capability checks.
     *
     * @return void
     */
    public static function register_hooks(): void
    {
        add_filter('map_meta_cap', [self::class, 'map_meta_cap']);
    }

    /**
     * Create the instructor role on activation.
     *
     * @return void
     */
    public static function register_role(): void
    {
        if (get_role(self::ROLE_INSTRUCTOR)) {
            return;
        }

        add_role(
            self::ROLE_INSTRUCTOR,
            __('Instructor', 'ghost-lms'),
            self::instructor_capabilities()
        );
    }

    /**
     * Add all Ghost LMS capability strings to the administrator role.
     *
     * @return void
     */
    public static function add_admin_capabilities(): void
    {
        $administrator = get_role('administrator');

        if (! $administrator) {
            return;
        }

        foreach (self::all_ghost_lms_capabilities() as $capability) {
            $administrator->add_cap($capability);
        }
    }

    /**
     * Check whether the current user can manage a given course.
     *
     * @param int $course_id Course post ID.
     * @return bool
     */
    public static function current_user_can_manage_course(int $course_id): bool
    {
        $user_id = get_current_user_id();

        if (0 === $user_id) {
            return false;
        }

        if (user_can($user_id, 'manage_options')) {
            return true;
        }

        $course = get_post($course_id);

        if (! $course || 'glms_course' !== get_post_type($course)) {
            return false;
        }

        return (int) $course->post_author === $user_id;
    }

    /**
     * Remove Ghost LMS capabilities and the instructor role on uninstall.
     *
     * @return void
     */
    public static function remove_role_and_capabilities(): void
    {
        $administrator = get_role('administrator');

        if ($administrator) {
            foreach (self::all_ghost_lms_capabilities() as $capability) {
                $administrator->remove_cap($capability);
            }
        }

        remove_role(self::ROLE_INSTRUCTOR);
    }

    /**
     * Enforce course ownership for Ghost LMS custom CPT meta caps.
     *
     * @param array<int, string> $caps Primitive capabilities.
     * @param string            $cap  Capability being checked.
     * @param int               $user_id Current user ID.
     * @param array             $args Additional arguments.
     * @return array<int, string>
     */
    public static function map_meta_cap(array $caps, string $cap, int $user_id, array $args): array
    {
        $supported_caps = [
            self::EDIT_GLMS_COURSE,
            self::DELETE_GLMS_COURSE,
            self::READ_GLMS_COURSE,
            self::EDIT_GLMS_LESSON,
            self::DELETE_GLMS_LESSON,
            self::READ_GLMS_LESSON,
            self::EDIT_GLMS_QUIZ,
            self::DELETE_GLMS_QUIZ,
            self::READ_GLMS_QUIZ,
            self::EDIT_GLMS_QUESTION,
            self::DELETE_GLMS_QUESTION,
            self::READ_GLMS_QUESTION,
        ];

        if (! in_array($cap, $supported_caps, true)) {
            return $caps;
        }

        $post_id = (int) ($args[0] ?? 0);
        $post    = get_post($post_id);

        if (! $post || ! in_array($post->post_type, [self::COURSE_TYPE, self::LESSON_TYPE, self::QUIZ_TYPE, self::QUESTION_TYPE], true)) {
            return $caps;
        }

        if (user_can($user_id, 'manage_options')) {
            return ['exist'];
        }

        if ((int) $user_id === (int) $post->post_author) {
            return ['exist'];
        }

        return ['do_not_allow'];
    }

    /**
     * Return the full Ghost LMS capability list.
     *
     * @return string[]
     */
    public static function all_ghost_lms_capabilities(): array
    {
        return [
            self::EDIT_GLMS_COURSE,
            self::READ_GLMS_COURSE,
            self::DELETE_GLMS_COURSE,
            self::EDIT_GLMS_COURSES,
            self::EDIT_OTHERS_GLMS_COURSES,
            self::PUBLISH_GLMS_COURSES,
            self::READ_PRIVATE_GLMS_COURSES,
            self::DELETE_GLMS_COURSES,
            self::DELETE_PRIVATE_GLMS_COURSES,
            self::DELETE_PUBLISHED_GLMS_COURSES,
            self::DELETE_OTHERS_GLMS_COURSES,
            self::EDIT_PRIVATE_GLMS_COURSES,
            self::EDIT_PUBLISHED_GLMS_COURSES,
            self::EDIT_GLMS_LESSON,
            self::READ_GLMS_LESSON,
            self::DELETE_GLMS_LESSON,
            self::EDIT_GLMS_LESSONS,
            self::EDIT_OTHERS_GLMS_LESSONS,
            self::PUBLISH_GLMS_LESSONS,
            self::READ_PRIVATE_GLMS_LESSONS,
            self::DELETE_GLMS_LESSONS,
            self::DELETE_PRIVATE_GLMS_LESSONS,
            self::DELETE_PUBLISHED_GLMS_LESSONS,
            self::DELETE_OTHERS_GLMS_LESSONS,
            self::EDIT_PRIVATE_GLMS_LESSONS,
            self::EDIT_PUBLISHED_GLMS_LESSONS,
            self::EDIT_GLMS_QUIZ,
            self::READ_GLMS_QUIZ,
            self::DELETE_GLMS_QUIZ,
            self::EDIT_GLMS_QUIZZES,
            self::EDIT_OTHERS_GLMS_QUIZZES,
            self::PUBLISH_GLMS_QUIZZES,
            self::READ_PRIVATE_GLMS_QUIZZES,
            self::DELETE_GLMS_QUIZZES,
            self::DELETE_PRIVATE_GLMS_QUIZZES,
            self::DELETE_PUBLISHED_GLMS_QUIZZES,
            self::DELETE_OTHERS_GLMS_QUIZZES,
            self::EDIT_PRIVATE_GLMS_QUIZZES,
            self::EDIT_PUBLISHED_GLMS_QUIZZES,
            self::EDIT_GLMS_QUESTION,
            self::READ_GLMS_QUESTION,
            self::DELETE_GLMS_QUESTION,
            self::EDIT_GLMS_QUESTIONS,
            self::EDIT_OTHERS_GLMS_QUESTIONS,
            self::PUBLISH_GLMS_QUESTIONS,
            self::READ_PRIVATE_GLMS_QUESTIONS,
            self::DELETE_GLMS_QUESTIONS,
            self::DELETE_PRIVATE_GLMS_QUESTIONS,
            self::DELETE_PUBLISHED_GLMS_QUESTIONS,
            self::DELETE_OTHERS_GLMS_QUESTIONS,
            self::EDIT_PRIVATE_GLMS_QUESTIONS,
            self::EDIT_PUBLISHED_GLMS_QUESTIONS,
            self::GLMS_MANAGE_ENROLLMENTS,
            self::GLMS_VIEW_REPORTS,
        ];
    }

    /**
     * Capability map for the glms_instructor role.
     *
     * @return array<string, bool>
     */
    private static function instructor_capabilities(): array
    {
        return [
            self::EDIT_GLMS_COURSES => true,
            self::PUBLISH_GLMS_COURSES => true,
            self::DELETE_GLMS_COURSES => true,
            self::READ_PRIVATE_GLMS_COURSES => true,
            self::EDIT_GLMS_LESSONS => true,
            self::PUBLISH_GLMS_LESSONS => true,
            self::DELETE_GLMS_LESSONS => true,
            self::READ_PRIVATE_GLMS_LESSONS => true,
            self::EDIT_GLMS_QUIZZES => true,
            self::PUBLISH_GLMS_QUIZZES => true,
            self::DELETE_GLMS_QUIZZES => true,
            self::READ_PRIVATE_GLMS_QUIZZES => true,
            self::EDIT_GLMS_QUESTIONS => true,
            self::PUBLISH_GLMS_QUESTIONS => true,
            self::DELETE_GLMS_QUESTIONS => true,
            self::READ_PRIVATE_GLMS_QUESTIONS => true,
            self::GLMS_MANAGE_ENROLLMENTS => true,
            self::GLMS_VIEW_REPORTS => true,
        ];
    }
}
