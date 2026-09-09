<?php
/**
 * Question custom post type registration.
 *
 * @package GhostLMS\PostTypes
 */

declare(strict_types=1);

namespace GhostLMS\PostTypes;

final class QuestionType implements Registrable
{
    public function register(): void
    {
        $labels = [
            'name' => __('Questions', 'ghost-lms'),
            'singular_name' => __('Question', 'ghost-lms'),
            'add_new_item' => __('Add New Question', 'ghost-lms'),
            'edit_item' => __('Edit Question', 'ghost-lms'),
            'new_item' => __('New Question', 'ghost-lms'),
            'view_item' => __('View Question', 'ghost-lms'),
            'search_items' => __('Search Questions', 'ghost-lms'),
            'not_found' => __('No questions found.', 'ghost-lms'),
            'all_items' => __('All Questions', 'ghost-lms'),
        ];

        register_post_type(
            'glms_question',
            [
                'labels' => $labels,
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'show_in_rest' => true,
                'supports' => ['title', 'page-attributes'],
                'menu_icon' => 'dashicons-editor-help',
                'capability_type' => 'glms_question',
                'map_meta_cap' => true,
                'capabilities' => [
                    'edit_post' => 'edit_glms_question',
                    'read_post' => 'read_glms_question',
                    'delete_post' => 'delete_glms_question',
                    'edit_posts' => 'edit_glms_questions',
                    'edit_others_posts' => 'edit_others_glms_questions',
                    'publish_posts' => 'publish_glms_questions',
                    'read_private_posts' => 'read_private_glms_questions',
                    'delete_posts' => 'delete_glms_questions',
                    'delete_private_posts' => 'delete_private_glms_questions',
                    'delete_published_posts' => 'delete_published_glms_questions',
                    'delete_others_posts' => 'delete_others_glms_questions',
                    'edit_private_posts' => 'edit_private_glms_questions',
                    'edit_published_posts' => 'edit_published_glms_questions',
                    'create_posts' => 'edit_glms_questions',
                ],
            ]
        );
    }
}
