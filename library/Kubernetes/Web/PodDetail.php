<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Label;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodCondition;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\Icon;

class PodDetail extends BaseHtmlElement
{
    const QOS_ICONS = [
        'best_effort' => 'award',
        'burstable' => 'wand-magic-sparkles',
        'guaranteed' => 'helmet-safety'
    ];

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
        $details->addHtml(new HorizontalKeyValue(t('Namespace'),  HtmlElement::create(
            'span',
            new Attributes(['class' => 'namespace-badge']),
            [
                new Icon('folder-open'),
                new Text($this->pod->namespace)
            ]
        )));
        $details->addHtml(new HorizontalKeyValue(t('Node'), [
            new Icon('share-nodes'),
            $this->pod->node_name
        ]));
        $details->addHtml(new HorizontalKeyValue(t('QoS Class'), [ new Icon(self::QOS_ICONS[strtolower($this->pod->qos)]), ucfirst(Str::camel($this->pod->qos)) ]));
        $details->addHtml(new HorizontalKeyValue(t('Restart Policy'), ucfirst(Str::camel($this->pod->restart_policy))));
        $details->addHtml(new HorizontalKeyValue(t('Created'), $this->pod->created->format('Y-m-d H:i:s')));

        $this->addHtml(
            $details,
            new Labels($this->pod->label),
            new HtmlElement('h2', null, new Text(t('Conditions'))),
            new ConditionTable($this->pod, (new PodCondition())->getColumnDefinitions()),
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
