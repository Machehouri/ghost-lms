<?php
/**
 * Question custom post type registration.
 *
 * @package GhostLMS\PostTypes
 */

declare(strict_types=1);

namespace GhostLMS\PostTypes;

use GhostLMS\Access\Capabilities;

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
                    'edit_post' => Capabilities::EDIT_GLMS_QUESTION,
                    'read_post' => Capabilities::READ_GLMS_QUESTION,
                    'delete_post' => Capabilities::DELETE_GLMS_QUESTION,
                    'edit_posts' => Capabilities::EDIT_GLMS_QUESTIONS,
                    'edit_others_posts' => Capabilities::EDIT_OTHERS_GLMS_QUESTIONS,
                    'publish_posts' => Capabilities::PUBLISH_GLMS_QUESTIONS,
                    'read_private_posts' => Capabilities::READ_PRIVATE_GLMS_QUESTIONS,
                    'delete_posts' => Capabilities::DELETE_GLMS_QUESTIONS,
                    'delete_private_posts' => Capabilities::DELETE_PRIVATE_GLMS_QUESTIONS,
                    'delete_published_posts' => Capabilities::DELETE_PUBLISHED_GLMS_QUESTIONS,
                    'delete_others_posts' => Capabilities::DELETE_OTHERS_GLMS_QUESTIONS,
                    'edit_private_posts' => Capabilities::EDIT_PRIVATE_GLMS_QUESTIONS,
                    'edit_published_posts' => Capabilities::EDIT_PUBLISHED_GLMS_QUESTIONS,
                    'create_posts' => Capabilities::EDIT_GLMS_QUESTIONS,
                ],
            ]
        );
    }
}
