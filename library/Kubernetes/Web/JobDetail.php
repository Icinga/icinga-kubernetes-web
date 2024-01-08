<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\Event;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Model\JobCondition;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Stdlib\Filter;
use ipl\Stdlib\Str;

class JobDetail extends BaseHtmlElement
{
    /** @var Job */
    protected $job;

    protected $tag = 'div';

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    protected function assemble()
    {
        $this->addHtml(
            new Details(new ResourceDetails($this->job, [
                t('Parallelism')                => $this->job->parallelism,
                t('Completions')                => $this->job->completions,
                t('Active Deadline Seconds')    => $this->job->active_deadline_seconds,
                t('Backoff Limit')              => $this->job->backoff_limit,
                t('TTL Seconds After Finished') => $this->job->ttl_seconds_after_finished,
                t('Completion Mode')            => ucfirst(Str::camel($this->job->completion_mode)),
                t('Active')                     => $this->job->active,
                t('Succeeded')                  => $this->job->succeeded,
                t('Failed')                     => $this->job->failed
            ])),
            new Labels($this->job->label),
            new ConditionTable($this->job, (new JobCondition())->getColumnDefinitions()),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text(t('Pods'))),
                new PodList($this->job->pod->with(['node']))
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text('Events')),
                new EventList(
                    Event::on(Database::connection())
                        ->filter(
                            Filter::all(
                                Filter::equal('reference_kind', 'Job'),
                                Filter::equal('reference_namespace', $this->job->namespace),
                                Filter::equal('reference_name', $this->job->name)
                            )
                        )
                )
            )
        );
    }
}
