<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\Service;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class ServiceEnvironment implements ValidHtml
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
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
            Filter::equal('pod.service.uuid', Uuid::fromBytes($this->service->uuid)->toString())
        );

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    Attributes::create(['class' => 'environment-widget-title']),
                    Text::create(t('Environment'))
                ),
                new Environment($this->service, $ingresses, $pods, $parentsFilter, $childrenFilter)
            );
    }
}
