<?php
/**
 * Drip-rule evaluation for lesson unlock checks.
 *
 * @package GhostLMS\Access
 */

declare(strict_types=1);

namespace GhostLMS\Access;

use DateTimeImmutable;
use GhostLMS\Database\EnrollmentRepository;
use GhostLMS\Database\LessonProgressRepository;

final class DripRule
{
    private string $type;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * Create a drip rule from a raw JSON-encoded rule payload.
     *
     * @param mixed $value Raw persisted rule payload.
     * @return self|null
     */
    public static function from_value(mixed $value): ?self
    {
        if (! is_string($value) || '' === trim($value)) {
            return null;
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded) || empty($decoded['type'])) {
            return null;
        }

        $type = sanitize_key((string) $decoded['type']);
        if ('' === $type) {
            return null;
        }

        $allowed_types = ['fixed_date', 'days_after_enrollment', 'prerequisite'];
        if (! in_array($type, $allowed_types, true)) {
            return null;
        }

        switch ($type) {
            case 'fixed_date':
                if (null === self::parse_fixed_date($decoded['date'] ?? null)) {
                    return null;
                }
                break;

            case 'days_after_enrollment':
                $days = $decoded['days'] ?? null;
                if (! is_int($days) || $days <= 0) {
                    return null;
                }
                break;

            case 'prerequisite':
                $required_lesson_id = $decoded['lesson_id'] ?? null;
                if (! is_numeric($required_lesson_id) || (int) $required_lesson_id <= 0) {
                    return null;
                }
                break;
        }

        return new self($type, $decoded);
    }

    /**
     * Parse a fixed-date rule using the site's timezone and documented format.
     *
     * @param mixed $value Raw date value.
     * @return DateTimeImmutable|null
     */
    public static function parse_fixed_date(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/D', $value)) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s', $value, wp_timezone());
        $errors = DateTimeImmutable::getLastErrors();
        if (false === $parsed || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $parsed;
    }

    /**
     * Build a drip rule from the lesson's persisted `_glms_drip_rule` meta.
     *
     * @param int $lesson_id Lesson post ID.
     * @return self|null
     */
    public static function from_lesson(int $lesson_id): ?self
    {
        if ($lesson_id <= 0) {
            return null;
        }

        return self::from_value((string) get_post_meta($lesson_id, '_glms_drip_rule', true));
    }

    /**
     * Create a new drip rule instance.
     *
     * @param string $type Rule type.
     * @param array<string, mixed> $config Rule payload.
     */
    public function __construct(string $type, array $config)
    {
        $this->type = $type;
        $this->config = $config;
    }

    /**
     * Determine whether the rule is satisfied for a user.
     *
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @param int $lesson_id Lesson post ID.
     * @return bool
     */
    public function is_satisfied(int $user_id, int $course_id, int $lesson_id): bool
    {
        if ($user_id <= 0 || $course_id <= 0 || $lesson_id <= 0) {
            return false;
        }

        switch ($this->type) {
            case 'fixed_date':
                return $this->is_fixed_date_unlocked();

            case 'days_after_enrollment':
                return $this->is_days_after_enrollment_unlocked($user_id, $course_id);

            case 'prerequisite':
                return $this->is_prerequisite_unlocked($user_id);

            default:
                return false;
        }
    }

    /**
     * Return a human-readable description of the rule.
     *
     * @return string
     */
    public function describe(): string
    {
        switch ($this->type) {
            case 'fixed_date':
                $unlock_at = self::parse_fixed_date($this->config['date'] ?? null);
                if (null === $unlock_at) {
                    return __('Unlocks on a future date.', 'ghost-lms');
                }

                return sprintf(__('Unlocks %s', 'ghost-lms'), wp_date(get_option('date_format'), $unlock_at->getTimestamp()));

            case 'days_after_enrollment':
                $days = absint($this->config['days'] ?? 0);
                if ($days <= 0) {
                    return __('Unlocks after enrollment.', 'ghost-lms');
                }
                return sprintf(_n('Unlocks %s day after enrollment', 'Unlocks %s days after enrollment', $days, 'ghost-lms'), (string) $days);

            case 'prerequisite':
                $required_lesson_id = absint($this->config['lesson_id'] ?? 0);
                if ($required_lesson_id <= 0) {
                    return __('Complete the prerequisite lesson first.', 'ghost-lms');
                }

                $required_lesson_title = get_the_title($required_lesson_id);
                if (is_string($required_lesson_title) && '' !== $required_lesson_title) {
                    return sprintf(__('Complete "%s" first', 'ghost-lms'), $required_lesson_title);
                }

                return __('Complete the prerequisite lesson first.', 'ghost-lms');

            default:
                return __('This lesson is locked by a drip rule.', 'ghost-lms');
        }
    }

    /**
     * Return the rule type.
     *
     * @return string
     */
    public function get_type(): string
    {
        return $this->type;
    }

    /**
     * Return the referenced prerequisite lesson ID, if any.
     *
     * @return int
     */
    public function get_prerequisite_lesson_id(): int
    {
        if ('prerequisite' !== $this->type) {
            return 0;
        }

        return absint($this->config['lesson_id'] ?? 0);
    }

    /**
     * Determine whether a fixed-date rule is satisfied.
     *
     * @return bool
     */
    private function is_fixed_date_unlocked(): bool
    {
        $unlock_at = self::parse_fixed_date($this->config['date'] ?? null);
        if (null === $unlock_at) {
            return false;
        }

        return current_datetime()->getTimestamp() >= $unlock_at->getTimestamp();
    }

    /**
     * Determine whether a days-after-enrollment rule is satisfied.
     *
     * @param int $user_id WordPress user ID.
     * @param int $course_id Course post ID.
     * @return bool
     */
    private function is_days_after_enrollment_unlocked(int $user_id, int $course_id): bool
    {
        $days = $this->config['days'] ?? null;
        if (! is_int($days) || $days <= 0) {
            return false;
        }

        $enrolled_at = EnrollmentRepository::get_enrolled_at($user_id, $course_id);
        if (null === $enrolled_at || '' === $enrolled_at) {
            return false;
        }

        $enrolled_timestamp = strtotime((string) $enrolled_at);
        if (false === $enrolled_timestamp) {
            return false;
        }

        return current_datetime()->getTimestamp() >= $enrolled_timestamp + ($days * DAY_IN_SECONDS);
    }

    /**
     * Determine whether a prerequisite rule is satisfied.
     *
     * @param int $user_id WordPress user ID.
     * @return bool
     */
    private function is_prerequisite_unlocked(int $user_id): bool
    {
        $required_lesson_id = $this->get_prerequisite_lesson_id();
        if ($required_lesson_id <= 0) {
            return false;
        }

        return LessonProgressRepository::is_complete($user_id, $required_lesson_id);
    }
}
