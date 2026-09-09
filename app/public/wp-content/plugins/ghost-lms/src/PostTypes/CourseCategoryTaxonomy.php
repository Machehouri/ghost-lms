<?php
/**
 * Course category taxonomy registration.
 *
 * @package GhostLMS\PostTypes
 */

declare(strict_types=1);

namespace GhostLMS\PostTypes;

final class CourseCategoryTaxonomy implements Registrable
{
    public function register(): void
    {
        $labels = [
            'name' => __('Course Categories', 'ghost-lms'),
            'singular_name' => __('Course Category', 'ghost-lms'),
            'search_items' => __('Search Course Categories', 'ghost-lms'),
            'all_items' => __('All Course Categories', 'ghost-lms'),
            'parent_item' => __('Parent Course Category', 'ghost-lms'),
            'parent_item_colon' => __('Parent Course Category:', 'ghost-lms'),
            'edit_item' => __('Edit Course Category', 'ghost-lms'),
            'update_item' => __('Update Course Category', 'ghost-lms'),
            'add_new_item' => __('Add New Course Category', 'ghost-lms'),
            'new_item_name' => __('New Course Category Name', 'ghost-lms'),
            'menu_name' => __('Categories', 'ghost-lms'),
        ];

        register_taxonomy(
            'glms_course_category',
            ['glms_course'],
            [
                'labels' => $labels,
                'public' => true,
                'show_in_rest' => true,
                'hierarchical' => true,
                'show_admin_column' => true,
                'rewrite' => [
                    'slug' => 'course-category',
                ],
                'capabilities' => [
                    'manage_terms' => 'manage_glms_course_categories',
                    'edit_terms' => 'edit_glms_course_categories',
                    'delete_terms' => 'delete_glms_course_categories',
                    'assign_terms' => 'assign_glms_course_categories',
                ],
            ]
        );
    }
}
