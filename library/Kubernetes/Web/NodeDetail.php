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
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;

class NodeDetail extends BaseHtmlElement
{
    use Translation;

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
                $this->translate('Name')               => $this->node->name,
                $this->translate('UID')                => $this->node->uid,
                $this->translate('Resource Version')   => $this->node->resource_version,
                $this->translate('Created')            => $this->node->created->format('Y-m-d H:i:s'),
                $this->translate('Pod CIDR')           => $this->node->pod_cidr,
                $this->translate('Number of IPs')      => $this->node->num_ips,
                $this->translate('Unschedulable')      => $this->node->unschedulable,
                $this->translate('Ready')              => $this->node->ready,
                $this->translate('CPU Capacity')       => sprintf('%d cores', $this->node->cpu_capacity / 1000),
                $this->translate('CPU Allocatable')    => sprintf('%d cores', $this->node->cpu_allocatable / 1000),
                $this->translate('Memory Capacity')    => Format::bytes($this->node->memory_capacity / 1000),
                $this->translate('Memory Allocatable') => Format::bytes($this->node->memory_allocatable / 1000),
                $this->translate('Pod Capacity')       => $this->node->pod_capacity
            ]),
            new Labels($this->node->label),
            new ConditionTable($this->node, (new NodeCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                new EventList(Event::on(Database::connection())
                    ->filter(Filter::all(
                        Filter::equal('reference_kind', 'Node'),
                        Filter::equal('reference_name', $this->node->name)
                    )))
            )
        );
    }
}
