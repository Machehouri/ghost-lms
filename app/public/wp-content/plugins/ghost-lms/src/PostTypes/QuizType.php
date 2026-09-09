<?php
/**
 * Quiz custom post type registration.
 *
 * @package GhostLMS\PostTypes
 */

declare(strict_types=1);

namespace GhostLMS\PostTypes;

use GhostLMS\Access\Capabilities;

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
                    'edit_post' => Capabilities::EDIT_GLMS_QUIZ,
                    'read_post' => Capabilities::READ_GLMS_QUIZ,
                    'delete_post' => Capabilities::DELETE_GLMS_QUIZ,
                    'edit_posts' => Capabilities::EDIT_GLMS_QUIZZES,
                    'edit_others_posts' => Capabilities::EDIT_OTHERS_GLMS_QUIZZES,
                    'publish_posts' => Capabilities::PUBLISH_GLMS_QUIZZES,
                    'read_private_posts' => Capabilities::READ_PRIVATE_GLMS_QUIZZES,
                    'delete_posts' => Capabilities::DELETE_GLMS_QUIZZES,
                    'delete_private_posts' => Capabilities::DELETE_PRIVATE_GLMS_QUIZZES,
                    'delete_published_posts' => Capabilities::DELETE_PUBLISHED_GLMS_QUIZZES,
                    'delete_others_posts' => Capabilities::DELETE_OTHERS_GLMS_QUIZZES,
                    'edit_private_posts' => Capabilities::EDIT_PRIVATE_GLMS_QUIZZES,
                    'edit_published_posts' => Capabilities::EDIT_PUBLISHED_GLMS_QUIZZES,
                    'create_posts' => Capabilities::EDIT_GLMS_QUIZZES,
                ],
            ]
        );
    }
}
