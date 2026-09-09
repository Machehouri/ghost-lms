<?php
/**
 * Archive template for the public course catalog.
 *
 * @package GhostLMS\Templates
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

get_header();
echo GhostLMS\Blocks\CourseCatalogBlock::render_catalog_html();
get_footer();
