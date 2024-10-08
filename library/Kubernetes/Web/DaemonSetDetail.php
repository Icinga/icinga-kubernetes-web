<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Format;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\DaemonSet;
use Icinga\Module\Kubernetes\Model\DaemonSetCondition;
use Icinga\Module\Kubernetes\Model\Event;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class DaemonSetDetail extends BaseHtmlElement
{
    use Translation;

    protected DaemonSet $daemonSet;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'daemon-set-detail'];

    public function __construct(DaemonSet $daemonSet)
    {
        $this->daemonSet = $daemonSet;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->daemonSet, [
                $this->translate('Min Ready Duration')       => (new HtmlDocument())->addHtml(
                    new Icon('stopwatch'),
                    new Text(Format::seconds($this->daemonSet->min_ready_seconds, $this->translate('None')))
                ),
                $this->translate('Update Strategy')          => (new HtmlDocument())->addHtml(
                    new Icon('retweet'),
                    new Text($this->daemonSet->update_strategy)
                ),
                $this->translate('Desired Number Scheduled') => $this->daemonSet->desired_number_scheduled,
                $this->translate('Current Number Scheduled') => $this->daemonSet->current_number_scheduled,
                $this->translate('Update Number Scheduled')  => $this->daemonSet->update_number_scheduled,
                $this->translate('Number Misscheduled')      => $this->daemonSet->number_misscheduled,
                $this->translate('Number Ready')             => $this->daemonSet->number_ready,
                $this->translate('Number Available')         => $this->daemonSet->number_available,
                $this->translate('Number Unavailable')       => $this->daemonSet->number_unavailable,
                $this->translate('Icinga State')             => (new HtmlDocument())->addHtml(
                    new StateBall($this->daemonSet->icinga_state, StateBall::SIZE_MEDIUM),
                    new HtmlElement(
                        'span',
                        new Attributes(['class' => 'icinga-state-text']),
                        new Text($this->daemonSet->icinga_state)
                    )
                ),
                $this->translate('Icinga State Reason')      => new IcingaStateReason(
                    $this->daemonSet->icinga_state_reason
                )
            ])),
            new Labels($this->daemonSet->label),
            new Annotations($this->daemonSet->annotation),
            new ConditionTable($this->daemonSet, (new DaemonSetCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                new PodList($this->daemonSet->pod->with(['node']))
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                new EventList(
                    Event::on(Database::connection())
                        ->filter(Filter::equal('referent_uuid', $this->daemonSet->uuid))
                )
            ),
            new Yaml($this->daemonSet->yaml)
        );
    }
}
