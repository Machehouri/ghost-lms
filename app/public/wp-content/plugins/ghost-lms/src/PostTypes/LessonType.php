<?php
/**
 * Lesson custom post type registration.
 *
 * @package GhostLMS\PostTypes
 */

declare(strict_types=1);

namespace GhostLMS\PostTypes;

use GhostLMS\Access\Capabilities;

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
                    'edit_post' => Capabilities::EDIT_GLMS_LESSON,
                    'read_post' => Capabilities::READ_GLMS_LESSON,
                    'delete_post' => Capabilities::DELETE_GLMS_LESSON,
                    'edit_posts' => Capabilities::EDIT_GLMS_LESSONS,
                    'edit_others_posts' => Capabilities::EDIT_OTHERS_GLMS_LESSONS,
                    'publish_posts' => Capabilities::PUBLISH_GLMS_LESSONS,
                    'read_private_posts' => Capabilities::READ_PRIVATE_GLMS_LESSONS,
                    'delete_posts' => Capabilities::DELETE_GLMS_LESSONS,
                    'delete_private_posts' => Capabilities::DELETE_PRIVATE_GLMS_LESSONS,
                    'delete_published_posts' => Capabilities::DELETE_PUBLISHED_GLMS_LESSONS,
                    'delete_others_posts' => Capabilities::DELETE_OTHERS_GLMS_LESSONS,
                    'edit_private_posts' => Capabilities::EDIT_PRIVATE_GLMS_LESSONS,
                    'edit_published_posts' => Capabilities::EDIT_PUBLISHED_GLMS_LESSONS,
                    'create_posts' => Capabilities::EDIT_GLMS_LESSONS,
                ],
            ]
        );
    }
}
