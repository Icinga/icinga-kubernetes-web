<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Authentication\Auth as IcingaAuth;

class Permissions
{
    public const SHOW_CONFIG_MAPS = 'kubernetes/config-maps/show';
    public const SHOW_CRON_JOBS = 'kubernetes/cron-jobs/show';
    public const SHOW_DAEMON_SETS = 'kubernetes/daemon-sets/show';
    public const SHOW_DEPLOYMENTS = 'kubernetes/deployments/show';
    public const SHOW_EVENTS = 'kubernetes/events/show';
    public const SHOW_INGRESSES = 'kubernetes/ingresses/show';
    public const SHOW_JOBS = 'kubernetes/jobs/show';
    public const SHOW_NAMESPACES = 'kubernetes/namespaces/show';
    public const SHOW_NODES = 'kubernetes/nodes/show';
    public const SHOW_PERSISTENT_VOLUMES = 'kubernetes/persistent-volumes/show';
    public const SHOW_PERSISTENT_VOLUME_CLAIMS = 'kubernetes/persistent-volume-claims/show';
    public const SHOW_PODS = 'kubernetes/pods/show';
    public const SHOW_REPLICA_SET = 'kubernetes/replica-sets/show';
    public const SHOW_SERVICES = 'kubernetes/services/show';
    public const SHOW_SECRETS = 'kubernetes/secrets/show';
    public const SHOW_STATEFUL_SET = 'kubernetes/stateful-sets/show';

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
            'configmap'             => static::SHOW_CONFIG_MAPS,
            'cronjob'               => static::SHOW_CRON_JOBS,
            'daemonset'             => static::SHOW_DAEMON_SETS,
            'deployment'            => static::SHOW_DEPLOYMENTS,
            'event'                 => static::SHOW_EVENTS,
            'ingress'               => static::SHOW_INGRESSES,
            'job'                   => static::SHOW_JOBS,
            'namespace'             => static::SHOW_NAMESPACES,
            'node'                  => static::SHOW_NODES,
            'persistentvolume'      => static::SHOW_PERSISTENT_VOLUMES,
            'persistentvolumeclaim' => static::SHOW_PERSISTENT_VOLUME_CLAIMS,
            'pod'                   => static::SHOW_PODS,
            'replicaset'            => static::SHOW_REPLICA_SET,
            'secret'                => static::SHOW_SECRETS,
            'service'               => static::SHOW_SERVICES,
            'statefulset'           => static::SHOW_STATEFUL_SET,
        ];

        if (isset($permissions[$kind])) {
            return Permissions::getInstance()->auth->hasPermission($permissions[$kind]);
        }

        return null;
    }

    public function canShowYaml(): bool
    {
        return Permissions::getInstance()->auth->hasPermission('kubernetes/yaml/show');
    }
}
