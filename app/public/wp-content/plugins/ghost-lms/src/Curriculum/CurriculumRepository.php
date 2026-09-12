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
        global $wpdb;

        // Use one lock because older MySQL/MariaDB versions do not support nested named locks.
        $lock_name = 'glms_curriculum_save';
        if ('1' !== (string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lock_name))) {
            return;
        }

        try {
            $previous_curriculum = get_post_meta($course_id, '_glms_curriculum', true);
            $previous_lesson_ids = is_array($previous_curriculum) ? self::lesson_ids($previous_curriculum) : [];
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

            $current_lesson_ids = self::lesson_ids($sanitized);
            $lesson_ids = array_unique(array_merge($previous_lesson_ids, $current_lesson_ids));

            update_post_meta($course_id, '_glms_curriculum', $sanitized);

            foreach ($lesson_ids as $lesson_id) {
                $course_ids = array_map('absint', (array) get_post_meta($lesson_id, '_glms_course_ids', true));
                $course_ids = array_values(array_diff($course_ids, [$course_id]));

                if (in_array($lesson_id, $current_lesson_ids, true)) {
                    $course_ids[] = $course_id;
                }

                $course_ids = array_values(array_unique(array_filter($course_ids)));
                if ([] === $course_ids) {
                    delete_post_meta($lesson_id, '_glms_course_ids');
                } else {
                    update_post_meta($lesson_id, '_glms_course_ids', $course_ids);
                }
            }
        } finally {
            $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
        }
    }

    private static function lesson_ids(array $curriculum): array
    {
        $lesson_ids = [];

        foreach ($curriculum as $module) {
            if (! is_array($module) || ! isset($module['lessons']) || ! is_array($module['lessons'])) {
                continue;
            }

            foreach ($module['lessons'] as $lesson) {
                if (is_array($lesson) && isset($lesson['lesson_id'])) {
                    $lesson_ids[] = absint($lesson['lesson_id']);
                }
            }
        }

        return array_values(array_unique(array_filter($lesson_ids)));
    }
}
