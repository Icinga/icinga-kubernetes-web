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
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class StatefulSetEnvironment implements ValidHtml
{
    use Translation;

    public function __construct(protected StatefulSet $statefulSet)
    {
    }

    public function render(): ValidHtml
    {
        $childrenFilter = Filter::all(
            Filter::equal('namespace', $this->statefulSet->namespace),
            Filter::equal('pod.owner.owner_uuid', (string) Uuid::fromBytes($this->statefulSet->uuid))
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
                new Environment($this->statefulSet, null, $pods, null, $childrenFilter)
            );
    }
}
