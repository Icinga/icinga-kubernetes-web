<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\Environment;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Factory;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodOwner;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class PodEnvironment implements ValidHtml
{
    use Translation;

    public function __construct(protected Pod $pod)
    {
    }

    public function render(): ValidHtml
    {
        $parentsFilter = Filter::all(
            Filter::equal('namespace', $this->pod->namespace),
            Filter::equal('service.pod.uuid', (string) Uuid::fromBytes($this->pod->uuid))
        );

        $services = $this->pod->service
            ->filter($parentsFilter)
            ->limit(3);

        $podOwner = PodOwner::on(Database::connection())
            ->filter(Filter::equal('pod_uuid', (string) Uuid::fromBytes($this->pod->uuid)))->first();


        if ($podOwner !== null) {
            $podOwnerKind = Factory::fetchResource($podOwner->kind)
                ->filter(Filter::equal('uuid', (string) Uuid::fromBytes($podOwner->owner_uuid)));
        } else {
            $podOwnerKind = null;
        }

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    new Attributes(['class' => 'environment-widget-title']),
                    new Text($this->translate('Environment'))
                ),
                new Environment($this->pod, $services, $podOwnerKind, $parentsFilter, null)
            );
    }
}
