<?php
/**
 * REST endpoint for lesson drip rules.
 *
 * @package GhostLMS\Rest
 */

declare(strict_types=1);

namespace GhostLMS\Rest;

use GhostLMS\Access\Capabilities;
use GhostLMS\Access\DripRule;
use GhostLMS\Access\DripRuleValidator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class LessonDripRuleController
{
    /**
     * Register the lesson drip-rule REST route.
     *
     * @return void
     */
    public function register(): void
    {
        register_rest_route(
            'ghost-lms/v1',
            '/lessons/(?P<id>\d+)/drip-rule',
            [
                'methods' => 'PUT',
                'callback' => [$this, 'save'],
                'permission_callback' => [$this, 'permission_callback'],
                'args' => [
                    'id' => ['sanitize_callback' => 'absint'],
                ],
            ]
        );
    }

    /**
     * Check whether the current user may manage the lesson's course.
     *
     * @param WP_REST_Request $request Current REST request.
     * @return bool|WP_Error True when allowed, otherwise a permission error.
     */
    public function permission_callback(WP_REST_Request $request): bool|WP_Error
    {
        $lesson_id = absint($request->get_param('id'));
        $lesson = get_post($lesson_id);
        $course_id = $this->get_course_id($lesson_id);

        if (! $lesson || 'glms_lesson' !== $lesson->post_type || $course_id <= 0 || ! Capabilities::current_user_can_manage_course($course_id)) {
            return new WP_Error('ghost_lms_forbidden', __('You do not have permission to manage this lesson drip rule.', 'ghost-lms'), ['status' => 403]);
        }

        return true;
    }

    /**
     * Save or clear a lesson's drip rule.
     *
     * @param WP_REST_Request $request Current REST request.
     * @return WP_REST_Response|WP_Error Saved rule response or validation error.
     */
    public function save(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $lesson_id = absint($request->get_param('id'));
        $body = trim((string) $request->get_body());
        $rule_json = 'null' === $body ? null : $this->encode_payload($request->get_json_params());

        if (false === $rule_json) {
            return new WP_Error('ghost_lms_invalid_drip_rule', __('The drip rule must be a JSON object or null.', 'ghost-lms'), ['status' => 400]);
        }

        $validation = DripRuleValidator::validate($lesson_id, $rule_json);
        if ($validation instanceof WP_Error) {
            $validation->add_data(['status' => 400]);
            return $validation;
        }

        if (null === $rule_json) {
            delete_post_meta($lesson_id, '_glms_drip_rule');
        } else {
            update_post_meta($lesson_id, '_glms_drip_rule', $rule_json);
        }

        $rule = DripRule::from_lesson($lesson_id);

        return new WP_REST_Response(
            [
                'lesson_id' => $lesson_id,
                'rule' => null === $rule ? null : $this->get_rule_response($rule, $rule_json),
            ],
            200
        );
    }

    /**
     * Encode a decoded JSON object for validation and storage.
     *
     * @param mixed $payload Decoded request payload.
     * @return string|false Encoded payload, or false for an invalid payload.
     */
    private function encode_payload(mixed $payload): string|false
    {
        if (! is_array($payload)) {
            return false;
        }

        $encoded = wp_json_encode($payload);
        return is_string($encoded) ? $encoded : false;
    }

    /**
     * Build the REST representation of a stored drip rule.
     *
     * @param DripRule $rule      Parsed drip rule.
     * @param string   $rule_json Stored JSON configuration.
     * @return array<string, mixed>
     */
    private function get_rule_response(DripRule $rule, string $rule_json): array
    {
        $decoded = json_decode($rule_json, true);
        return [
            'type' => $rule->get_type(),
            'config' => is_array($decoded) ? $decoded : [],
            'description' => $rule->describe(),
        ];
    }

    /**
     * Get the first course associated with a lesson.
     *
     * @param int $lesson_id Lesson post ID.
     * @return int Associated course post ID, or zero when none exists.
     */
    private function get_course_id(int $lesson_id): int
    {
        return (int) (array_map('absint', (array) get_post_meta($lesson_id, '_glms_course_ids', true))[0] ?? 0);
    }

}
