<?php
/**
 * WooCommerce My Account integration for enrolled courses.
 *
 * @package GhostLMS\Woo
 */

declare(strict_types=1);

namespace GhostLMS\Woo;

use GhostLMS\Blocks\MyCoursesBlock;

final class MyAccountIntegration
{
    public function register(): void
    {
        add_action('init', [$this, 'register_endpoint']);
        add_filter('woocommerce_account_menu_items', [$this, 'add_menu_item']);
        add_action('woocommerce_account_my-courses_endpoint', [$this, 'render_endpoint']);
    }

    public function register_endpoint(): void
    {
        add_rewrite_endpoint('my-courses', EP_ROOT | EP_PAGES);
    }

    public function add_menu_item(array $items): array
    {
        $updated = [];

        foreach ($items as $key => $label) {
            $updated[$key] = $label;
            if ('dashboard' === $key) {
                $updated['my-courses'] = __('My Courses', 'ghost-lms');
            }
        }

        return $updated;
    }

    public function render_endpoint(): void
    {
        echo MyCoursesBlock::render_dashboard_html();
    }
}