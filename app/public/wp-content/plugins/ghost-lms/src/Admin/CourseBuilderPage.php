<?php
/**
 * Course builder admin page shell.
 *
 * @package GhostLMS\Admin
 */

declare(strict_types=1);

namespace GhostLMS\Admin;

if (! defined('ABSPATH')) {
    exit;
}

use GhostLMS\Access\Capabilities;

final class CourseBuilderPage
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_page(): void
    {
        add_submenu_page(
            null, // Hidden from menu
            __('Course Builder', 'ghost-lms'),
            __('Course Builder', 'ghost-lms'),
            'manage_options',
            'glms-course-builder',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        $course_id = isset($_GET['course_id']) ? absint(wp_unslash($_GET['course_id'])) : 0;

        if ($course_id <= 0) {
            wp_die(esc_html__('Invalid course ID.', 'ghost-lms'));
        }

        if (! Capabilities::current_user_can_manage_course($course_id)) {
            wp_die(esc_html__('You do not have permission to manage this course.', 'ghost-lms'));
        }

        echo '<div class="wrap">';
        echo '<div id="glms-course-builder-root"></div>';
        echo '</div>';
    }

    public function enqueue_assets(string $hook): void
    {
        if ('ghost-lms_page_glms-course-builder' !== $hook) {
            return;
        }

        $course_id = isset($_GET['course_id']) ? absint(wp_unslash($_GET['course_id'])) : 0;

        if ($course_id <= 0) {
            return;
        }

        // Enqueue the built admin-builder assets if they exist
        $build_dir = GHOST_LMS_PLUGIN_DIR . 'build/admin-builder/';
        $build_url = GHOST_LMS_PLUGIN_URL . 'build/admin-builder/';

        $index_js = $build_dir . 'index.js';
        $index_css = $build_dir . 'style.css';

        if (file_exists($index_js)) {
            wp_enqueue_script(
                'glms-course-builder',
                $build_url . 'index.js',
                ['wp-element', 'wp-api-fetch', 'wp-components'],
                GHOST_LMS_VERSION,
                true
            );

            wp_localize_script(
                'glms-course-builder',
                'glmsBuilder',
                [
                    'courseId' => $course_id,
                    'restUrl' => rest_url('wp/v2/'),
                    'nonce' => wp_create_nonce('wp_rest'),
                ]
            );
        }

        if (file_exists($index_css)) {
            wp_enqueue_style(
                'glms-course-builder',
                $build_url . 'style.css',
                [],
                GHOST_LMS_VERSION
            );
        }
    }
}
