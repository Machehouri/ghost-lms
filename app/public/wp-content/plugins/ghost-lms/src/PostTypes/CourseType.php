<?php
/**
 * Course custom post type registration.
 *
 * @package GhostLMS\PostTypes
 */

declare(strict_types=1);

namespace GhostLMS\PostTypes;

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
                    'edit_post' => 'edit_glms_course',
                    'read_post' => 'read_glms_course',
                    'delete_post' => 'delete_glms_course',
                    'edit_posts' => 'edit_glms_courses',
                    'edit_others_posts' => 'edit_others_glms_courses',
                    'publish_posts' => 'publish_glms_courses',
                    'read_private_posts' => 'read_private_glms_courses',
                    'delete_posts' => 'delete_glms_courses',
                    'delete_private_posts' => 'delete_private_glms_courses',
                    'delete_published_posts' => 'delete_published_glms_courses',
                    'delete_others_posts' => 'delete_others_glms_courses',
                    'edit_private_posts' => 'edit_private_glms_courses',
                    'edit_published_posts' => 'edit_published_glms_courses',
                    'create_posts' => 'edit_glms_courses',
                ],
            ]
        );
    }
}
