<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\ItemList;

use Icinga\Exception\NotImplementedError;
use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\DetailActions;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Favorite;
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
use Icinga\Module\Kubernetes\View\ConfigMapRenderer;
use Icinga\Module\Kubernetes\View\ContainerRenderer;
use Icinga\Module\Kubernetes\View\CronJobRenderer;
use Icinga\Module\Kubernetes\View\DaemonSetRenderer;
use Icinga\Module\Kubernetes\View\DeploymentRenderer;
use Icinga\Module\Kubernetes\View\EventRenderer;
use Icinga\Module\Kubernetes\View\IngressRenderer;
use Icinga\Module\Kubernetes\View\InitContainerRenderer;
use Icinga\Module\Kubernetes\View\JobRenderer;
use Icinga\Module\Kubernetes\View\NamespaceRenderer;
use Icinga\Module\Kubernetes\View\NodeRenderer;
use Icinga\Module\Kubernetes\View\PersistentVolumeClaimRenderer;
use Icinga\Module\Kubernetes\View\PersistentVolumeRenderer;
use Icinga\Module\Kubernetes\View\PodRenderer;
use Icinga\Module\Kubernetes\View\ReplicaSetRenderer;
use Icinga\Module\Kubernetes\View\ResourceDefaultItemLayout;
use Icinga\Module\Kubernetes\View\ResourceDetailedItemLayout;
use Icinga\Module\Kubernetes\View\ResourceMinimalItemLayout;
use Icinga\Module\Kubernetes\View\SecretRenderer;
use Icinga\Module\Kubernetes\View\ServiceRenderer;
use Icinga\Module\Kubernetes\View\SidecarContainerRenderer;
use Icinga\Module\Kubernetes\View\StatefulSetRenderer;
use Icinga\Module\Kubernetes\Web\Factory;
use ipl\Orm\Model;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\ItemList;
use ipl\Web\Widget\ListItem;

class ResourceList extends ItemList
{
    use DetailActions;

    public function __construct($data)
    {
        parent::__construct($data, function (Model $item) {
            return match ($item::class) {
                ConfigMap::class             => new ConfigMapRenderer(),
                Container::class             => new ContainerRenderer(),
                CronJob::class               => new CronJobRenderer(),
                DaemonSet::class             => new DaemonSetRenderer(),
                Deployment::class            => new DeploymentRenderer(),
                Event::class                 => new EventRenderer(),
                Ingress::class               => new IngressRenderer(),
                InitContainer::class         => new InitContainerRenderer(),
                Job::class                   => new JobRenderer(),
                NamespaceModel::class        => new NamespaceRenderer(),
                Node::class                  => new NodeRenderer(),
                PersistentVolume::class      => new PersistentVolumeRenderer(),
                PersistentVolumeClaim::class => new PersistentVolumeClaimRenderer(),
                Pod::class                   => new PodRenderer(),
                ReplicaSet::class            => new ReplicaSetRenderer(),
                Secret::class                => new SecretRenderer(),
                Service::class               => new ServiceRenderer(),
                SidecarContainer::class      => new SidecarContainerRenderer(),
                StatefulSet::class           => new StatefulSetRenderer(),
                default                      => throw new NotImplementedError('Not implemented')
            };
        });
    }

    protected function init(): void
    {
        $this->initializeDetailActions();
    }

    /**
     * Set the view mode
     *
     * @param ViewMode $mode
     *
     * @return $this
     */
    public function setViewMode(ViewMode $mode): self
    {
        return $this->setItemLayoutClass(match ($mode) {
            ViewMode::Minimal  => ResourceMinimalItemLayout::class,
            ViewMode::Common   => ResourceDefaultItemLayout::class,
            ViewMode::Detailed => ResourceDetailedItemLayout::class,
        });
    }

    /**
     * Extend the created list item
     *
     * @param object $data
     *
     * @return ListItem
     */
    protected function createListItem(object $data): ListItem
    {
        $item = parent::createListItem($data);

        $favorite = Favorite::on(Database::connection())
            ->filter(
                Filter::all(
                    Filter::equal('resource_uuid', $data->uuid),
                    Filter::equal('username', Auth::getInstance()->getUser()->getUsername())
                )
            )
            ->first();

        if ($favorite !== null) {
            $item->addAttributes(['class' => 'favored']);
        }

        $this->setDetailUrl(Factory::createDetailUrl(Factory::canonicalizeKind($data->getTableName())));


        if (! $this->getDetailActionsDisabled()) {
            $this->addDetailFilterAttribute($item, Filter::equal('id', $data->uuid));
        }

        return $item;
    }
}
