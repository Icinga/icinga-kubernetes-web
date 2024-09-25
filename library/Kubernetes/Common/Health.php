<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

abstract class Health
{
    public const DEGRADED = 'degraded';

    public const HEALTHY = 'healthy';

    public const UNDECIDABLE = 'undecidable';

    public const UNHEALTHY = 'unhealthy';

    public static function icon(string $health): string
    {
        return match ($health) {
            static::HEALTHY     => Icons::HEALTH_HEALTHY,
            static::DEGRADED    => Icons::HEALTH_DEGRADED,
            static::UNHEALTHY   => Icons::HEALTH_UNHEALTHY,
            static::UNDECIDABLE => Icons::HEALTH_UNDECIDABLE,
            default             => Icons::BUG
        };
    }
}
