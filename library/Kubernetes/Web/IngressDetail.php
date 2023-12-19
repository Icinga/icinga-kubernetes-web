<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Ingress;
use Icinga\Module\Kubernetes\Model\IngressBackendResource;
use Icinga\Module\Kubernetes\Model\IngressBackendService;
use Icinga\Module\Kubernetes\Model\IngressRule;
use Icinga\Module\Kubernetes\Model\IngressTls;
use ipl\Html\BaseHtmlElement;
use ipl\Stdlib\Filter;

class IngressDetail extends BaseHtmlElement
{
    /** @var Ingress */
    protected $ingress;

    protected $tag = 'div';

    public function __construct(Ingress $ingress)
    {
        $this->ingress = $ingress;
    }

    protected function assemble()
    {
        $this->addHtml(new Details(new ResourceDetails($this->ingress)));

        $backendServices = IngressBackendService::on(Database::connection())
            ->filter(Filter::all(
                Filter::equal('ingress_id', $this->ingress->id)
            ));
        if ($backendServices->count()) {
            $this->addHtml(
                new IngressRuleTable(
                    $this->ingress,
                    'backend_service',
                    (new IngressRule())->getColumnDefinitions(),
                    (new IngressBackendService())->getColumnDefinitions(),
                    (new IngressTls())->getColumnDefinitions()
                )
            );
        }

        $backendResources = IngressBackendResource::on(Database::connection())
            ->filter(Filter::all(
                Filter::equal('ingress_id', $this->ingress->id)
            ));
        if ($backendResources->count()) {
            $this->addHtml(
                new IngressRuleTable(
                    $this->ingress,
                    'backend_resource',
                    (new IngressRule())->getColumnDefinitions(),
                    (new IngressBackendResource())->getColumnDefinitions(),
                    (new IngressTls())->getColumnDefinitions()
                )
            );
        }
    }
}
