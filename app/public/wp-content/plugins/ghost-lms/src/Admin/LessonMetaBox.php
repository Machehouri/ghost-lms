<?php
/**
 * Lesson meta box for content editor fields.
 *
 * @package GhostLMS\Admin
 */

declare(strict_types=1);

namespace GhostLMS\Admin;

final class LessonMetaBox
{
    public function register(): void
    {
        add_action('add_meta_boxes_glms_lesson', [$this, 'register_meta_box']);
        add_action('save_post_glms_lesson', [$this, 'save_lesson_meta'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_meta_box(): void
    {
        add_meta_box(
            'ghost_lms_lesson_content_fields',
            __('Lesson Content', 'ghost-lms'),
            [$this, 'render_meta_box'],
            'glms_lesson',
            'normal',
            'default'
        );
    }

    public function render_meta_box(\WP_Post $post): void
    {
        wp_nonce_field('ghost_lms_save_lesson_meta', 'ghost_lms_lesson_meta_nonce');

        $video_url = get_post_meta($post->ID, '_glms_video_url', true);
        $video_url = is_string($video_url) ? esc_url($video_url) : '';

        $attachment_id = (int) get_post_meta($post->ID, '_glms_attachment_id', true);
        $attachment = $attachment_id > 0 ? get_post($attachment_id) : null;
        $attachment_name = $attachment instanceof \WP_Post ? $attachment->post_title : '';

        echo '<div class="components-base-control" style="margin-bottom: 16px;">';
        echo '<p><strong>' . esc_html__('Video URL', 'ghost-lms') . '</strong></p>';
        echo '<input type="url" id="_glms_video_url" name="_glms_video_url" value="' . esc_attr($video_url) . '" class="regular-text" placeholder="https://example.com/video" style="width:100%;" />';
        echo '<p class="description">' . esc_html__('Use an oEmbed-compatible URL such as YouTube or Vimeo.', 'ghost-lms') . '</p>';
        echo '</div>';

        echo '<div class="components-base-control">';
        echo '<p><strong>' . esc_html__('Attachment', 'ghost-lms') . '</strong></p>';
        echo '<input type="hidden" id="_glms_attachment_id" name="_glms_attachment_id" value="' . esc_attr((string) $attachment_id) . '" />';
        echo '<button type="button" class="button" id="glms-select-attachment">' . esc_html__('Select File', 'ghost-lms') . '</button> ';
        echo '<span id="glms-attachment-name" class="description">' . esc_html($attachment_name ?: __('No file selected', 'ghost-lms')) . '</span>';
        echo '</div>';

        if (! empty($video_url)) {
            echo '<div style="margin-top: 16px;">';
            echo '<p><strong>' . esc_html__('Preview', 'ghost-lms') . '</strong></p>';
            $embed_html = wp_oembed_get($video_url);
            if (is_string($embed_html) && '' !== $embed_html) {
                echo wp_kses_post($embed_html);
            } else {
                echo '<p class="description">' . esc_html__('Preview unavailable for this URL.', 'ghost-lms') . '</p>';
            }
            echo '</div>';
        }
    }

    public function save_lesson_meta(int $post_id, \WP_Post $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        if (! isset($_POST['ghost_lms_lesson_meta_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ghost_lms_lesson_meta_nonce'])), 'ghost_lms_save_lesson_meta')) {
            return;
        }

        $video_url = isset($_POST['_glms_video_url']) ? esc_url_raw(wp_unslash($_POST['_glms_video_url'])) : '';
        if ('' === $video_url) {
            delete_post_meta($post_id, '_glms_video_url');
        } else {
            update_post_meta($post_id, '_glms_video_url', $video_url);
        }

        $attachment_id = isset($_POST['_glms_attachment_id']) ? absint(wp_unslash($_POST['_glms_attachment_id'])) : 0;
        if ($attachment_id > 0) {
            $attachment = get_post($attachment_id);
            if ($attachment instanceof \WP_Post && 'attachment' === $attachment->post_type) {
                update_post_meta($post_id, '_glms_attachment_id', $attachment_id);
            } else {
                delete_post_meta($post_id, '_glms_attachment_id');
            }
        } else {
            delete_post_meta($post_id, '_glms_attachment_id');
        }
    }

    public function enqueue_assets(string $hook): void
    {
        $screen = get_current_screen();
        if (! $screen || 'glms_lesson' !== $screen->post_type) {
            return;
        }

        if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            'ghost-lms-lesson-meta-box',
            GHOST_LMS_PLUGIN_URL . 'assets/blocks/lesson-meta-box.js',
            ['wp-util'],
            GHOST_LMS_VERSION,
            true
        );
    }
}
