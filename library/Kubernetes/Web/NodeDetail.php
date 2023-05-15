<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Node;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Stdlib\Filter;

class NodeDetail extends BaseHtmlElement
{
    /** @var Node */
    protected $node;

    protected $defaultAttributes = [
        'class' => 'node-detail',
    ];

    protected $tag = 'div';

    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    protected function assemble()
    {
        $this->addHtml(new Details([
            t('Name')                 => $this->node->name,
            t('Namespace')            => $this->node->namespace,
            t('UID')                  => $this->node->uid,
            t('Resource Version')     => $this->node->resource_version,
            t('Pod CIDR')             => $this->node->pod_cidr,
            t('Number of IPs')        => $this->node->num_ips,
            t('Unschedulable')        => $this->node->unschedulable,
            t('Ready')                => $this->node->ready,
            t('CPU Capacity')         => $this->node->cpu_capacity,
            t('CPU Allocatable')      => $this->node->cpu_allocatable,
            t('Memory Capacity')      => $this->node->memory_capacity,
            t('Memory Allocatable')   => $this->node->memory_allocatable,
            t('Pod Capacity')         => $this->node->pod_capacity,
            t('Created')              => $this->node->created->format('Y-m-d H:i:s')
        ]));


        $details = new HtmlElement('div');
        $this->addHtml(
            $details,
            new EventList(Event::on(Database::connection())
                ->filter(Filter::all(
                    Filter::equal('reference_kind', 'Node'),
                    Filter::equal('reference_namespace', $this->node->namespace),
                    Filter::equal('reference_name', $this->node->name)
                )))
        );
    }
}
