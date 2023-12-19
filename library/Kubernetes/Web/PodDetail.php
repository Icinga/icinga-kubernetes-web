<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodCondition;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;

class PodDetail extends BaseHtmlElement
{
    /** @var Pod */
    protected $pod;

    protected $defaultAttributes = [
        'class' => 'pod-detail',
    ];

    protected $tag = 'div';

    public function __construct(Pod $pod)
    {
        $this->pod = $pod;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details([
                t('Name')           => $this->pod->name,
                t('IP')             => $this->pod->ip,
                t('Namespace')      => $this->pod->namespace,
                t('Node')           => $this->pod->node_name,
                t('QoS Class')      => ucfirst(Str::camel($this->pod->qos)),
                t('Restart Policy') => ucfirst(Str::camel($this->pod->restart_policy)),
                t('Phase')          => $this->pod->phase,
                t('Created')        => $this->pod->created->format('Y-m-d H:i:s')
            ]),
            new Labels($this->pod->label),
            new ConditionTable($this->pod, (new PodCondition())->getColumnDefinitions()),
            new HtmlElement('h2', null, new Text('Containers')),
            new ContainerList($this->pod->container),
            new HtmlElement('h2', null, new Text('Events')),
            new EventList(
                Event::on(Database::connection())
                    ->filter(
                        Filter::all(
                            Filter::equal('reference_kind', 'Pod'),
                            Filter::equal('reference_namespace', $this->pod->namespace),
                            Filter::equal('reference_name', $this->pod->name)
                        )
                    )
            )
        );
    }
}
