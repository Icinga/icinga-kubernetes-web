<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Model\StatefulSetCondition;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;
use ipl\Web\Widget\StateBall;

class StatefulSetDetail extends BaseHtmlElement
{
    use Translation;

    /** @var StatefulSet */
    protected $statefulSet;

    protected $tag = 'div';

    public function __construct($statefulSet)
    {
        $this->statefulSet = $statefulSet;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->statefulSet, [
                $this->translate('Service Name')          => $this->statefulSet->service_name,
                $this->translate('Pod Management Policy') => ucfirst(Str::camel(
                    $this->statefulSet->pod_management_policy
                )),
                $this->translate('Update Strategy')       => ucfirst(Str::camel(
                    $this->statefulSet->update_strategy
                )),
                $this->translate('Min Ready Seconds')     => $this->statefulSet->min_ready_seconds,
                $this->translate('Desired Replicas')      => $this->statefulSet->desired_replicas,
                $this->translate('Actual Replicas')       => $this->statefulSet->actual_replicas,
                $this->translate('Current Replicas')      => $this->statefulSet->current_replicas,
                $this->translate('Updated Replicas')      => $this->statefulSet->updated_replicas,
                $this->translate('Ready Replicas')        => $this->statefulSet->ready_replicas,
                $this->translate('Available Replicas')    => $this->statefulSet->available_replicas,
                $this->translate('Icinga State')          => (new HtmlDocument())
                    ->addHtml(new StateBall($this->statefulSet->icinga_state, StateBall::SIZE_MEDIUM))
                    ->addHtml(new HtmlElement('span', null, Text::create(' ' . $this->statefulSet->icinga_state))),
                $this->translate('Icinga State Reason')   => new HtmlElement(
                    'div',
                    new Attributes(['class' => 'state-reason detail']),
                    Text::create($this->statefulSet->icinga_state_reason)
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
            new Yaml($this->statefulSet->yaml)
        );
    }
}
