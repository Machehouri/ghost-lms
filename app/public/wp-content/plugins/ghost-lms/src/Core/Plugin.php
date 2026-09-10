<?php
/**
 * Main plugin bootstrap class.
 *
 * @package GhostLMS\Core
 */

declare(strict_types=1);

namespace GhostLMS\Core;

use GhostLMS\Access\Capabilities;
use GhostLMS\Admin\LessonMetaBox;
use GhostLMS\Admin\Menu;
use GhostLMS\Admin\ProductLinkMetaBox;
use GhostLMS\Blocks\CourseCatalogBlock;
use GhostLMS\Blocks\CourseDetailBlock;
use GhostLMS\Blocks\MyCoursesBlock;
use GhostLMS\Database\Migrator;
use GhostLMS\PostTypes\CourseCategoryTaxonomy;
use GhostLMS\PostTypes\CourseType;
use GhostLMS\PostTypes\LessonType;
use GhostLMS\PostTypes\QuestionType;
use GhostLMS\PostTypes\QuizType;
use GhostLMS\Woo\EnrollmentHooks;
use GhostLMS\Woo\MyAccountIntegration;

final class Plugin
{
    public function register(): void
    {
        add_action('init', [$this, 'load_textdomain'], 5);
        add_action('plugins_loaded', [Migrator::class, 'maybe_migrate'], 25);
        add_action('init', [$this, 'boot'], 20);
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('ghost-lms', false, dirname(plugin_basename(GHOST_LMS_PLUGIN_FILE)) . '/languages');
    }

    public function boot(): void
    {
        Capabilities::register_hooks();

        $registrables = [
            new CourseType(),
            new LessonType(),
            new QuizType(),
            new QuestionType(),
            new CourseCategoryTaxonomy(),
        ];

        foreach ($registrables as $registrable) {
            $registrable->register();
        }

        $menu = new Menu();
        $menu->register();

        $course_catalog_block = new CourseCatalogBlock();
        $course_catalog_block->register();

        $course_detail_block = new CourseDetailBlock();
        $course_detail_block->register();

        $my_courses_block = new MyCoursesBlock();
        $my_courses_block->register();

        $lesson_meta_box = new LessonMetaBox();
        $lesson_meta_box->register();

        $product_link_meta_box = new ProductLinkMetaBox();
        $product_link_meta_box->register();

        $enrollment_hooks = new EnrollmentHooks();
        $enrollment_hooks->register();

        $my_account_integration = new MyAccountIntegration();
        $my_account_integration->register();
    }
}
