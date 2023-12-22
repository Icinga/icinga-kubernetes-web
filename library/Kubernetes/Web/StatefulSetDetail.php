<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\StatefulSet;
use Icinga\Module\Kubernetes\Model\StatefulSetCondition;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;

class StatefulSetDetail extends BaseHtmlElement
{
    /** @var StatefulSet */
    private $statefulSet;

    protected $tag = 'div';

    public function __construct($statefulSet)
    {
        $this->statefulSet = $statefulSet;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->statefulSet, [
                t('Service Name')          => $this->statefulSet->service_name,
                t('Pod Management Policy') => ucfirst(Str::camel($this->statefulSet->pod_management_policy)),
                t('Update Strategy')       => ucfirst(Str::camel($this->statefulSet->update_strategy)),
                t('Min Ready Seconds')     => $this->statefulSet->min_ready_seconds,
                t('Desired Replicas')      => $this->statefulSet->desired_replicas,
                t('Actual Replicas')       => $this->statefulSet->actual_replicas,
                t('Current Replicas')      => $this->statefulSet->current_replicas,
                t('Updated Replicas')      => $this->statefulSet->updated_replicas,
                t('Ready Replicas')        => $this->statefulSet->ready_replicas,
                t('Available Replicas')    => $this->statefulSet->available_replicas,
            ])),
            new Labels($this->statefulSet->label),
            new ConditionTable($this->statefulSet, (new StatefulSetCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text(t('Pods'))),
                new PodList($this->statefulSet->pod->with(['node']))
            )
        );
    }
}
