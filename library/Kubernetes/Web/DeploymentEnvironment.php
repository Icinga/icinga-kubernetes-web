<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Deployment;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class DeploymentEnvironment implements ValidHtml
{
    private Deployment $deployment;

    public function __construct($deployment)
    {
        $this->deployment = $deployment;
    }

    public function render(): ValidHtml
    {
        $childrenFilter = Filter::all(
            Filter::equal('namespace', $this->deployment->namespace),
            Filter::equal('replica_set.owner.owner_uuid', Uuid::fromBytes($this->deployment->uuid)->toString())
        );

        $replicaSets = ReplicaSet::on(Database::connection())
            ->filter($childrenFilter)
            ->limit(3);

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    Attributes::create(['class' => 'environment-widget-title']),
                    Text::create(t('Environment'))
                ),
                new Environment($this->deployment, null, $replicaSets, $childrenFilter)
            );
    }
}
