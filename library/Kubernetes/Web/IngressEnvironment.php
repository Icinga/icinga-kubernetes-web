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

class IngressEnvironment implements ValidHtml
{
    private Ingress $ingress;

    public function __construct($ingress)
    {
        $this->ingress = $ingress;
    }

    public function render(): ValidHtml
    {
        $services = Service::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->ingress->namespace));

        $filters = [];
        foreach ($this->ingress->backend_service as $backendService) {
            $filters[] = Filter::all(
                Filter::equal('service.name', $backendService->service_name),
                Filter::equal('service.port.port', $backendService->service_port_number)
            );
        }

        $services->filter(Filter::any(...$filters))
            ->limit(3);

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    Attributes::create(['class' => 'environment-widget-title']),
                    Text::create(t('Environment'))
                ),
                new Environment($this->ingress, null, $services, null, Filter::any(...$filters))
            );
    }
}
