<?php
/**
 * Database schema definitions for Ghost LMS.
 *
 * @package GhostLMS\Database
 */

declare(strict_types=1);

namespace GhostLMS\Database;

final class Schema
{
    /**
     * Return all Ghost LMS table create statements.
     *
     * @return array<string, string>
     */
    public static function get_tables(): array
    {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        return [
            "{$wpdb->prefix}glms_enrollments" => "CREATE TABLE {$wpdb->prefix}glms_enrollments (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                course_id bigint(20) unsigned NOT NULL,
                order_id bigint(20) unsigned DEFAULT NULL,
                status varchar(20) NOT NULL,
                enrolled_at datetime NOT NULL,
                expires_at datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_course_idx (user_id, course_id),
                KEY course_id_idx (course_id)
            ) {$charset};",
            "{$wpdb->prefix}glms_lesson_progress" => "CREATE TABLE {$wpdb->prefix}glms_lesson_progress (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                lesson_id bigint(20) unsigned NOT NULL,
                course_id bigint(20) unsigned NOT NULL,
                status varchar(32) NOT NULL,
                completed_at datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_lesson_idx (user_id, lesson_id),
                KEY user_course_idx (user_id, course_id)
            ) {$charset};",
            "{$wpdb->prefix}glms_quiz_attempts" => "CREATE TABLE {$wpdb->prefix}glms_quiz_attempts (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                quiz_id bigint(20) unsigned NOT NULL,
                score double DEFAULT NULL,
                passed tinyint(1) DEFAULT NULL,
                started_at datetime NOT NULL,
                submitted_at datetime DEFAULT NULL,
                answers longtext NOT NULL,
                PRIMARY KEY  (id),
                KEY user_quiz_idx (user_id, quiz_id),
                KEY quiz_id_idx (quiz_id)
            ) {$charset};",
            "{$wpdb->prefix}glms_cohorts" => "CREATE TABLE {$wpdb->prefix}glms_cohorts (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                course_id bigint(20) unsigned NOT NULL,
                name varchar(255) NOT NULL,
                start_date date NOT NULL,
                capacity int DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY course_id_idx (course_id)
            ) {$charset};",
            "{$wpdb->prefix}glms_cohort_members" => "CREATE TABLE {$wpdb->prefix}glms_cohort_members (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                cohort_id bigint(20) unsigned NOT NULL,
                user_id bigint(20) unsigned NOT NULL,
                joined_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY cohort_user_idx (cohort_id, user_id)
            ) {$charset};",
            "{$wpdb->prefix}glms_certificates" => "CREATE TABLE {$wpdb->prefix}glms_certificates (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                course_id bigint(20) unsigned NOT NULL,
                issued_at datetime NOT NULL,
                file_path varchar(255) NOT NULL,
                verification_code varchar(255) NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_course_idx (user_id, course_id),
                UNIQUE KEY verification_code_idx (verification_code)
            ) {$charset};",
            "{$wpdb->prefix}glms_forum_posts" => "CREATE TABLE {$wpdb->prefix}glms_forum_posts (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                course_id bigint(20) unsigned NOT NULL,
                lesson_id bigint(20) unsigned DEFAULT NULL,
                user_id bigint(20) unsigned NOT NULL,
                parent_id bigint(20) unsigned DEFAULT NULL,
                body longtext NOT NULL,
                status varchar(20) NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY course_id_idx (course_id),
                KEY course_lesson_idx (course_id, lesson_id)
            ) {$charset};",
        ];
    }
}
