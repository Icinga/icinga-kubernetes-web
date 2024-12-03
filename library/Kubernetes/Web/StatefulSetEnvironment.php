<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class StatefulSetEnvironment implements ValidHtml
{
    private StatefulSet $statefulSet;

    public function __construct($statefulSet)
    {
        $this->statefulSet = $statefulSet;
    }

    public function render(): ValidHtml
    {
        $childrenFilter = Filter::all(
            Filter::equal('namespace', $this->statefulSet->namespace),
            Filter::equal('pod.owner.owner_uuid', Uuid::fromBytes($this->statefulSet->uuid)->toString())
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
                new Environment($this->statefulSet, null, $pods, null, $childrenFilter)
            );
    }
}
