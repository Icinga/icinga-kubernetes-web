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
        switch ($health) {
            case static::HEALTHY:
                return Icons::HEALTH_HEALTHY;
            case static::DEGRADED:
                return Icons::HEALTH_DEGRADED;
            case static::UNHEALTHY:
                return Icons::HEALTH_UNHEALTHY;
            case static::UNDECIDABLE:
                return Icons::HEALTH_UNDECIDABLE;
            default:
                return Icons::BUG;
        }
    }
}
