<?php
/**
 * Database migration runner for Ghost LMS.
 *
 * @package GhostLMS\Database
 */

declare(strict_types=1);

namespace GhostLMS\Database;

final class Migrator
{
    /**
     * Run the Ghost LMS database migration when the schema version is stale.
     *
     * @return void
     */
    public static function maybe_migrate(): void
    {
        if (! function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        if (! defined('GHOST_LMS_DB_VERSION')) {
            return;
        }

        $stored_version = get_option('ghost_lms_db_version', '');

        if ($stored_version === GHOST_LMS_DB_VERSION) {
            return;
        }

        foreach (Schema::get_tables() as $sql) {
            dbDelta($sql);
        }

        update_option('ghost_lms_db_version', GHOST_LMS_DB_VERSION);
    }
}
