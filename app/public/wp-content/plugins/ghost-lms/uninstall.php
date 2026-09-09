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

GhostLMS\Access\Capabilities::remove_role_and_capabilities();
