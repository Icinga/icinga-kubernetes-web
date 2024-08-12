<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Format;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Model\StatefulSetCondition;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class StatefulSetDetail extends BaseHtmlElement
{
    use Translation;

    protected StatefulSet $statefulSet;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'stateful-set-detail'];

    public function __construct(StatefulSet $statefulSet)
    {
        $this->statefulSet = $statefulSet;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->statefulSet, [
                $this->translate('Min Ready Duration')    => (new HtmlDocument())->addHtml(
                    new Icon('stopwatch'),
                    new Text(Format::seconds($this->statefulSet->min_ready_seconds, $this->translate('None')))
                ),
                $this->translate('Update Strategy')       => (new HtmlDocument())->addHtml(
                    new Icon('retweet'),
                    new Text($this->statefulSet->update_strategy)
                ),
                $this->translate('Pod Management Policy') => (new HtmlDocument())->addHtml(
                    new Icon('shuffle'),
                    new Text($this->statefulSet->pod_management_policy)
                ),
                $this->translate('Service Name')          => (new HtmlDocument())->addHtml(
                    new HtmlElement('i', new Attributes(['class' => 'icon kicon-service'])),
                    new Text($this->statefulSet->service_name)
                ),
                $this->translate('Desired Replicas')      => $this->statefulSet->desired_replicas,
                $this->translate('Actual Replicas')       => $this->statefulSet->actual_replicas,
                $this->translate('Current Replicas')      => $this->statefulSet->current_replicas,
                $this->translate('Updated Replicas')      => $this->statefulSet->updated_replicas,
                $this->translate('Ready Replicas')        => $this->statefulSet->ready_replicas,
                $this->translate('Available Replicas')    => $this->statefulSet->available_replicas,
                $this->translate('Icinga State')          => (new HtmlDocument())->addHtml(
                    new StateBall($this->statefulSet->icinga_state, StateBall::SIZE_MEDIUM),
                    new HtmlElement(
                        'span',
                        new Attributes(['class' => 'icinga-state-text']),
                        new Text(' ' . $this->statefulSet->icinga_state))
                ),
                $this->translate('Icinga State Reason')   => new IcingaStateReason(
                    $this->statefulSet->icinga_state_reason
                )
            ])),
            new Labels($this->statefulSet->label),
            new Annotations($this->statefulSet->annotation),
            new ConditionTable($this->statefulSet, (new StatefulSetCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                new PodList($this->statefulSet->pod->with(['node']))
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                new EventList(
                    Event::on(Database::connection())
                        ->filter(Filter::equal('referent_uuid', $this->statefulSet->uuid))
                )
            ),
            new Yaml($this->statefulSet->yaml)
        );
    }
}
