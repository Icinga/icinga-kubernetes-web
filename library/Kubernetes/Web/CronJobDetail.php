<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Common\Icons;
use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Model\Event;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Stdlib\Filter;
use ipl\Web\Widget\EmptyState;

class CronJobDetail extends BaseHtmlElement
{
    use Translation;

    /** @var CronJob */
    protected $cronJob;

    protected $tag = 'div';

    public function __construct(CronJob $cronJob)
    {
        $this->cronJob = $cronJob;
    }

    protected function assemble(): void
    {
        if (isset($this->cronJob->last_schedule_time)) {
            $lastScheduleTime = $this->cronJob->last_schedule_time->format('Y-m-d H:i:s');
        } else {
            $lastScheduleTime = new EmptyState($this->translate('None'));
        }

        if (isset($this->cronJob->last_successful_time)) {
            $lastSuccessfulTime = $this->cronJob->last_successful_time->format('Y-m-d H:i:s');
        } else {
            $lastSuccessfulTime = new EmptyState($this->translate('None'));
        }

        $this->addHtml(
            new Details(new ResourceDetails($this->cronJob, [
                $this->translate('Schedule')                      => $this->cronJob->schedule,
                $this->translate('Timezone')                      => $this->cronJob->timezone ?? new EmptyState($this->translate('None')),
                $this->translate('Active')                        => $this->cronJob->active,
                $this->translate('Starting Deadline Seconds')     => $this->cronJob->starting_deadline_seconds,
                $this->translate('Concurrency  Policy')           => $this->cronJob->concurrency_policy,
                $this->translate('Suspend')                       => Icons::ready($this->cronJob->suspend),
                $this->translate('Successful Jobs History Limit') => $this->cronJob->successful_jobs_history_limit,
                $this->translate('Failed Jobs History Limit')     => $this->cronJob->failed_jobs_history_limit,
                $this->translate('Last Schedule Time')            => $lastScheduleTime,
                $this->translate('Last Successful Time')          => $lastSuccessfulTime
            ])),
            new Labels($this->cronJob->label),
            new Annotations($this->cronJob->annotation),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Jobs'))),
                new JobList($this->cronJob->job)
            ),
            new HtmlElement(
                'section',
                null,
                new HtmlElement('h2', null, new Text($this->translate('Events'))),
                new EventList(
                    Event::on(Database::connection())
                        ->filter(Filter::equal('referent_uuid', $this->cronJob->uuid))
                )
            ),
            new Yaml($this->cronJob->yaml)
        );
    }
}
