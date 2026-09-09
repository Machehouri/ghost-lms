<?php
/**
 * Single template for public course detail pages.
 *
 * @package GhostLMS\Templates
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

get_header();
echo GhostLMS\Blocks\CourseDetailBlock::render_detail_html((int) get_queried_object_id());
get_footer();
