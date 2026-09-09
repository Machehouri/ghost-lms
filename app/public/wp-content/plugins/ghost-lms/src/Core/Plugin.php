<?php
/**
 * Main plugin bootstrap class.
 *
 * @package GhostLMS\Core
 */

declare(strict_types=1);

namespace GhostLMS\Core;

use GhostLMS\Admin\Menu;
use GhostLMS\PostTypes\CourseCategoryTaxonomy;
use GhostLMS\PostTypes\CourseType;
use GhostLMS\PostTypes\LessonType;
use GhostLMS\PostTypes\QuestionType;
use GhostLMS\PostTypes\QuizType;

final class Plugin
{
    public function register(): void
    {
        add_action('init', [$this, 'load_textdomain'], 5);
        add_action('init', [$this, 'boot'], 20);
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('ghost-lms', false, dirname(plugin_basename(GHOST_LMS_PLUGIN_FILE)) . '/languages');
    }

    public function boot(): void
    {
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
    }
}
