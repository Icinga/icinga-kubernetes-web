<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Format;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Job;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\StateBall;

class JobDetail extends BaseHtmlElement
{
    use Translation;

    protected Job $job;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'job-detail'];

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    protected function assemble(): void
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->job, [
                $this->translate('Parallelism')                 => (new HtmlDocument())->addHtml(
                    new Icon('grip-lines'),
                    new Text($this->job->parallelism)
                ),
                $this->translate('Completion Mode')             => $this->job->completion_mode,
                $this->translate('Completions')                 => (new HtmlDocument())->addHtml(
                    new Icon('check-double'),
                    new Text($this->job->getCompletions())
                ),
                $this->translate('Succeeded')                   => $this->job->succeeded,
                $this->translate('Backoff Limit')               => (new HtmlDocument())->addHtml(
                    new Icon('circle-exclamation'),
                    new Text($this->job->backoff_limit)
                ),
                $this->translate('Failed')                      => $this->job->failed,
                $this->translate('Start Time')                  => $this->job->getStartTime(),
                $this->translate('Active')                      => $this->job->active,
                $this->translate('Active Deadline Duration')    => (new HtmlDocument())->addHtml(
                    new Icon('skull-crossbones'),
                    new Text(Format::seconds($this->job->active_deadline_seconds) ?? $this->translate('None'))
                ),
                $this->translate('TTL Duration After Finished') => (new HtmlDocument())->addHtml(
                    new Icon('hourglass-start'),
                    new Text(Format::seconds($this->job->ttl_seconds_after_finished) ?? $this->translate('None'))
                ),
                $this->translate('Icinga State')                => (new HtmlDocument())->addHtml(
                    new StateBall($this->job->icinga_state, StateBall::SIZE_MEDIUM),
                    new HtmlElement(
                        'span',
                        new Attributes(['class' => 'icinga-state-text']),
                        new Text($this->job->icinga_state)
                    )
                ),
                $this->translate('Icinga State Reason')         => new IcingaStateReason(
                    $this->job->icinga_state_reason
                )
            ])),
            new Labels($this->job->label),
            new Annotations($this->job->annotation),
            new JobConditions($this->job)
        );

        if (Auth::getInstance()->hasPermission(Auth::SHOW_PODS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                new PodList(Auth::getInstance()->withRestrictions(
                    Auth::SHOW_PODS,
                    $this->job->pod->with(['node'])
                ))
            ));
        }

        if (Auth::getInstance()->hasPermission(Auth::SHOW_EVENTS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text('Events')),
                new EventList(Event::on(Database::connection())
                    ->filter(Filter::equal('reference_uuid', $this->job->uuid)))
            ));
        }

        if (Auth::getInstance()->canShowYaml()) {
            $this->addHtml(new Yaml($this->job->yaml));
        }
    }
}
