<?php
/**
 * Quiz custom post type registration.
 *
 * @package GhostLMS\PostTypes
 */

declare(strict_types=1);

namespace GhostLMS\PostTypes;

final class QuizType implements Registrable
{
    public function register(): void
    {
        $labels = [
            'name' => __('Quizzes', 'ghost-lms'),
            'singular_name' => __('Quiz', 'ghost-lms'),
            'add_new_item' => __('Add New Quiz', 'ghost-lms'),
            'edit_item' => __('Edit Quiz', 'ghost-lms'),
            'new_item' => __('New Quiz', 'ghost-lms'),
            'view_item' => __('View Quiz', 'ghost-lms'),
            'search_items' => __('Search Quizzes', 'ghost-lms'),
            'not_found' => __('No quizzes found.', 'ghost-lms'),
            'all_items' => __('All Quizzes', 'ghost-lms'),
        ];

        register_post_type(
            'glms_quiz',
            [
                'labels' => $labels,
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'show_in_rest' => true,
                'supports' => ['title'],
                'menu_icon' => 'dashicons-clipboard',
                'capability_type' => 'glms_quiz',
                'map_meta_cap' => true,
                'capabilities' => [
                    'edit_post' => 'edit_glms_quiz',
                    'read_post' => 'read_glms_quiz',
                    'delete_post' => 'delete_glms_quiz',
                    'edit_posts' => 'edit_glms_quizzes',
                    'edit_others_posts' => 'edit_others_glms_quizzes',
                    'publish_posts' => 'publish_glms_quizzes',
                    'read_private_posts' => 'read_private_glms_quizzes',
                    'delete_posts' => 'delete_glms_quizzes',
                    'delete_private_posts' => 'delete_private_glms_quizzes',
                    'delete_published_posts' => 'delete_published_glms_quizzes',
                    'delete_others_posts' => 'delete_others_glms_quizzes',
                    'edit_private_posts' => 'edit_private_glms_quizzes',
                    'edit_published_posts' => 'edit_published_glms_quizzes',
                    'create_posts' => 'edit_glms_quizzes',
                ],
            ]
        );
    }
}
