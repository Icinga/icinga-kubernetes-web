<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Label;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\replicaSetCondition;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\HorizontalKeyValue;
use ipl\Web\Widget\TimeAgo;

class ReplicaSetDetail extends BaseHtmlElement
{
    protected $defaultAttributes = [
        'class' => 'replica-set-detail'
    ];

    protected $tag = 'div';

    /** @var ReplicaSet */
    protected $replicaSet;

    public function __construct($replicaSet)
    {
        $this->replicaSet = $replicaSet;
    }

    protected function assemble()
    {
        $this->addHtml(new Details([
            t('Name')                   => $this->replicaSet->name,
            t('Namespace')              => $this->replicaSet->namespace,
            t('UID')                    => $this->replicaSet->uid,
            t('Min Ready Seconds')      => $this->replicaSet->min_ready_seconds,
            t('Desired Replicas')       => $this->replicaSet->desired_replicas,
            t('Actual Replicas')        => $this->replicaSet->actual_replicas,
            t('Fully Labeled Replicas') => $this->replicaSet->fully_labeled_replicas,
            t('Ready Replicas')         => $this->replicaSet->ready_replicas,
            t('Available Replicas')     => $this->replicaSet->available_replicas,
            t('Created')                => new TimeAgo($this->replicaSet->created->getTimestamp())
        ]));

        $this->addHtml(
            new Labels($this->replicaSet->label),
            new ConditionTable($this->replicaSet, (new ReplicaSetCondition())->getColumnDefinitions())
        );

        $this->addHtml(new HtmlElement(
            'section',
            new Attributes(['class' => 'resource-pods']),
            new HtmlElement('h2', null, new Text(t('Pods'))),
            new PodList($this->replicaSet->pods->with(['node']))
        ));

        $this->addHtml(new HtmlElement(
            'section',
            new Attributes(['class' => 'resource-events']),
            new HtmlElement('h2', null, new Text(t('Events'))),
            new EventList(Event::on(Database::connection())
                ->filter(Filter::all(
                    Filter::equal('reference_kind', 'ReplicaSet'),
                    Filter::equal('reference_namespace', $this->replicaSet->namespace),
                    Filter::equal('reference_name', $this->replicaSet->name)
                )))
        ));
    }

}
