<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

abstract class Icons
{
    public const POD_PENDING = 'circle-exclamation';

    public const POD_RUNNING = 'exclamation-triangle';

    public const POD_SUCCEEDED = 'circle-check';

    public const POD_FAILED = 'exclamation-triangle';

    public const DEPLOYMENT_HEALTHY = 'circle-check';

    public const DEPLOYMENT_UNHEALTHY = 'exclamation-triangle';

    public const DEPLOYMENT_CRITICAL = 'circle-exclamation';

    public const DEPLOYMENT_UNKNOWN = 'pause-circle';

    public const CRITICAL = 'circle-exclamation';

    public const ERROR = 'circle-xmark';

    public const USER = 'user';

    public const USER_MANAGER = 'user-tie';

    public const CLOSED = 'circle-check';

    public const OPENED = 'sun';

    public const MANAGE = 'circle-check';

    public const UNMANAGE = 'circle-xmark';

    public const UNSUBSCRIBED = 'circle-xmark';

    public const SUBSCRIBED = 'circle-check';

    public const TRIGGERED = 'square-up-right';

    public const NOTIFIED = 'paper-plane';

    public const SUBSCRIBE = 'sync-alt';
}
