<?php
/**
 * Meta box to enable course-wide lesson sequencing.
 *
 * @package GhostLMS\Admin
 */

declare(strict_types=1);

namespace GhostLMS\Admin;

use WP_Post;

final class CourseSequencingMetaBox
{
    public function register(): void
    {
        add_action('add_meta_boxes_glms_course', [$this, 'register_meta_box']);
        add_action('save_post_glms_course', [$this, 'save_course_setting'], 10, 2);
    }

    public function register_meta_box(): void
    {
        add_meta_box(
            'ghost_lms_course_sequencing',
            __('Lesson Sequencing', 'ghost-lms'),
            [$this, 'render_meta_box'],
            'glms_course',
            'normal',
            'default'
        );
    }

    public function render_meta_box(WP_Post $post): void
    {
        wp_nonce_field('ghost_lms_save_course_sequencing', 'ghost_lms_course_sequencing_nonce');

        $enabled = '1' === (string) get_post_meta($post->ID, '_glms_sequential_progression', true);
        echo '<p>';
        echo '<label for="_glms_sequential_progression">';
        echo '<input type="checkbox" id="_glms_sequential_progression" name="_glms_sequential_progression" value="1" ' . checked($enabled, true, false) . ' /> ';
        echo esc_html__('Require lessons to be completed in order', 'ghost-lms');
        echo '</label>';
        echo '</p>';
        echo '<p class="description">' . esc_html__('When enabled, students must complete each lesson before moving to the next one in the course curriculum.', 'ghost-lms') . '</p>';
    }

    public function save_course_setting(int $post_id, WP_Post $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        if (! isset($_POST['ghost_lms_course_sequencing_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ghost_lms_course_sequencing_nonce'])), 'ghost_lms_save_course_sequencing')) {
            return;
        }

        if (isset($_POST['_glms_sequential_progression'])) {
            update_post_meta($post_id, '_glms_sequential_progression', '1');
        } else {
            delete_post_meta($post_id, '_glms_sequential_progression');
        }
    }
}
