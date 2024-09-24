<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Authentication\Auth as IcingaAuth;

class Permissions
{
    public const LIST_CONFIG_MAPS = 'kubernetes/list/config-maps';
    public const LIST_CRON_JOBS = 'kubernetes/list/cron-jobs';
    public const LIST_DAEMON_SETS = 'kubernetes/list/daemon-sets';
    public const LIST_DEPLOYMENTS = 'kubernetes/list/deployments';
    public const LIST_EVENTS = 'kubernetes/list/events';
    public const LIST_INGRESSES = 'kubernetes/list/ingresses';
    public const LIST_JOBS = 'kubernetes/list/jobs';
    public const LIST_NAMESPACES = 'kubernetes/list/namespaces';
    public const LIST_NODES = 'kubernetes/list/nodes';
    public const LIST_PERSISTENT_VOLUMES = 'kubernetes/list/persistent-volumes';
    public const LIST_PERSISTENT_VOLUME_CLAIMS = 'kubernetes/list/persistent-volume-claims';
    public const LIST_PODS = 'kubernetes/list/pods';
    public const LIST_REPLICA_SET = 'kubernetes/list/replica-sets';
    public const LIST_SERVICES = 'kubernetes/list/services';
    public const LIST_SECRETS = 'kubernetes/list/secrets';
    public const LIST_STATEFUL_SET = 'kubernetes/list/stateful-sets';

    protected IcingaAuth $auth;

    private static self $instance;

    private function __construct()
    {
        $this->auth = IcingaAuth::getInstance();
    }

    public static function getInstance(): static
    {
        if (! isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function canList(string $kind): ?bool
    {
        $kind = strtolower(str_replace(['_', '-'], '', $kind));

        $permissions = [
            'configmap'             => static::LIST_CONFIG_MAPS,
            'cronjob'               => static::LIST_CRON_JOBS,
            'daemonset'             => static::LIST_DAEMON_SETS,
            'deployment'            => static::LIST_DEPLOYMENTS,
            'event'                 => static::LIST_EVENTS,
            'ingress'               => static::LIST_INGRESSES,
            'job'                   => static::LIST_JOBS,
            'namespace'             => static::LIST_NAMESPACES,
            'node'                  => static::LIST_NODES,
            'persistentvolume'      => static::LIST_PERSISTENT_VOLUMES,
            'persistentvolumeclaim' => static::LIST_PERSISTENT_VOLUME_CLAIMS,
            'pod'                   => static::LIST_PODS,
            'replicaset'            => static::LIST_REPLICA_SET,
            'secret'                => static::LIST_SECRETS,
            'service'               => static::LIST_SERVICES,
            'statefulset'           => static::LIST_STATEFUL_SET,
        ];

        if (isset($permissions[$kind])) {
            return Permissions::getInstance()->auth->hasPermission($permissions[$kind]);
        }

        return null;
    }

    public function canShowYaml(): bool
    {
        return Permissions::getInstance()->auth->hasPermission('kubernetes/show-yaml');
    }
}
