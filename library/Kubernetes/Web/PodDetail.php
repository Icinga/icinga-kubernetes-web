<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Label;
use Icinga\Module\Kubernetes\Model\Pod;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;

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
        $details = new HtmlElement('div');
        $details->addHtml(new HtmlElement('h2', null, new Text('Details')));
        $details->addHtml(new HorizontalKeyValue(t('Name'), $this->pod->name));
        $details->addHtml(new HorizontalKeyValue(t('IP'), $this->pod->ip));
        $details->addHtml(new HorizontalKeyValue(t('Namespace'), $this->pod->namespace));
        $details->addHtml(new HorizontalKeyValue(t('Node'), $this->pod->node_name));
        $details->addHtml(new HorizontalKeyValue(t('QoS Class'), ucfirst(Str::camel($this->pod->qos))));
        $details->addHtml(new HorizontalKeyValue(t('Restart Policy'), ucfirst(Str::camel($this->pod->restart_policy))));
        $details->addHtml(new HorizontalKeyValue(t('Created'), $this->pod->created->format('Y-m-d H:i:s')));
        $labels = new HtmlElement('div');
        /** @var Label $label */
        foreach ($this->pod->label as $label) {
            $labels->addHtml(new HorizontalKeyValue($label->name, $label->value));
        }
        $this->addHtml(
            $details,
            new HtmlElement('h2', null, new Text('Conditions')),
            new PodConditionTable($this->pod),
            new HtmlElement('h2', null, new Text('Labels')),
            $labels,
            new HtmlElement('h2', null, new Text('Containers')),
            new ContainerList($this->pod->container),
            new HtmlElement('h2', null, new Text('Events')),
            new EventList(Event::on(Database::connection())
                ->filter(Filter::all(
                    Filter::equal('reference_kind', 'Pod'),
                    Filter::equal('reference_namespace', $this->pod->namespace),
                    Filter::equal('reference_name', $this->pod->name)
                )))
        );
    }
}
