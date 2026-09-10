<?php
/**
 * Public-facing enrolled courses dashboard renderer.
 *
 * @package GhostLMS\Blocks
 */

declare(strict_types=1);

namespace GhostLMS\Blocks;

use GhostLMS\Access\ProgressStub;
use GhostLMS\Database\EnrollmentRepository;
use WP_Post;

final class MyCoursesBlock
{
    public function register(): void
    {
        add_action('init', [$this, 'register_block']);
    }

    public function register_block(): void
    {
        register_block_type(
            GHOST_LMS_PLUGIN_DIR . 'assets/blocks/my-courses',
            [
                'render_callback' => [$this, 'render'],
            ]
        );
    }

    public function render(array $attributes = [], string $content = '', $block = null): string
    {
        return self::render_dashboard_html();
    }

    public static function render_dashboard_html(): string
    {
        if (! is_user_logged_in()) {
            $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            wp_safe_redirect(wp_login_url($request_uri));
            return '';
        }

        $enrollments = EnrollmentRepository::get_active_for_user(get_current_user_id());

        ob_start();
        echo '<div class="glms-root glms-my-courses">';
        echo '<style>
            .glms-my-courses { color: var(--glms-color-text, #1f2937); font-family: var(--glms-font-body, system-ui, -apple-system, "Segoe UI", sans-serif); }
            .glms-my-courses__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; }
            .glms-my-courses__card { display: flex; flex-direction: column; gap: 16px; padding: 18px; border: 1px solid var(--glms-color-border, #e5e7eb); border-radius: var(--glms-radius, 8px); background: var(--glms-color-bg, #fff); }
            .glms-my-courses__title { margin: 0; font-size: 1.2rem; }
            .glms-my-courses__title a, .glms-my-courses__button { color: var(--glms-color-primary, #2563eb); }
            .glms-my-courses__progress { display: grid; gap: 8px; }
            .glms-my-courses__progress-track { height: 8px; overflow: hidden; border-radius: 999px; background: var(--glms-color-bg-subtle, #f9fafb); }
            .glms-my-courses__progress-fill { height: 100%; background: var(--glms-color-primary, #2563eb); }
            .glms-my-courses__button { display: inline-block; align-self: flex-start; padding: 10px 14px; border-radius: var(--glms-radius, 8px); background: var(--glms-color-primary, #2563eb); color: #fff; text-decoration: none; }
            .glms-my-courses__empty { display: grid; gap: 16px; }
        </style>';

        if (empty($enrollments)) {
            $catalog_url = get_post_type_archive_link('glms_course') ?: home_url('/courses/');
            echo '<div class="glms-my-courses__empty">';
            echo '<p>' . esc_html__('You are not enrolled in any courses yet.', 'ghost-lms') . '</p>';
            echo '<a class="glms-my-courses__button" href="' . esc_url($catalog_url) . '">' . esc_html__('Browse courses', 'ghost-lms') . '</a>';
            echo '</div></div>';
            return ob_get_clean();
        }

        echo '<div class="glms-my-courses__grid">';
        foreach ($enrollments as $enrollment) {
            $course = get_post((int) $enrollment->course_id);
            if (! $course instanceof WP_Post || 'glms_course' !== $course->post_type || 'publish' !== $course->post_status) {
                continue;
            }

            $course_id = (int) $course->ID;
            $progress = ProgressStub::get_course_progress_percent(get_current_user_id(), $course_id);
            $course_url = get_permalink($course);
            echo '<article class="glms-my-courses__card">';
            echo '<h2 class="glms-my-courses__title"><a href="' . esc_url($course_url) . '">' . esc_html(get_the_title($course)) . '</a></h2>';
            echo '<div class="glms-my-courses__progress" aria-label="' . esc_attr(sprintf(__('Course progress: %d%%', 'ghost-lms'), $progress)) . '">';
            echo '<span>' . esc_html(sprintf(__('%d%% complete', 'ghost-lms'), $progress)) . '</span>';
            echo '<div class="glms-my-courses__progress-track"><div class="glms-my-courses__progress-fill" style="width: ' . esc_attr((string) $progress) . '%"></div></div>';
            echo '</div>';
            echo '<a class="glms-my-courses__button" href="' . esc_url($course_url) . '">' . esc_html__('Continue', 'ghost-lms') . '</a>';
            echo '</article>';
        }
        echo '</div></div>';

        return ob_get_clean();
    }
}