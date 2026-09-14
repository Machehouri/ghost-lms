<?php
/**
 * Validation utilities for drip rule payloads.
 *
 * @package GhostLMS\Access
 */

declare(strict_types=1);

namespace GhostLMS\Access;

use GhostLMS\Curriculum\CurriculumRepository;
use WP_Error;

final class DripRuleValidator
{
    /**
     * Validate a rule payload before saving it to lesson metadata.
     *
     * @param int $lesson_id Lesson being saved.
     * @param mixed $value Raw payload.
     * @return bool|WP_Error
     */
    public static function validate(int $lesson_id, mixed $value): bool|WP_Error
    {
        if (null === $value || '' === $value) {
            return true;
        }

        if (! is_string($value)) {
            return new WP_Error('ghost_lms_invalid_drip_rule', __('The drip rule is invalid.', 'ghost-lms'));
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            return new WP_Error('ghost_lms_invalid_drip_rule', __('The drip rule must be valid JSON.', 'ghost-lms'));
        }

        $type = isset($decoded['type']) ? sanitize_key((string) $decoded['type']) : '';
        if ('' === $type) {
            return new WP_Error('ghost_lms_invalid_drip_rule', __('The drip rule type is missing.', 'ghost-lms'));
        }

        if ('prerequisite' === $type) {
            return self::validate_prerequisite($lesson_id, $decoded);
        }

        if ('fixed_date' === $type) {
            if (null === DripRule::parse_fixed_date($decoded['date'] ?? null)) {
                return new WP_Error('ghost_lms_invalid_drip_rule', __('The fixed-date drip rule is invalid.', 'ghost-lms'));
            }

            return true;
        }

        if ('days_after_enrollment' === $type) {
            $days = $decoded['days'] ?? null;
            if (! is_int($days) || $days <= 0) {
                return new WP_Error('ghost_lms_invalid_drip_rule', __('The enrollment-delay drip rule must use a positive day count.', 'ghost-lms'));
            }

            return true;
        }

        return new WP_Error('ghost_lms_invalid_drip_rule', __('The drip rule type is not supported.', 'ghost-lms'));
    }

    /**
     * Validate a prerequisite rule's cross-course and cycle constraints.
     *
     * @param int $lesson_id Lesson being saved.
     * @param array<string, mixed> $decoded Rule payload.
     * @return bool|WP_Error
     */
    public static function validate_prerequisite(int $lesson_id, array $decoded): bool|WP_Error
    {
        $required_lesson_id = absint($decoded['lesson_id'] ?? 0);
        if ($required_lesson_id <= 0) {
            return new WP_Error('ghost_lms_invalid_drip_rule', __('The prerequisite lesson is missing.', 'ghost-lms'));
        }

        if ('glms_lesson' !== get_post_type($required_lesson_id)) {
            return new WP_Error('ghost_lms_invalid_drip_rule', __('The prerequisite lesson does not exist.', 'ghost-lms'));
        }

        $this_course_id = self::get_course_for_lesson($lesson_id);
        $required_course_id = self::get_course_for_lesson($required_lesson_id);
        if ($this_course_id <= 0 || $required_course_id <= 0 || $this_course_id !== $required_course_id) {
            return new WP_Error('ghost_lms_invalid_drip_rule', __('A prerequisite lesson must belong to the same course.', 'ghost-lms'));
        }

        $curriculum = CurriculumRepository::get($this_course_id);
        $lesson_position = self::get_curriculum_position($curriculum, $lesson_id);
        $required_position = self::get_curriculum_position($curriculum, $required_lesson_id);
        if (null === $lesson_position || null === $required_position || $required_position >= $lesson_position) {
            return new WP_Error('ghost_lms_invalid_drip_rule', __('A prerequisite lesson must appear earlier in the course curriculum.', 'ghost-lms'));
        }

        $chain = self::collect_prerequisite_chain($required_lesson_id, [$required_lesson_id]);
        if (in_array($lesson_id, $chain, true)) {
            return new WP_Error('ghost_lms_invalid_drip_rule', __('This prerequisite rule would create a circular dependency.', 'ghost-lms'));
        }

        return true;
    }

    /**
     * Find a lesson's zero-based position in the normalized curriculum.
     *
     * @param array<int, array<string, mixed>> $curriculum Normalized curriculum.
     * @param int $lesson_id Lesson post ID.
     * @return int|null
     */
    private static function get_curriculum_position(array $curriculum, int $lesson_id): ?int
    {
        $position = 0;

        foreach ($curriculum as $module) {
            foreach ($module['lessons'] ?? [] as $lesson) {
                if (is_array($lesson) && (int) ($lesson['lesson_id'] ?? 0) === $lesson_id) {
                    return $position;
                }

                $position++;
            }
        }

        return null;
    }

    /**
     * Collect lesson IDs required by a prerequisite chain.
     *
     * @param int $lesson_id Starting lesson ID.
     * @param array<int, int> $seen Already visited lesson IDs.
     * @return array<int, int>
     */
    private static function collect_prerequisite_chain(int $lesson_id, array $seen): array
    {
        $rule = DripRule::from_lesson($lesson_id);
        if (! $rule || 'prerequisite' !== $rule->get_type()) {
            return $seen;
        }

        $required_lesson_id = $rule->get_prerequisite_lesson_id();
        if ($required_lesson_id <= 0 || in_array($required_lesson_id, $seen, true)) {
            return $seen;
        }

        $seen[] = $required_lesson_id;
        return self::collect_prerequisite_chain($required_lesson_id, $seen);
    }

    /**
     * Discover the owning course for a lesson.
     *
     * @param int $lesson_id Lesson post ID.
     * @return int
     */
    private static function get_course_for_lesson(int $lesson_id): int
    {
        $course_ids = array_map('absint', (array) get_post_meta($lesson_id, '_glms_course_ids', true));
        return (int) ($course_ids[0] ?? 0);
    }
}
