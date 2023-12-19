<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
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

    protected $defaultAttributes = [
        'class' => 'ingress-detail',
    ];

    public function __construct(Ingress $ingress)
    {
        $this->ingress = $ingress;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details([
                t('Name')      => $this->ingress->name,
                t('Namespace') => $this->ingress->namespace,
                t('Created')   => $this->ingress->created->format('Y-m-d H:i:s')
            ]),
        );

        $services = IngressBackendService::on(Database::connection())
            ->filter(Filter::all(
                    Filter::equal('ingress_id', $this->ingress->id)
                ));
        if ($services->first() !== null) {
            $this->addHtml(
                new IngressRuleTable(
                    $this->ingress, 'backend_service', (new IngressRule())->getColumnDefinitions(),
                    (new IngressBackendService())->getColumnDefinitions(), (new IngressTls())->getColumnDefinitions()
                )
            );
        }

        $resources = IngressBackendResource::on(Database::connection())
            ->filter(Filter::all(
                    Filter::equal('ingress_id', $this->ingress->id)
                ));
        if ($resources->first() !== null) {
            $this->addHtml(
                new IngressRuleTable(
                    $this->ingress, 'backend_resource', (new IngressRule())->getColumnDefinitions(),
                    (new IngressBackendResource())->getColumnDefinitions(), (new IngressTls())->getColumnDefinitions()
                )
            );
        }
    }
}
