<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Model\ConfigMap;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\InitContainer;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\Secret;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Model\SidecarContainer;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use ipl\Web\Url;
use Ramsey\Uuid\Uuid;

abstract class Links
{
    public static function configMap(ConfigMap $configMap): Url
    {
        return Url::fromPath('kubernetes/configmap', ['id' => (string) Uuid::fromBytes($configMap->uuid)]);
    }

    public static function container(Container $container): Url
    {
        return Url::fromPath('kubernetes/container', ['id' => (string) Uuid::fromBytes($container->uuid)]);
    }

    public static function cronJob(CronJob $cronjob): Url
    {
        return Url::fromPath('kubernetes/cronjob', ['id' => (string) Uuid::fromBytes($cronjob->uuid)]);
    }

    public static function daemonSet(DaemonSet $daemonSet): Url
    {
        return Url::fromPath('kubernetes/daemonset', ['id' => (string) Uuid::fromBytes($daemonSet->uuid)]);
    }

    public static function deployment(Deployment $deployment): Url
    {
        return Url::fromPath('kubernetes/deployment', ['id' => (string) Uuid::fromBytes($deployment->uuid)]);
    }

    public static function event(Event $event): Url
    {
        return Url::fromPath('kubernetes/event', ['id' => (string) Uuid::fromBytes($event->uuid)]);
    }

    public static function ingress(Ingress $ingress): Url
    {
        return Url::fromPath('kubernetes/ingress', ['id' => (string) Uuid::fromBytes($ingress->uuid)]);
    }

    public static function initContainer(InitContainer $initContainer): Url
    {
        return Url::fromPath('kubernetes/init-container', ['id' => (string) Uuid::fromBytes($initContainer->uuid)]);
    }

    public static function job(Job $job): Url
    {
        return Url::fromPath('kubernetes/job', ['id' => (string) Uuid::fromBytes($job->uuid)]);
    }

    public static function namespace(NamespaceModel $namespace): Url
    {
        return Url::fromPath('kubernetes/namespace', ['id' => (string) Uuid::fromBytes($namespace->uuid)]);
    }

    public static function node(Node $node): Url
    {
        return Url::fromPath('kubernetes/node', ['id' => (string) Uuid::fromBytes($node->uuid)]);
    }

    public static function persistentVolume(PersistentVolume $persistentVolume): Url
    {
        return Url::fromPath(
            'kubernetes/persistentvolume',
            ['id' => (string) Uuid::fromBytes($persistentVolume->uuid)]
        );
    }

    public static function pod(Pod $pod): Url
    {
        return Url::fromPath('kubernetes/pod', ['id' => (string) Uuid::fromBytes($pod->uuid)]);
    }

    public static function pvc(PersistentVolumeClaim $persistentVolumeClaim): Url
    {
        return Url::fromPath(
            'kubernetes/persistentvolumeclaim',
            ['id' => (string) Uuid::fromBytes($persistentVolumeClaim->uuid)]
        );
    }

    public static function replicaSet(ReplicaSet $replicaSet): Url
    {
        return Url::fromPath('kubernetes/replicaset', ['id' => (string) Uuid::fromBytes($replicaSet->uuid)]);
    }

    public static function secret(Secret $secret): Url
    {
        return Url::fromPath('kubernetes/secret', ['id' => (string) Uuid::fromBytes($secret->uuid)]);
    }

    public static function service(Service $service): Url
    {
        return Url::fromPath('kubernetes/service', ['id' => (string) Uuid::fromBytes($service->uuid)]);
    }

    public static function sidecarContainer(SidecarContainer $sidecarContainer): Url
    {
        return Url::fromPath(
            'kubernetes/sidecar-container',
            ['id' => (string) Uuid::fromBytes($sidecarContainer->uuid)]
        );
    }

    public static function statefulSet(StatefulSet $statefulSet): Url
    {
        return Url::fromPath('kubernetes/statefulset', ['id' => (string) Uuid::fromBytes($statefulSet->uuid)]);
    }
}
