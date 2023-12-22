<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

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
                return Icons::HEALTHY;
            case static::DEGRADED:
                return Icons::DEGRADED;
            case static::UNHEALTHY:
                return Icons::UNHEALTHY;
            case static::UNDECIDABLE:
                return Icons::UNDECIDABLE;
            default:
                return Icons::BUG;
        }
    }
}
