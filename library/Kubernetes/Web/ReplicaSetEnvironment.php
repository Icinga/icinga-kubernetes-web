<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\ReplicaSetOwner;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class ReplicaSetEnvironment implements ValidHtml
{
    protected ReplicaSet $replicaSet;

    public function __construct(ReplicaSet $replicaSet)
    {
        $this->replicaSet = $replicaSet;
    }

    public function render(): ValidHtml
    {
        $replicaSetOwner = ReplicaSetOwner::on(Database::connection())
            ->filter(Filter::equal('replica_set_uuid', Uuid::fromBytes($this->replicaSet->uuid)->toString()))->first();

        if ($replicaSetOwner !== null) {
            $deployments = Deployment::on(Database::connection())
                ->filter(Filter::equal('uuid', Uuid::fromBytes($replicaSetOwner->owner_uuid)->toString()));
        } else {
            $deployments = null;
        }

        $childrenFilter = Filter::all(
            Filter::equal('namespace', $this->replicaSet->namespace),
            Filter::equal('pod.owner.owner_uuid', Uuid::fromBytes($this->replicaSet->uuid)->toString())
        );

        $pods = Pod::on(Database::connection())
            ->filter($childrenFilter)
            ->limit(3);

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    Attributes::create(['class' => 'environment-widget-title']),
                    Text::create(t('Environment'))
                ),
                new Environment($this->replicaSet, $deployments, $pods, null, $childrenFilter)
            );
    }
}
