<?php
/**
 * Uninstall Ghost LMS.
 *
 * @package GhostLMS
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

global $wpdb;

if (get_option('ghost_lms_drop_tables_on_uninstall', false)) {
    $tables = [
        "{$wpdb->prefix}glms_enrollments",
        "{$wpdb->prefix}glms_lesson_progress",
        "{$wpdb->prefix}glms_quiz_attempts",
        "{$wpdb->prefix}glms_cohorts",
        "{$wpdb->prefix}glms_cohort_members",
        "{$wpdb->prefix}glms_certificates",
        "{$wpdb->prefix}glms_forum_posts",
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}

GhostLMS\Access\Capabilities::remove_role_and_capabilities();
