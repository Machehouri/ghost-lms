<?php
/**
 * Course curriculum repository.
 *
 * @package GhostLMS\Curriculum
 */

declare(strict_types=1);

namespace GhostLMS\Curriculum;

final class CurriculumRepository
{
    public static function get(int $course_id): array
    {
        $curriculum = get_post_meta($course_id, '_glms_curriculum', true);

        if (! is_array($curriculum)) {
            return [];
        }

        $normalized = [];

        foreach ($curriculum as $module_index => $module) {
            if (! is_array($module)) {
                continue;
            }

            $module_id = isset($module['module_id']) ? (string) $module['module_id'] : 'module_' . $module_index;
            $title = isset($module['title']) ? sanitize_text_field((string) $module['title']) : __('Untitled module', 'ghost-lms');
            $lessons = [];

            if (isset($module['lessons']) && is_array($module['lessons'])) {
                foreach ($module['lessons'] as $lesson_entry) {
                    if (! is_array($lesson_entry)) {
                        continue;
                    }

                    $lesson_id = isset($lesson_entry['lesson_id']) ? absint($lesson_entry['lesson_id']) : 0;
                    if ($lesson_id <= 0) {
                        continue;
                    }

                    $lesson_post = get_post($lesson_id);
                    $lesson_title = $lesson_post instanceof \WP_Post ? $lesson_post->post_title : __('Untitled lesson', 'ghost-lms');

                    $lessons[] = [
                        'lesson_id' => $lesson_id,
                        'title' => $lesson_title,
                    ];
                }
            }

            $normalized[] = [
                'module_id' => $module_id,
                'title' => $title,
                'lessons' => $lessons,
            ];
        }

        return $normalized;
    }

    public static function save(int $course_id, array $curriculum): void
    {
        $sanitized = [];

        foreach ($curriculum as $module_index => $module) {
            if (! is_array($module)) {
                continue;
            }

            $module_id = isset($module['module_id']) ? sanitize_key((string) $module['module_id']) : 'module_' . $module_index;
            $title = isset($module['title']) ? sanitize_text_field((string) $module['title']) : __('Untitled module', 'ghost-lms');
            $lessons = [];

            if (isset($module['lessons']) && is_array($module['lessons'])) {
                foreach ($module['lessons'] as $lesson_entry) {
                    if (! is_array($lesson_entry)) {
                        continue;
                    }

                    $lesson_id = isset($lesson_entry['lesson_id']) ? absint($lesson_entry['lesson_id']) : 0;
                    if ($lesson_id <= 0) {
                        continue;
                    }

                    $lessons[] = [
                        'lesson_id' => $lesson_id,
                        'order' => isset($lesson_entry['order']) ? absint($lesson_entry['order']) : 0,
                    ];
                }
            }

            $sanitized[] = [
                'module_id' => $module_id,
                'title' => $title,
                'order' => isset($module['order']) ? absint($module['order']) : $module_index,
                'lessons' => $lessons,
            ];
        }

        update_post_meta($course_id, '_glms_curriculum', $sanitized);
    }
}
