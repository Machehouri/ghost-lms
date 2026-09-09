<?php
/**
 * Ghost LMS admin menu registration.
 *
 * @package GhostLMS\Admin
 */

declare(strict_types=1);

namespace GhostLMS\Admin;

final class Menu
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_ghost_lms_promote_instructor', [$this, 'promote_instructor']);
    }

    public function register_menu(): void
    {
        add_menu_page(
            __('Ghost LMS', 'ghost-lms'),
            __('Ghost LMS', 'ghost-lms'),
            'manage_options',
            'ghost-lms',
            [$this, 'render_page'],
            'dashicons-book',
            26
        );

        add_submenu_page(
            'ghost-lms',
            __('Promote Instructor', 'ghost-lms'),
            __('Promote Instructor', 'ghost-lms'),
            'manage_options',
            'ghost-lms-promote-instructor',
            [$this, 'render_promote_instructor_page']
        );
    }

    public function render_page(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Welcome to Ghost LMS', 'ghost-lms') . '</h1>';
        echo '</div>';
    }

    public function render_promote_instructor_page(): void
    {
        $users = get_users(
            [
                'orderby' => 'display_name',
                'order'   => 'ASC',
            ]
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Promote Instructor', 'ghost-lms') . '</h1>';

        if (isset($_GET['ghost_lms_status']) && 'success' === $_GET['ghost_lms_status']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('User promoted to instructor.', 'ghost-lms') . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('ghost_lms_promote_instructor', 'ghost_lms_promote_instructor_nonce');
        echo '<input type="hidden" name="action" value="ghost_lms_promote_instructor" />';
        echo '<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="ghost_lms_instructor_user">' . esc_html__('User', 'ghost-lms') . '</label></th><td><select id="ghost_lms_instructor_user" name="user_id" required>';
        echo '<option value="">' . esc_html__('Select a user', 'ghost-lms') . '</option>';

        foreach ($users as $user) {
            $selected = '';
            if (in_array('glms_instructor', (array) $user->roles, true)) {
                $selected = ' selected';
            }

            echo '<option value="' . esc_attr((string) $user->ID) . '"' . $selected . '>' . esc_html($user->display_name . ' (' . $user->user_login . ')') . '</option>';
        }

        echo '</select></td></tr></tbody></table>';
        submit_button(__('Promote to Instructor', 'ghost-lms'));
        echo '</form>';
        echo '</div>';
    }

    public function promote_instructor(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'ghost-lms'));
        }

        if (! isset($_POST['ghost_lms_promote_instructor_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ghost_lms_promote_instructor_nonce'])), 'ghost_lms_promote_instructor')) {
            wp_die(esc_html__('Security check failed.', 'ghost-lms'));
        }

        $user_id = isset($_POST['user_id']) ? absint(wp_unslash($_POST['user_id'])) : 0;

        if (0 === $user_id) {
            wp_safe_redirect(admin_url('admin.php?page=ghost-lms-promote-instructor'));
            exit;
        }

        $user = get_user_by('id', $user_id);

        if (! $user) {
            wp_safe_redirect(admin_url('admin.php?page=ghost-lms-promote-instructor'));
            exit;
        }

        $user->add_role('glms_instructor');

        wp_safe_redirect(admin_url('admin.php?page=ghost-lms-promote-instructor&ghost_lms_status=success'));
        exit;
    }
}
