<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\Environment;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\Service;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class ServiceEnvironment implements ValidHtml
{
    use Translation;

    public function __construct(protected Service $service)
    {
    }

    public function render(): ValidHtml
    {
        $ingresses = Ingress::on(Database::connection());

        $parentsFilter = Filter::all();
        $parentsFilter->add(
            Filter::all(
                Filter::equal('namespace', $this->service->namespace),
                Filter::equal('ingress.backend_service.service_name', $this->service->name)
            )
        );

        foreach ($this->service->port as $servicePort) {
            $parentsFilter->add(Filter::equal('ingress.backend_service.service_port_number', $servicePort->port));
        }

        $ingresses = $ingresses->setFilter($parentsFilter)
            ->limit(3);

        $pods = $this->service->pod
            ->filter(Filter::equal('namespace', $this->service->namespace))
            ->limit(3);

        $childrenFilter = Filter::all(
            Filter::equal('namespace', $this->service->namespace),
            Filter::equal('pod.service.uuid', (string) Uuid::fromBytes($this->service->uuid))
        );

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    new Attributes(['class' => 'environment-widget-title']),
                    new Text($this->translate('Environment'))
                ),
                new Environment($this->service, $ingresses, $pods, $parentsFilter, $childrenFilter)
            );
    }
}
