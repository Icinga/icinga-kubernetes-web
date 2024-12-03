<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\ConfigMap;
use Icinga\Module\Kubernetes\Model\Container;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Model\PersistentVolumeClaim;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodOwner;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\Service;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class PodEnvironment implements ValidHtml
{
    protected Pod $pod;

    public function __construct(Pod $pod)
    {
        $this->pod = $pod;
    }

    public function render(): ValidHtml
    {
        $parentsFilter = Filter::all(
            Filter::equal('namespace', $this->pod->namespace),
            Filter::equal('service.pod.uuid', Uuid::fromBytes($this->pod->uuid)->toString())
        );

        $services = $this->pod->service
            ->filter($parentsFilter)
            ->limit(3);

        $podOwner = PodOwner::on(Database::connection())
            ->filter(Filter::equal('pod_uuid', Uuid::fromBytes($this->pod->uuid)->toString()))->first();


        if ($podOwner !== null) {
            $podOwnerKind = Factory::fetchResource($podOwner->kind)
                ->filter(Filter::equal('uuid', Uuid::fromBytes($podOwner->owner_uuid)->toString()));
        } else {
            $podOwnerKind = null;
        }

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    Attributes::create(['class' => 'environment-widget-title']),
                    Text::create(t('Environment'))
                ),
                new Environment($this->pod, $services, $podOwnerKind, $parentsFilter, null)
            );
    }
}
