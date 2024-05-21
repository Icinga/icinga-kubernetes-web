<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\DaemonSetCondition;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Str;

class DaemonSetDetail extends BaseHtmlElement
{
    use Translation;

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
                $this->translate('Update Strategy')          => ucfirst(Str::camel($this->daemonSet->update_strategy)),
                $this->translate('Min Ready Seconds')        => $this->daemonSet->min_ready_seconds,
                $this->translate('Desired Number Scheduled') => $this->daemonSet->desired_number_scheduled,
                $this->translate('Current Number Scheduled') => $this->daemonSet->current_number_scheduled,
                $this->translate('Update Number Scheduled')  => $this->daemonSet->update_number_scheduled,
                $this->translate('Number Misscheduled')      => $this->daemonSet->number_misscheduled,
                $this->translate('Number Ready')             => $this->daemonSet->number_ready,
                $this->translate('Number Available')         => $this->daemonSet->number_available,
                $this->translate('Number Unavailable')       => $this->daemonSet->number_unavailable
            ])),
            new Labels($this->daemonSet->label),
            new Annotations($this->daemonSet->annotation),
            new ConditionTable($this->daemonSet, (new DaemonSetCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                new PodList($this->daemonSet->pod->with(['node']))
            )
        );
    }
}
