<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\ReplicaSet;
use Icinga\Module\Kubernetes\Model\replicaSetCondition;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;

class ReplicaSetDetail extends BaseHtmlElement
{
    use Translation;

    /** @var ReplicaSet */
    protected $replicaSet;

    protected $tag = 'div';

    public function __construct(ReplicaSet $replicaSet)
    {
        $this->replicaSet = $replicaSet;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->replicaSet, [
                $this->translate('Min Ready Seconds')      => $this->replicaSet->min_ready_seconds,
                $this->translate('Desired Replicas')       => $this->replicaSet->desired_replicas,
                $this->translate('Actual Replicas')        => $this->replicaSet->actual_replicas,
                $this->translate('Fully Labeled Replicas') => $this->replicaSet->fully_labeled_replicas,
                $this->translate('Ready Replicas')         => $this->replicaSet->ready_replicas,
                $this->translate('Available Replicas')     => $this->replicaSet->available_replicas
            ])),
            new Labels($this->replicaSet->label),
            new ConditionTable($this->replicaSet, (new ReplicaSetCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                new PodList($this->replicaSet->pod->with(['node']))
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                new EventList(Event::on(Database::connection())
                    ->filter(Filter::all(
                        Filter::equal('reference_kind', 'ReplicaSet'),
                        Filter::equal('reference_namespace', $this->replicaSet->namespace),
                        Filter::equal('reference_name', $this->replicaSet->name)
                    )))
            )
        );
    }
}
