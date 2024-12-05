<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Auth;
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
    protected Ingress $ingress;

    protected $tag = 'div';

    public function __construct(Ingress $ingress)
    {
        $this->ingress = $ingress;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->ingress)),
            new Labels($this->ingress->label),
            new Annotations($this->ingress->annotation)
        );

        $backendServices = IngressBackendService::on(Database::connection())
            ->filter(Filter::all(
                Filter::equal('ingress_uuid', $this->ingress->uuid)
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
                Filter::equal('ingress_uuid', $this->ingress->uuid)
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

        if (Auth::getInstance()->canShowYaml()) {
            $this->addHtml(new Yaml($this->ingress->yaml));
        }
    }
}
