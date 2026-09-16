<?php
/**
 * Course edit meta box with Edit Curriculum link.
 *
 * @package GhostLMS\Admin
 */

declare(strict_types=1);

namespace GhostLMS\Admin;

if (! defined('ABSPATH')) {
    exit;
}

use WP_Post;

final class CourseEditMetaBox
{
    public function register(): void
    {
        add_action('add_meta_boxes_glms_course', [$this, 'register_meta_box']);
    }

    public function register_meta_box(): void
    {
        add_meta_box(
            'ghost_lms_course_builder_link',
            __('Course Builder', 'ghost-lms'),
            [$this, 'render_meta_box'],
            'glms_course',
            'side',
            'high'
        );
    }

    public function render_meta_box(WP_Post $post): void
    {
        $builder_url = add_query_arg(
            [
                'page' => 'glms-course-builder',
                'course_id' => $post->ID,
            ],
            admin_url('admin.php')
        );

        echo '<p>';
        echo '<a href="' . esc_url($builder_url) . '" class="button button-primary button-large" style="width: 100%; text-align: center;">';
        echo esc_html__('Edit Curriculum', 'ghost-lms');
        echo '</a>';
        echo '</p>';
        echo '<p class="description">' . esc_html__('Open the course builder to manage modules and lessons.', 'ghost-lms') . '</p>';
    }
}
