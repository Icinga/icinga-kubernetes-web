<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\Environment;

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
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class ReplicaSetEnvironment implements ValidHtml
{
    use Translation;

    public function __construct(protected ReplicaSet $replicaSet)
    {
    }

    public function render(): ValidHtml
    {
        $replicaSetOwner = ReplicaSetOwner::on(Database::connection())
            ->filter(Filter::equal('replica_set_uuid', (string) Uuid::fromBytes($this->replicaSet->uuid)))->first();

        if ($replicaSetOwner !== null) {
            $deployments = Deployment::on(Database::connection())
                ->filter(Filter::equal('uuid', (string) Uuid::fromBytes($replicaSetOwner->owner_uuid)));
        } else {
            $deployments = null;
        }

        $childrenFilter = Filter::all(
            Filter::equal('namespace', $this->replicaSet->namespace),
            Filter::equal('pod.owner.owner_uuid', (string) Uuid::fromBytes($this->replicaSet->uuid))
        );

        $pods = Pod::on(Database::connection())
            ->filter($childrenFilter)
            ->limit(3);

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    new Attributes(['class' => 'environment-widget-title']),
                    new Text($this->translate('Environment'))
                ),
                new Environment($this->replicaSet, $deployments, $pods, null, $childrenFilter)
            );
    }
}
