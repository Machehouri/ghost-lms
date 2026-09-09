<?php
/**
 * Shared registry contract for WordPress content objects.
 *
 * @package GhostLMS\PostTypes
 */

declare(strict_types=1);

namespace GhostLMS\PostTypes;

interface Registrable
{
    public function register(): void;
}
