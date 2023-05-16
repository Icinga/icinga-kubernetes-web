<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

use LogicException;

abstract class States
{
    public const HEALTHY = 'healthy';

    public const DEGRADED = 'degraded';

    public const UNHEALTHY = 'unhealthy';

    public const UNDECIDABLE = 'undecidable';

    public static function icon(string $state): string
    {
        switch ($state) {
            case static::HEALTHY:
                return Icons::HEALTHY;
            case static::DEGRADED:
                return Icons::DEGRADED;
            case static::UNHEALTHY:
                return Icons::UNHEALTHY;
            case static::UNDECIDABLE:
                return Icons::UNDECIDABLE;
            default:
                throw new LogicException();
        }
    }
}
