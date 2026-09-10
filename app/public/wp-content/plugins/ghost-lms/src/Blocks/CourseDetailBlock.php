<?php
/**
 * Public-facing course detail block renderer.
 *
 * @package GhostLMS\Blocks
 */

declare(strict_types=1);

namespace GhostLMS\Blocks;

use GhostLMS\Curriculum\CurriculumRepository;
use WP_Post;

final class CourseDetailBlock
{
    public function register(): void
    {
        add_action('init', [$this, 'register_block']);
        add_filter('template_include', [$this, 'render_single_template']);
    }

    public function register_block(): void
    {
        register_block_type(
            GHOST_LMS_PLUGIN_DIR . 'assets/blocks/course-detail',
            [
                'render_callback' => [$this, 'render'],
            ]
        );
    }

    public function render(array $attributes = [], string $content = '', $block = null): string
    {
        $course_id = isset($attributes['course_id']) ? absint($attributes['course_id']) : 0;

        if ($course_id <= 0) {
            $course_id = get_queried_object_id();
        }

        if ($course_id <= 0) {
            $course_id = get_the_ID();
        }

        if ($course_id <= 0) {
            return '';
        }

        return self::render_detail_html($course_id);
    }

    public function render_single_template(string $template): string
    {
        if (! is_singular('glms_course')) {
            return $template;
        }

        return GHOST_LMS_PLUGIN_DIR . 'templates/course-detail/single.php';
    }

    public static function render_detail_html(int $course_id): string
    {
        $course = get_post($course_id);

        if (! $course instanceof WP_Post || 'glms_course' !== $course->post_type) {
            return '';
        }

        $product_id = absint(get_post_meta($course_id, '_glms_woo_product_id', true));
        $instructor = get_userdata((int) $course->post_author);
        $curriculum = CurriculumRepository::get($course_id);
        $excerpt = $course->post_excerpt ? $course->post_excerpt : wp_trim_words(strip_shortcodes($course->post_content), 40);

        ob_start();
        echo '<div class="glms-root glms-course-detail">';
        echo '<style>
            .glms-course-detail {
                color: var(--glms-color-text, #1f2937);
                font-family: var(--glms-font-body, system-ui, -apple-system, "Segoe UI", sans-serif);
            }
            .glms-course-detail__hero {
                display: grid;
                grid-template-columns: minmax(220px, 360px) minmax(0, 1fr);
                gap: 24px;
                align-items: center;
                margin-bottom: 32px;
            }
            .glms-course-detail__image-wrap {
                display: block;
                overflow: hidden;
                border: 1px solid var(--glms-color-border, #e5e7eb);
                border-radius: var(--glms-radius, 8px);
                background: var(--glms-color-bg-subtle, #f9fafb);
            }
            .glms-course-detail__image {
                display: block;
                width: 100%;
                height: 100%;
                min-height: 240px;
                object-fit: cover;
            }
            .glms-course-detail__fallback {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 240px;
                font-size: 0.85rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--glms-color-text-muted, #6b7280);
                background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            }
            .glms-course-detail__meta {
                margin: 0 0 12px;
                color: var(--glms-color-text-muted, #6b7280);
                font-size: 0.8rem;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }
            .glms-course-detail__title {
                margin: 0 0 12px;
                font-size: clamp(2rem, 3vw, 3rem);
                line-height: 1.2;
            }
            .glms-course-detail__excerpt {
                margin: 0;
                color: var(--glms-color-text-muted, #6b7280);
                line-height: 1.7;
            }
            .glms-course-detail__section {
                margin-top: 32px;
                padding-top: 24px;
                border-top: 1px solid var(--glms-color-border, #e5e7eb);
            }
            .glms-course-detail__section h2 {
                margin: 0 0 16px;
                font-size: 1.4rem;
            }
            .glms-course-detail__curriculum {
                display: grid;
                gap: 12px;
            }
            .glms-course-detail__module {
                border: 1px solid var(--glms-color-border, #e5e7eb);
                border-radius: var(--glms-radius, 8px);
                background: var(--glms-color-bg, #ffffff);
                overflow: hidden;
            }
            .glms-course-detail__module summary {
                list-style: none;
                cursor: pointer;
                padding: 16px 18px;
                font-weight: 600;
                background: var(--glms-color-bg-subtle, #f9fafb);
            }
            .glms-course-detail__module summary::-webkit-details-marker {
                display: none;
            }
            .glms-course-detail__lessons {
                display: grid;
                padding: 0 18px 18px;
                gap: 10px;
                margin-top: 12px;
            }
            .glms-course-detail__lessons li {
                list-style: none;
                padding: 10px 12px;
                border: 1px solid var(--glms-color-border, #e5e7eb);
                border-radius: var(--glms-radius, 8px);
                background: var(--glms-color-bg, #ffffff);
            }
            .glms-course-detail__bio {
                display: grid;
                gap: 16px;
                padding: 20px;
                border: 1px solid var(--glms-color-border, #e5e7eb);
                border-radius: var(--glms-radius, 8px);
                background: var(--glms-color-bg-subtle, #f9fafb);
            }
            .glms-course-detail__bio-name {
                margin: 0;
                font-size: 1.1rem;
            }
            .glms-course-detail__bio-description {
                margin: 0;
                color: var(--glms-color-text-muted, #6b7280);
                line-height: 1.7;
            }
            .glms-course-detail__cta {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                padding: 20px;
                border: 1px solid var(--glms-color-border, #e5e7eb);
                border-radius: var(--glms-radius, 8px);
                background: var(--glms-color-bg-subtle, #f9fafb);
            }
            .glms-course-detail__cta button {
                padding: 12px 18px;
                border: 0;
                border-radius: var(--glms-radius, 8px);
                background: var(--glms-color-primary, #2563eb);
                color: #ffffff;
                font-weight: 600;
                cursor: not-allowed;
                opacity: 0.7;
            }
            .glms-course-detail__cta-note {
                margin: 0;
                color: var(--glms-color-text-muted, #6b7280);
                font-size: 0.9rem;
            }
            @media (max-width: 768px) {
                .glms-course-detail__hero {
                    grid-template-columns: 1fr;
                }
            }
        </style>';

        echo '<article class="glms-course-detail__hero">';
        echo '<div class="glms-course-detail__image-wrap">';
        $thumbnail = get_the_post_thumbnail($course, 'medium_large', ['class' => 'glms-course-detail__image']);
        if (! empty($thumbnail)) {
            echo $thumbnail;
        } else {
            echo '<div class="glms-course-detail__fallback">' . esc_html__('Course image', 'ghost-lms') . '</div>';
        }
        echo '</div>';

        echo '<div class="glms-course-detail__content">';
        echo '<p class="glms-course-detail__meta">' . esc_html__('Course', 'ghost-lms') . '</p>';
        echo '<h1 class="glms-course-detail__title">' . esc_html($course->post_title) . '</h1>';
        echo '<p class="glms-course-detail__excerpt">' . esc_html($excerpt) . '</p>';
        echo '</div>';
        echo '</article>';

        echo '<section class="glms-course-detail__section">';
        echo '<h2>' . esc_html__('Curriculum', 'ghost-lms') . '</h2>';
        echo '<div class="glms-course-detail__curriculum">';

        if (empty($curriculum)) {
            echo '<p>' . esc_html__('This course does not have any modules yet.', 'ghost-lms') . '</p>';
        } else {
            foreach ($curriculum as $module) {
                $module_title = isset($module['title']) ? (string) $module['title'] : __('Untitled module', 'ghost-lms');
                $lessons = isset($module['lessons']) && is_array($module['lessons']) ? $module['lessons'] : [];

                echo '<details class="glms-course-detail__module" open>';
                echo '<summary>' . esc_html($module_title) . '</summary>';
                echo '<ul class="glms-course-detail__lessons">';

                if (empty($lessons)) {
                    echo '<li>' . esc_html__('No lessons in this module yet.', 'ghost-lms') . '</li>';
                } else {
                    foreach ($lessons as $lesson) {
                        $lesson_title = isset($lesson['title']) ? (string) $lesson['title'] : __('Untitled lesson', 'ghost-lms');
                        echo '<li>' . esc_html($lesson_title) . '</li>';
                    }
                }

                echo '</ul>';
                echo '</details>';
            }
        }

        echo '</div>';
        echo '</section>';

        echo '<section class="glms-course-detail__section">';
        echo '<h2>' . esc_html__('Instructor', 'ghost-lms') . '</h2>';
        echo '<div class="glms-course-detail__bio">';
        echo '<p class="glms-course-detail__bio-name">' . esc_html($instructor ? $instructor->display_name : __('Instructor', 'ghost-lms')) . '</p>';
        $bio = $instructor ? $instructor->description : '';
        echo '<p class="glms-course-detail__bio-description">' . esc_html($bio ? $bio : __('This instructor has not added a bio yet.', 'ghost-lms')) . '</p>';
        echo '</div>';
        echo '</section>';

        echo '<section class="glms-course-detail__section">';
        echo '<h2>' . esc_html__('Get started', 'ghost-lms') . '</h2>';
        echo '<div class="glms-course-detail__cta">';

        if ($product_id > 0 && function_exists('wc_get_product')) {
            $product = wc_get_product($product_id);
            if ($product) {
                echo '<div class="glms-course-detail__price">' . wp_kses_post(wc_price($product->get_price())) . '</div>';
                echo '<form class="cart" method="post" enctype="multipart/form-data">';
                echo '<input type="hidden" name="add-to-cart" value="' . esc_attr((string) $product_id) . '" />';
                echo '<button type="submit" class="single_add_to_cart_button button alt">' . esc_html__('Add to Cart', 'ghost-lms') . '</button>';
                echo '</form>';
            } else {
                echo '<button type="button" disabled>' . esc_html__('Coming soon', 'ghost-lms') . '</button>';
                echo '<p class="glms-course-detail__cta-note">' . esc_html__('This course is linked to a product that is not available yet.', 'ghost-lms') . '</p>';
            }
        } else {
            echo '<button type="button" disabled>' . esc_html__('Coming soon', 'ghost-lms') . '</button>';
            echo '<p class="glms-course-detail__cta-note">' . esc_html__('Enrollment is not available yet for this course.', 'ghost-lms') . '</p>';
        }

        echo '</div>';
        echo '</section>';

        echo '</div>';

        return ob_get_clean();
    }
}
