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
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;

class IngressEnvironment implements ValidHtml
{
    use Translation;

    public function __construct(protected Ingress $ingress)
    {
    }

    public function render(): ValidHtml
    {
        $services = Service::on(Database::connection())
            ->filter(Filter::equal('namespace', $this->ingress->namespace));

        $childrenFilter = null;
        $filters = [];
        if ($this->ingress->backend_service->first() != null) {
            foreach ($this->ingress->backend_service as $backendService) {
                $filters[] = Filter::all(
                    Filter::equal('service.name', $backendService->service_name),
                    Filter::equal('service.port.port', $backendService->service_port_number)
                );
            }

            $childrenFilter = Filter::any(...$filters);
            $services
                ->filter($childrenFilter)
                ->limit(3);
        } else {
            $services = null;
        }

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    new Attributes(['class' => 'environment-widget-title']),
                    new Text($this->translate('Environment'))
                ),
                new Environment($this->ingress, null, $services, null, $childrenFilter)
            );
    }
}
