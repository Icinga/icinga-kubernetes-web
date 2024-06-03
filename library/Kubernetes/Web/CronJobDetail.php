<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\CronJob;
use ipl\Html\BaseHtmlElement;
use ipl\I18n\Translation;

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

    protected function assemble()
    {
        $lastSuccessfulTime = '-';
        if (isset($this->cronJob->last_successful_time)) {
            $lastSuccessfulTime = $this->cronJob->last_successful_time->format('Y-m-d H:i:s');
        }
        $lastScheduleTime = '-';
        if (isset($this->cronJob->last_schedule_time)) {
            $lastScheduleTime = $this->cronJob->last_schedule_time->format('Y-m-d H:i:s');
        }
        $this->addHtml(
            new Details(new ResourceDetails($this->cronJob, [
                $this->translate('Schedule')                      => $this->cronJob->schedule,
                $this->translate('Timezone')                      => $this->cronJob->timezone,
                $this->translate('Active')                        => $this->cronJob->active,
                $this->translate('Starting Deadline Seconds')     => $this->cronJob->starting_deadline_seconds,
                $this->translate('Concurrency  Policy')           => $this->cronJob->concurrency_policy,
                $this->translate('Suspend')                       => $this->cronJob->suspend,
                $this->translate('Successful Jobs History Limit') => $this->cronJob->successful_jobs_history_limit,
                $this->translate('Failed Jobs History Limit')     => $this->cronJob->failed_jobs_history_limit,
                $this->translate('Last Schedule Time')            => $lastScheduleTime,
                $this->translate('Last Successful Time')          => $lastSuccessfulTime
            ])),
            new Labels($this->cronJob->label),
            new Annotations($this->cronJob->annotation),
            new Yaml($this->cronJob->yaml)
        );
    }
}
