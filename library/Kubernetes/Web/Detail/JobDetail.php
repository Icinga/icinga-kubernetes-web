<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Detail;

use Icinga\Module\Kubernetes\Common\Auth;
use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Format;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Common\ViewMode;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Web\ItemList\ResourceList;
use Icinga\Module\Kubernetes\Web\Widget\Annotations;
use Icinga\Module\Kubernetes\Web\Widget\Conditions\JobConditions;
use Icinga\Module\Kubernetes\Web\Widget\Details;
use Icinga\Module\Kubernetes\Web\Widget\DetailState;
use Icinga\Module\Kubernetes\Web\Widget\Environment\JobEnvironment;
use Icinga\Module\Kubernetes\Web\Widget\IcingaStateReason\IcingaStateReason;
use Icinga\Module\Kubernetes\Web\Widget\Labels;
use Icinga\Module\Kubernetes\Web\Widget\Yaml;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\Icon;

class JobDetail extends BaseHtmlElement
{
    use Translation;

    protected Job $job;

    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'object-detail job-detail'];

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    protected function assemble(): void
    {
        $this->addHtml(new HtmlElement(
            'section',
            null,
            new HtmlElement('h2', null, new Text($this->translate('Icinga State Reason'))),
            new IcingaStateReason($this->job->icinga_state_reason, $this->job->icinga_state)
        ));

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
                $this->translate('Icinga State')                => new DetailState($this->job->icinga_state)
            ])),
            new Labels($this->job->label),
            new Annotations($this->job->annotation),
            new JobConditions($this->job),
            new JobEnvironment($this->job),
        );

        if (Auth::getInstance()->hasPermission(Auth::SHOW_PODS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Pods'))),
                (new ResourceList(Auth::getInstance()->withRestrictions(
                    Auth::SHOW_PODS,
                    $this->job->pod->with(['node'])
                )))
                    ->setViewMode(ViewMode::Common)
            ));
        }

        if (Auth::getInstance()->hasPermission(Auth::SHOW_EVENTS)) {
            $this->addHtml(new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text('Events')),
                (new ResourceList(Event::on(Database::connection())
                    ->filter(Filter::equal('reference_uuid', $this->job->uuid))))
                    ->setViewMode(ViewMode::Common)
            ));
        }

        if (Auth::getInstance()->canShowYaml()) {
            $this->addHtml(new Yaml($this->job->yaml));
        }
    }
}
