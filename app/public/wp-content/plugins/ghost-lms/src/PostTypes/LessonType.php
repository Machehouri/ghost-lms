<?php
/**
 * Lesson custom post type registration.
 *
 * @package GhostLMS\PostTypes
 */

declare(strict_types=1);

namespace GhostLMS\PostTypes;

final class LessonType implements Registrable
{
    public function register(): void
    {
        $labels = [
            'name' => __('Lessons', 'ghost-lms'),
            'singular_name' => __('Lesson', 'ghost-lms'),
            'add_new_item' => __('Add New Lesson', 'ghost-lms'),
            'edit_item' => __('Edit Lesson', 'ghost-lms'),
            'new_item' => __('New Lesson', 'ghost-lms'),
            'view_item' => __('View Lesson', 'ghost-lms'),
            'search_items' => __('Search Lessons', 'ghost-lms'),
            'not_found' => __('No lessons found.', 'ghost-lms'),
            'all_items' => __('All Lessons', 'ghost-lms'),
        ];

        register_post_type(
            'glms_lesson',
            [
                'labels' => $labels,
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'show_in_rest' => true,
                'supports' => ['title', 'editor', 'page-attributes'],
                'hierarchical' => false,
                'menu_icon' => 'dashicons-welcome-learn-more',
                'capability_type' => 'glms_lesson',
                'map_meta_cap' => true,
                'capabilities' => [
                    'edit_post' => 'edit_glms_lesson',
                    'read_post' => 'read_glms_lesson',
                    'delete_post' => 'delete_glms_lesson',
                    'edit_posts' => 'edit_glms_lessons',
                    'edit_others_posts' => 'edit_others_glms_lessons',
                    'publish_posts' => 'publish_glms_lessons',
                    'read_private_posts' => 'read_private_glms_lessons',
                    'delete_posts' => 'delete_glms_lessons',
                    'delete_private_posts' => 'delete_private_glms_lessons',
                    'delete_published_posts' => 'delete_published_glms_lessons',
                    'delete_others_posts' => 'delete_others_glms_lessons',
                    'edit_private_posts' => 'edit_private_glms_lessons',
                    'edit_published_posts' => 'edit_published_glms_lessons',
                    'create_posts' => 'edit_glms_lessons',
                ],
            ]
        );
    }
}
