<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Model\NamespaceModel;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Model\PersistentVolume;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\Secret;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Stdlib\Filter\Rule;
use ipl\Web\Url;
use ipl\Web\Widget\EmptyState;
use ipl\Web\Widget\Icon;

abstract class Factory
{
    public static function createIcon(string $kind): ?ValidHtml
    {
        $kind = strtolower(str_replace(['_', '-'], '', $kind));

        return match ($kind) {
            'configmap',
            'container',
            'cronjob',
            'daemonset',
            'deployment',
            'event',
            'ingress',
            'job',
            'namespace',
            'persistentvolume',
            'persistentvolumeclaim',
            'pod',
            'replicaset',
            'secret',
            'service',
            'statefulset' => new HtmlElement('i', new Attributes(['class' => "icon kicon-$kind"])),
            'node'        => new Icon('share-nodes'),
            default       => null
        };
    }

    public static function createList(string $kind, Rule $filter): ValidHtml
    {
        $kind = strtolower(str_replace(['_', '-'], '', $kind));

        $database = Database::connection();

        switch ($kind) {
            case 'configmap':
                $q = ConfigMap::on($database)->filter($filter);

                return new ConfigMapList($q);
            case 'container':
                $q = Container::on($database)->filter($filter);

                return new ContainerList($q);
            case 'cronjob':
                $q = CronJob::on($database)->filter($filter);

                return new CronJobList($q);
            case 'daemonset':
                $q = DaemonSet::on($database)->filter($filter);

                return new DaemonSetList($q);
            case 'deployment':
                $q = Deployment::on($database)->filter($filter);

                return new DeploymentList($q);
            case 'event':
                $q = Event::on($database)->filter($filter);

                return new EventList($q);
            case 'ingress':
                $q = Ingress::on($database)->filter($filter);

                return new IngressList($q);
            case 'job':
                $q = Job::on($database)->filter($filter);

                return new JobList($q);
            case 'namespace':
                $q = NamespaceModel::on($database)->filter($filter);

                return new NamespaceList($q);
            case 'node':
                $q = Node::on($database)->filter($filter);

                return new NodeList($q);
            case 'persistentvolume':
                $q = PersistentVolume::on($database)->filter($filter);

                return new PersistentVolumeList($q);
            case 'persistentvolumeclaim':
                $q = PersistentVolumeClaim::on($database)->filter($filter);

                return new PersistentVolumeClaimList($q);
            case 'pod':
                $q = Pod::on($database)->filter($filter);

                return new PodList($q);
            case 'replicaset':
                $q = ReplicaSet::on($database)->filter($filter);

                return new ReplicaSetList($q);
            case 'secret':
                $q = Secret::on($database)->filter($filter);

                return new SecretList($q);
            case 'service':
                $q = Service::on($database)->filter($filter);

                return new ServiceList($q);
            case 'statefulset':
                $q = StatefulSet::on($database)->filter($filter);

                return new StatefulSetList($q);
            default:
                return new EmptyState("No items to display. $kind seems to be a custom resource.");
        }
    }

    public static function createModel(string $kind): ?Model
    {
        $kind = strtolower(str_replace(['_', '-'], '', $kind));

        return match ($kind) {
            'configmap'             => new ConfigMap(),
            'cronjob'               => new CronJob(),
            'daemonset'             => new DaemonSet(),
            'deployment'            => new Deployment(),
            'event'                 => new Event(),
            'ingress'               => new Ingress(),
            'job'                   => new Job(),
            'namespace'             => new NamespaceModel(),
            'node'                  => new Node(),
            'persistentvolume'      => new PersistentVolume(),
            'persistentvolumeclaim' => new PersistentVolumeClaim(),
            'pod'                   => new Pod(),
            'replicaset'            => new ReplicaSet(),
            'secret'                => new Secret(),
            'service'               => new Service(),
            'statefulset'           => new StatefulSet(),
            default                 => null,
        };
    }

    public static function createDetailUrl(string $kind): ?Url
    {
        $kind = strtolower(str_replace(['_', '-'], '', $kind));

        return match ($kind) {
            'configmap',
            'container',
            'cronjob',
            'daemonset',
            'deployment',
            'event',
            'ingress',
            'job',
            'namespace',
            'node',
            'persistentvolume',
            'persistentvolumeclaim',
            'pod',
            'replicaset',
            'secret',
            'service',
            'statefulset' => Url::fromPath("kubernetes/$kind"),
            default       => null
        };
    }

    public static function createListUrl(string $kind): ?Url
    {
        $kind = strtolower(str_replace(['_', '-'], '', $kind));

        $controller = match ($kind) {
            'configmap',
            'container',
            'cronjob',
            'daemonset',
            'deployment',
            'event',
            'job',
            'namespace',
            'node',
            'persistentvolume',
            'persistentvolumeclaim',
            'pod',
            'replicaset',
            'secret',
            'service',
            'statefulset' => "{$kind}s",
            'ingress'     => 'ingresses',
            default       => null
        };

        if ($controller !== null) {
            return Url::fromPath("kubernetes/$controller");
        }

        return null;
    }

    public static function getKindFromModel(Model $model): string
    {
        $kind = match (true) {
            $model instanceof ConfigMap,
            $model instanceof CronJob,
            $model instanceof DaemonSet,
            $model instanceof Deployment,
            $model instanceof Ingress,
            $model instanceof Job,
            $model instanceof Node,
            $model instanceof PersistentVolume,
            $model instanceof PersistentVolumeClaim,
            $model instanceof Pod,
            $model instanceof ReplicaSet,
            $model instanceof Secret,
            $model instanceof Service,
            $model instanceof StatefulSet => basename(str_replace('\\', '/', get_class($model))),
            default                       => null
        };

        return strtolower(str_replace(['_', '-'], '', $kind));
    }

    /**
     * Retrieves a resource by its kind.
     *
     * @param string $kind The kind of the resource
     *
     * @return Query|null
     */
    public static function fetchResource(string $kind): ?Query
    {
        $kind = strtolower(str_replace(['_', '-'], '', $kind));

        $database = Database::connection();

        $query = match ($kind) {
            'configmap'             => ConfigMap::on($database),
            'container'             => Container::on($database),
            'cronjob'               => CronJob::on($database),
            'daemonset'             => DaemonSet::on($database),
            'deployment'            => Deployment::on($database),
            'ingress'               => Ingress::on($database),
            'job'                   => Job::on($database),
            'node'                  => Node::on($database),
            'persistentvolumeclaim' => PersistentVolumeClaim::on($database),
            'pod'                   => Pod::on($database),
            'replicaset'            => ReplicaSet::on($database),
            'service'               => Service::on($database),
            'statefulset'           => StatefulSet::on($database),
            default                 => null,
        };

        return $query;
    }
}
