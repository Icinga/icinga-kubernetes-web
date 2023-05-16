<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

abstract class Icons
{
    public const POD_PENDING = 'spinner';

    public const POD_RUNNING = 'circle-check';

    public const POD_SUCCEEDED = 'hourglass-end';

    public const POD_FAILED = 'circle-triangle';

    public const DEPLOYMENT_HEALTHY = 'circle-check';

    public const DEPLOYMENT_UNHEALTHY = 'exclamation-triangle';

    public const DEPLOYMENT_CRITICAL = 'circle-exclamation';

    public const DEPLOYMENT_UNKNOWN = 'pause-circle';

    public const STATEFULSET_HEALTHY = 'circle-check';

    public const STATEFULSET_UNHEALTHY = 'exclamation-triangle';

    public const STATEFULSET_CRITICAL = 'circle-exclamation';

    public const HEALTHY = 'circle-check';

    public const DEGRADED = 'exclamation-triangle';

    public const UNHEALTHY = 'circle-exclamation';

    public const UNDECIDABLE = 'circle-check';

    public const STATEFULSET_UNKNOWN = 'pause-circle';

    public const REPLICASET_HEALTHY = 'circle-check';

    public const REPLICASET_UNHEALTHY = 'exclamation-triangle';

    public const REPLICASET_CRITICAL = 'circle-exclamation';

    public const REPLICASET_UNKNOWN = 'pause-circle';

    public const DAEMONSET_HEALTHY = 'circle-check';

    public const DAEMONSET_UNHEALTHY = 'exclamation-triangle';

    public const DAEMONSET_UNKNOWN = 'pause-circle';

    public const EVENT_NORMAL = 'circle-check';

    public const EVENT_WARNING = 'exclamation-triangle';

    public const EVENT_UNKNOWN = 'pause-circle';

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
