<?php
/**
 * Frontend lesson player route and renderer.
 *
 * @package GhostLMS\Frontend
 */

declare(strict_types=1);

namespace GhostLMS\Frontend;

use GhostLMS\Access\Capabilities;
use GhostLMS\Access\CourseAccess;
use GhostLMS\Access\LessonUnlockResolver;
use GhostLMS\Curriculum\CurriculumRepository;
use GhostLMS\Database\LessonProgressRepository;

final class LessonPlayerController
{
    public function register(): void
    {
        self::register_rewrite_rules();
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_action('template_redirect', [$this, 'render_route']);
    }

    public static function register_rewrite_rules(): void
    {
        add_rewrite_rule(
            '^learn/([^/]+)/([0-9]+)/?$',
            'index.php?glms_learn_course=$matches[1]&glms_learn_lesson=$matches[2]',
            'top'
        );
    }

    public function register_query_vars(array $vars): array
    {
        $vars[] = 'glms_learn_course';
        $vars[] = 'glms_learn_lesson';

        return $vars;
    }

    public function render_route(): void
    {
        $course_slug = get_query_var('glms_learn_course');
        $lesson_id = absint(get_query_var('glms_learn_lesson'));

        if (! is_string($course_slug) || '' === $course_slug || $lesson_id <= 0) {
            return;
        }

        $course = get_page_by_path(sanitize_title($course_slug), OBJECT, 'glms_course');
        $lesson = get_post($lesson_id);
        $can_manage_course = $course && Capabilities::current_user_can_manage_course((int) $course->ID);
        if (! $course || ! $lesson || 'glms_lesson' !== $lesson->post_type || ! self::lesson_belongs_to_course($lesson_id, (int) $course->ID) || (! $can_manage_course && ('publish' !== $course->post_status || 'publish' !== $lesson->post_status))) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        if (! is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(home_url('/learn/' . $course_slug . '/' . $lesson_id . '/')));
            exit;
        }

        if (! CourseAccess::current_user_can_view($course->ID)) {
            wp_safe_redirect(add_query_arg('glms_notice', 'enroll', get_permalink($course->ID)));
            exit;
        }

        if (! LessonUnlockResolver::is_unlocked(get_current_user_id(), $lesson_id)) {
            $first_incomplete_lesson_id = LessonUnlockResolver::get_first_incomplete_lesson_id(get_current_user_id(), $course->ID);
            $redirect_url = 0 !== $first_incomplete_lesson_id ? home_url('/learn/' . $course->post_name . '/' . $first_incomplete_lesson_id . '/') : get_permalink($course->ID);
            wp_safe_redirect(add_query_arg('glms_notice', 'locked', $redirect_url));
            exit;
        }

        $this->render_player($course->ID, $lesson_id);
        exit;
    }

    public static function get_course_id_for_lesson(int $lesson_id): int
    {
        $course_ids = array_map('absint', (array) get_post_meta($lesson_id, '_glms_course_ids', true));

        return (int) ($course_ids[0] ?? 0);
    }

    public static function lesson_belongs_to_course(int $lesson_id, int $course_id): bool
    {
        foreach (CurriculumRepository::get($course_id) as $module) {
            foreach ($module['lessons'] as $lesson) {
                if ($lesson_id === (int) $lesson['lesson_id']) {
                    return true;
                }
            }
        }

        return false;
    }

    private function render_player(int $course_id, int $lesson_id): void
    {
        $course = get_post($course_id);
        $lesson = get_post($lesson_id);
        if (! $course || ! $lesson) {
            return;
        }

        $completed_ids = LessonProgressRepository::get_completed_lesson_ids(get_current_user_id(), $course_id);
        $curriculum = CurriculumRepository::get($course_id);

        wp_enqueue_style('ghost-lms-lesson-player', GHOST_LMS_PLUGIN_URL . 'assets/lesson-player.css', [], GHOST_LMS_VERSION);
        wp_enqueue_script('ghost-lms-lesson-player', GHOST_LMS_PLUGIN_URL . 'assets/lesson-player.js', [], GHOST_LMS_VERSION, true);
        wp_localize_script(
            'ghost-lms-lesson-player',
            'ghostLmsLessonPlayer',
            [
                'completeUrl' => rest_url('ghost-lms/v1/lessons/' . $lesson_id . '/complete'),
                'nonce' => wp_create_nonce('wp_rest'),
                'courseId' => $course_id,
                'completedLabel' => __('Completed', 'ghost-lms'),
            ]
        );

        get_header();
        echo '<div class="glms-root glms-lesson-player">';
        echo '<a class="glms-lesson-player__back" href="' . esc_url(get_permalink($course_id)) . '">' . esc_html__('Back to course', 'ghost-lms') . '</a>';
        echo '<div class="glms-lesson-player__layout">';
        echo '<aside class="glms-lesson-player__sidebar" aria-label="' . esc_attr__('Course curriculum', 'ghost-lms') . '">';
        echo '<h2>' . esc_html($course->post_title) . '</h2>';
        foreach ($curriculum as $module) {
            echo '<section class="glms-lesson-player__module">';
            echo '<h3>' . esc_html($module['title']) . '</h3><ul>';
            foreach ($module['lessons'] as $curriculum_lesson) {
                $curriculum_lesson_id = (int) $curriculum_lesson['lesson_id'];
                $is_completed = in_array($curriculum_lesson_id, $completed_ids, true);
                $is_unlocked = LessonUnlockResolver::is_unlocked(get_current_user_id(), $curriculum_lesson_id);
                $class = $curriculum_lesson_id === $lesson_id ? ' is-current' : '';
                echo '<li class="glms-lesson-player__lesson' . esc_attr($class) . '">';
                if ($is_unlocked) {
                    echo '<a href="' . esc_url(home_url('/learn/' . $course->post_name . '/' . $curriculum_lesson_id . '/')) . '" aria-disabled="false">';
                } else {
                    echo '<span aria-disabled="true" style="cursor: not-allowed; opacity: 0.7; display: inline-block;">';
                }
                echo '<span class="glms-lesson-player__status" aria-hidden="true">' . ($is_completed ? '&#10003;' : ($is_unlocked ? '' : '&#128274;')) . '</span>';
                echo esc_html($curriculum_lesson['title']);
                if ($is_unlocked) {
                    echo '</a>';
                } else {
                    echo '</span>';
                }
                echo '</li>';
            }
            echo '</ul></section>';
        }
        echo '</aside>';
        echo '<main class="glms-lesson-player__content">';
        echo '<h1>' . esc_html($lesson->post_title) . '</h1>';
        echo '<div class="glms-lesson-player__body">' . wp_kses_post(apply_filters('the_content', $lesson->post_content)) . '</div>';

        $video_url = get_post_meta($lesson_id, '_glms_video_url', true);
        if (is_string($video_url) && '' !== $video_url) {
            $embed = wp_oembed_get($video_url);
            if (is_string($embed) && '' !== $embed) {
                echo '<div class="glms-lesson-player__video">' . wp_kses_post($embed) . '</div>';
            }
        }

        $attachment_id = absint(get_post_meta($lesson_id, '_glms_attachment_id', true));
        if ($attachment_id > 0) {
            $attachment_url = add_query_arg(
                [
                    'course_id' => $course_id,
                    '_wpnonce' => wp_create_nonce('wp_rest'),
                ],
                rest_url('ghost-lms/v1/lessons/' . $lesson_id . '/attachment')
            );
            echo '<p><a class="glms-lesson-player__attachment" href="' . esc_url($attachment_url) . '">' . esc_html__('Download lesson attachment', 'ghost-lms') . '</a></p>';
        }

        $is_complete = in_array($lesson_id, $completed_ids, true);
        echo '<button type="button" class="glms-lesson-player__complete" data-lesson-id="' . esc_attr((string) $lesson_id) . '"' . disabled($is_complete, true, false) . '>' . esc_html($is_complete ? __('Completed', 'ghost-lms') : __('Mark Complete', 'ghost-lms')) . '</button>';
        echo '</main></div></div>';
        get_footer();
    }
}