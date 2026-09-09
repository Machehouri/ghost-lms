<?php
/**
 * Activation bootstrap.
 *
 * @package GhostLMS\Core
 */

declare(strict_types=1);

namespace GhostLMS\Core;

use GhostLMS\Access\Capabilities;

final class Activator
{
    public static function activate(): void
    {
        Capabilities::register_role();
        Capabilities::add_admin_capabilities();
    }
}
