<?php
/**
 * Course custom post type registration.
 *
 * @package GhostLMS\PostTypes
 */

declare(strict_types=1);

namespace GhostLMS\PostTypes;

use GhostLMS\Access\Capabilities;

final class CourseType implements Registrable
{
    public function register(): void
    {
        $labels = [
            'name' => __('Courses', 'ghost-lms'),
            'singular_name' => __('Course', 'ghost-lms'),
            'add_new_item' => __('Add New Course', 'ghost-lms'),
            'edit_item' => __('Edit Course', 'ghost-lms'),
            'new_item' => __('New Course', 'ghost-lms'),
            'view_item' => __('View Course', 'ghost-lms'),
            'search_items' => __('Search Courses', 'ghost-lms'),
            'not_found' => __('No courses found.', 'ghost-lms'),
            'all_items' => __('All Courses', 'ghost-lms'),
        ];

        register_post_type(
            'glms_course',
            [
                'labels' => $labels,
                'public' => true,
                'has_archive' => true,
                'rewrite' => [
                    'slug' => 'courses',
                ],
                'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
                'show_in_rest' => true,
                'show_in_menu' => false,
                'menu_icon' => 'dashicons-book-alt',
                'taxonomies' => ['glms_course_category'],
                'capability_type' => 'glms_course',
                'map_meta_cap' => true,
                'capabilities' => [
                    'edit_post' => Capabilities::EDIT_GLMS_COURSE,
                    'read_post' => Capabilities::READ_GLMS_COURSE,
                    'delete_post' => Capabilities::DELETE_GLMS_COURSE,
                    'edit_posts' => Capabilities::EDIT_GLMS_COURSES,
                    'edit_others_posts' => Capabilities::EDIT_OTHERS_GLMS_COURSES,
                    'publish_posts' => Capabilities::PUBLISH_GLMS_COURSES,
                    'read_private_posts' => Capabilities::READ_PRIVATE_GLMS_COURSES,
                    'delete_posts' => Capabilities::DELETE_GLMS_COURSES,
                    'delete_private_posts' => Capabilities::DELETE_PRIVATE_GLMS_COURSES,
                    'delete_published_posts' => Capabilities::DELETE_PUBLISHED_GLMS_COURSES,
                    'delete_others_posts' => Capabilities::DELETE_OTHERS_GLMS_COURSES,
                    'edit_private_posts' => Capabilities::EDIT_PRIVATE_GLMS_COURSES,
                    'edit_published_posts' => Capabilities::EDIT_PUBLISHED_GLMS_COURSES,
                    'create_posts' => Capabilities::EDIT_GLMS_COURSES,
                ],
            ]
        );
    }
}
