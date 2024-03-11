<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

use ipl\Web\Widget\Icon;

abstract class Icons
{
    public const BUG = 'bug';

    public const CONTAINER_RUNNING = 'circle-check';

    public const CONTAINER_TERMINATED = 'circle-exclamation';

    public const CONTAINER_WAITING = 'spinner';

    public const HEALTH_DEGRADED = 'exclamation-triangle';

    public const HEALTH_HEALTHY = 'circle-check';

    public const HEALTH_UNDECIDABLE = 'circle-question';

    public const HEALTH_UNHEALTHY = 'circle-exclamation';

    public const NAMESPACE_ACTIVE = 'circle-check';

    public const NAMESPACE_TERMINATING = 'circle-triangle';

    public const NODE_NOT_READY = 'circle-exclamation';

    public const NODE_READY = 'circle-check';

    public const POD_FAILED = 'circle-exclamation';

    public const POD_PENDING = 'spinner';

    public const POD_RUNNING = 'circle-check';

    public const POD_SUCCEEDED = 'hourglass-end';

    public const PVC_BOUND = 'link';

    public const PVC_LOST = 'file-circle-exclamation';

    public const PVC_PENDING = 'spinner';

    public const PV_AVAILABLE = 'file-circle-check';

    public const PV_BOUND = 'link';

    public const PV_FAILED = 'file-circle-exclamation';

    public const PV_PENDING = 'spinner';

    public const PV_RELEASED = 'link-slash';

    public static function ready(bool $ready): Icon
    {
        return new Icon($ready ? 'check' : 'xmark');
    }
}
