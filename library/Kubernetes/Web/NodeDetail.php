<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Node;
use Icinga\Module\Kubernetes\Model\NodeCondition;
use Icinga\Util\Format;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;

class NodeDetail extends BaseHtmlElement
{
    /** @var Node */
    protected $node;

    protected $tag = 'div';

    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details([
                t('Name')               => $this->node->name,
                t('UID')                => $this->node->uid,
                t('Resource Version')   => $this->node->resource_version,
                t('Created')            => $this->node->created->format('Y-m-d H:i:s'),
                t('Pod CIDR')           => $this->node->pod_cidr,
                t('Number of IPs')      => $this->node->num_ips,
                t('Unschedulable')      => $this->node->unschedulable,
                t('Ready')              => $this->node->ready,
                t('CPU Capacity')       => sprintf('%d cores', $this->node->cpu_capacity / 1000),
                t('CPU Allocatable')    => sprintf('%d cores', $this->node->cpu_allocatable / 1000),
                t('Memory Capacity')    => Format::bytes($this->node->memory_capacity / 1000),
                t('Memory Allocatable') => Format::bytes($this->node->memory_allocatable / 1000),
                t('Pod Capacity')       => $this->node->pod_capacity,
            ]),
            new Labels($this->node->label),
            new ConditionTable($this->node, (new NodeCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text(t('Events'))),
                new EventList(Event::on(Database::connection())
                    ->filter(Filter::all(
                        Filter::equal('reference_kind', 'Node'),
                        Filter::equal('reference_name', $this->node->name)
                    )))
            )
        );
    }
}
