<?php
/**
 * Public-facing course catalog block renderer.
 *
 * @package GhostLMS\Blocks
 */

declare(strict_types=1);

namespace GhostLMS\Blocks;

final class CourseCatalogBlock
{
    public function register(): void
    {
        add_action('init', [$this, 'register_block']);
        add_filter('template_include', [$this, 'render_archive_template']);
    }

    public function register_block(): void
    {
        register_block_type(
            GHOST_LMS_PLUGIN_DIR . 'assets/blocks/course-catalog',
            [
                'render_callback' => [$this, 'render'],
            ]
        );
    }

    public function render(array $attributes = [], string $content = '', $block = null): string
    {
        return self::render_catalog_html($attributes);
    }

    public function render_archive_template(string $template): string
    {
        if (! is_post_type_archive('glms_course')) {
            return $template;
        }

        return GHOST_LMS_PLUGIN_DIR . 'templates/course-catalog/archive.php';
    }

    public static function render_catalog_html(array $attributes = []): string
    {
        $posts_per_page = isset($attributes['postsPerPage']) ? absint($attributes['postsPerPage']) : 12;
        $selected_category = isset($_GET['glms_category']) ? sanitize_title(wp_unslash($_GET['glms_category'])) : '';

        $query_args = [
            'post_type' => 'glms_course',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if (! empty($selected_category)) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'glms_course_category',
                    'field' => 'slug',
                    'terms' => [$selected_category],
                    'operator' => 'IN',
                ],
            ];
        }

        $courses = get_posts($query_args);
        $terms = get_terms(
            [
                'taxonomy' => 'glms_course_category',
                'hide_empty' => true,
            ]
        );

        ob_start();
        echo '<div class="glms-root glms-course-catalog">';
        echo '<style>
            .glms-course-catalog {
                color: var(--glms-color-text, #1f2937);
                font-family: var(--glms-font-body, system-ui, -apple-system, "Segoe UI", sans-serif);
            }
            .glms-course-catalog__filters {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-bottom: 24px;
            }
            .glms-course-catalog__filter {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 8px 12px;
                border: 1px solid var(--glms-color-border, #e5e7eb);
                border-radius: 999px;
                background: var(--glms-color-bg-subtle, #f9fafb);
                color: var(--glms-color-text, #1f2937);
                text-decoration: none;
                font-size: 0.9rem;
            }
            .glms-course-catalog__filter.is-active {
                background: var(--glms-color-primary, #2563eb);
                border-color: var(--glms-color-primary, #2563eb);
                color: #ffffff;
            }
            .glms-course-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 24px;
            }
            .glms-course-card {
                display: flex;
                flex-direction: column;
                height: 100%;
                overflow: hidden;
                border: 1px solid var(--glms-color-border, #e5e7eb);
                border-radius: var(--glms-radius, 8px);
                background: var(--glms-color-bg, #ffffff);
            }
            .glms-course-card__image-wrap {
                display: block;
                background: var(--glms-color-bg-subtle, #f9fafb);
            }
            .glms-course-card__image {
                display: block;
                width: 100%;
                height: 200px;
                object-fit: cover;
            }
            .glms-course-card__fallback {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                height: 200px;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: var(--glms-color-text-muted, #6b7280);
                background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            }
            .glms-course-card__body {
                display: flex;
                flex: 1;
                flex-direction: column;
                gap: 12px;
                padding: 18px;
            }
            .glms-course-card__meta {
                font-size: 0.8rem;
                color: var(--glms-color-text-muted, #6b7280);
            }
            .glms-course-card__title {
                margin: 0;
                font-size: 1.2rem;
            }
            .glms-course-card__title a {
                color: var(--glms-color-text, #1f2937);
                text-decoration: none;
            }
            .glms-course-card__excerpt {
                margin: 0;
                color: var(--glms-color-text-muted, #6b7280);
                line-height: 1.6;
            }
            .glms-course-card__footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: auto;
                padding: 0 18px 18px;
            }
            .glms-course-card__badge {
                display: inline-flex;
                align-items: center;
                padding: 6px 10px;
                border-radius: 999px;
                background: var(--glms-color-bg-subtle, #f9fafb);
                color: var(--glms-color-text, #1f2937);
                font-size: 0.75rem;
                font-weight: 600;
            }
            .glms-course-card__badge--accent {
                background: rgba(37, 99, 235, 0.1);
                color: var(--glms-color-primary, #2563eb);
            }
            .glms-course-catalog__empty {
                margin: 0;
                color: var(--glms-color-text-muted, #6b7280);
            }
        </style>';

        if (! is_wp_error($terms) && ! empty($terms)) {
            echo '<nav class="glms-course-catalog__filters" aria-label="Course categories">';
            $catalog_url = get_post_type_archive_link('glms_course');

            if (empty($catalog_url)) {
                $catalog_url = home_url('/courses/');
            }

            $all_url = add_query_arg([], $catalog_url);
            echo '<a class="glms-course-catalog__filter ' . (empty($selected_category) ? 'is-active' : '') . '" href="' . esc_url($all_url) . '">' . esc_html__('All courses', 'ghost-lms') . '</a>';

            foreach ($terms as $term) {
                $term_url = add_query_arg('glms_category', rawurlencode($term->slug), $catalog_url);
                $is_active = $selected_category === $term->slug;
                echo '<a class="glms-course-catalog__filter ' . ($is_active ? 'is-active' : '') . '" href="' . esc_url($term_url) . '">' . esc_html($term->name) . '</a>';
            }
            echo '</nav>';
        }

        if (empty($courses)) {
            echo '<p class="glms-course-catalog__empty">' . esc_html__('No courses are available yet.', 'ghost-lms') . '</p>';
            echo '</div>';
            return ob_get_clean();
        }

        echo '<div class="glms-course-grid">';

        foreach ($courses as $course) {
            $course_id = (int) $course->ID;
            $course_url = get_permalink($course);
            $thumbnail = get_the_post_thumbnail($course, 'medium', ['class' => 'glms-course-card__image']);
            $product_id = absint(get_post_meta($course_id, '_glms_woo_product_id', true));
            $has_product = $product_id > 0;

            include GHOST_LMS_PLUGIN_DIR . 'templates/course-catalog/card.php';
        }

        echo '</div>';
        echo '</div>';

        return ob_get_clean();
    }
}
