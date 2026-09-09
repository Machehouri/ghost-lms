<?php
/**
 * Course card partial for the public catalog.
 *
 * @package GhostLMS\Templates
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$course_title = get_the_title($course);
$author_name = get_the_author_meta('display_name', (int) $course->post_author);
$excerpt = get_the_excerpt($course);
$excerpt = '' !== $excerpt ? $excerpt : __('No description yet.', 'ghost-lms');
$course_url = get_permalink($course);
$has_product = false;
$badge_text = __('Coming soon', 'ghost-lms');

if (! empty($course_url)) {
    $has_product = false;
}

?>
<article class="glms-course-card">
    <a class="glms-course-card__image-wrap" href="<?php echo esc_url($course_url); ?>">
        <?php
        if (! empty($thumbnail)) {
            echo wp_kses_post($thumbnail);
        } else {
            echo '<span class="glms-course-card__fallback">' . esc_html__('Course', 'ghost-lms') . '</span>';
        }
        ?>
    </a>

    <div class="glms-course-card__body">
        <div class="glms-course-card__meta"><?php echo esc_html__('By', 'ghost-lms') . ' ' . esc_html($author_name); ?></div>
        <h3 class="glms-course-card__title"><a href="<?php echo esc_url($course_url); ?>"><?php echo esc_html($course_title); ?></a></h3>
        <p class="glms-course-card__excerpt"><?php echo esc_html(wp_trim_words($excerpt, 20)); ?></p>
    </div>

    <div class="glms-course-card__footer">
        <?php if ($has_product) : ?>
            <span class="glms-course-card__badge glms-course-card__badge--accent"><?php esc_html_e('View course', 'ghost-lms'); ?></span>
        <?php else : ?>
            <span class="glms-course-card__badge"><?php echo esc_html($badge_text); ?></span>
        <?php endif; ?>
    </div>
</article>
