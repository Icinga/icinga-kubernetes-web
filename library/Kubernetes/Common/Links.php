<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Common;

use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use ipl\Web\Url;

abstract class Links
{
    public static function container(Container $container): Url
    {
        return Url::fromPath('kubernetes/container', ['id' => bin2hex($container->id)]);
    }

    public static function node(Node $node): Url
    {
        return Url::fromPath('kubernetes/node', ['id' => bin2hex($node->id)]);
    }

    public static function pod(Pod $pod): Url
    {
        return Url::fromPath('kubernetes/pod', ['id' => bin2hex($pod->id)]);
    }

    public static function namespace(NamespaceModel $namespace): Url
    {
        return Url::fromPath('kubernetes/namespace', ['id' => bin2hex($namespace->id)]);
    }

    public static function deployment(Deployment $deployment): Url
    {
        return Url::fromPath('kubernetes/deployment', ['id' => bin2hex($deployment->id)]);
    }

    public static function statefulSet(StatefulSet $statefulSet): Url
    {
        return Url::fromPath('kubernetes/statefulset', ['id' => bin2hex($statefulSet->id)]);
    }

    public static function replicaSet(ReplicaSet $replicaSet): Url
    {
        return Url::fromPath('kubernetes/replicaSet', ['id' => bin2hex($replicaSet->id)]);
    }

    public static function daemonSet(DaemonSet $daemonSet): Url
    {
        return Url::fromPath('kubernetes/daemonset', ['id' => bin2hex($daemonSet->id)]);
    }

    public static function event(Event $event): Url
    {
        return Url::fromPath('kubernetes/event', ['id' => bin2hex($event->id)]);
    }

    public static function pvc(PersistentVolumeClaim $persistentVolumeClaim): Url
    {
        return Url::fromPath('kubernetes/persistentvolumeclaim', ['id' => bin2hex($persistentVolumeClaim->id)]);
    }

    public static function persistentVolume(PersistentVolume $persistentVolume): Url
    {
        return Url::fromPath('kubernetes/persistentvolume', ['id' => bin2hex($persistentVolume->id)]);
    }
}
