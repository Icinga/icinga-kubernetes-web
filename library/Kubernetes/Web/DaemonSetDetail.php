<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\DaemonSetCondition;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Str;

class DaemonSetDetail extends BaseHtmlElement
{
    /** @var DaemonSet */
    protected $daemonSet;

    protected $tag = 'div';

    public function __construct(DaemonSet $daemonSet)
    {
        $this->daemonSet = $daemonSet;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->daemonSet, [
                t('Update Strategy')          => ucfirst(Str::camel($this->daemonSet->update_strategy)),
                t('Min Ready Seconds')        => $this->daemonSet->min_ready_seconds,
                t('Desired Number Scheduled') => $this->daemonSet->desired_number_scheduled,
                t('Current Number Scheduled') => $this->daemonSet->current_number_scheduled,
                t('Update Number Scheduled')  => $this->daemonSet->update_number_scheduled,
                t('Number Misscheduled')      => $this->daemonSet->number_misscheduled,
                t('Number Ready')             => $this->daemonSet->number_ready,
                t('Number Available')         => $this->daemonSet->number_available,
                t('Number Unavailable')       => $this->daemonSet->number_unavailable,
            ])),
            new Labels($this->daemonSet->label),
            new ConditionTable($this->daemonSet, (new DaemonSetCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text(t('Pods'))),
                new PodList($this->daemonSet->pod->with(['node']))
            )
        );
    }
}
