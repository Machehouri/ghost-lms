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
    }

    public function render_page(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Welcome to Ghost LMS', 'ghost-lms') . '</h1>';
        echo '</div>';
    }
}
