<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\ResourceDetails;
use Icinga\Module\Kubernetes\Model\CronJob;
use ipl\Html\BaseHtmlElement;

class CronJobDetail extends BaseHtmlElement
{
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
                t('Schedule')                      => $this->cronJob->schedule,
                t('Timezone')                      => $this->cronJob->timezone,
                t('Active')                        => $this->cronJob->active,
                t('Starting Deadline Seconds')     => $this->cronJob->starting_deadline_seconds,
                t('Concurrency  Policy')           => $this->cronJob->concurrency_policy,
                t('Suspend')                       => $this->cronJob->suspend,
                t('Successful Jobs History Limit') => $this->cronJob->successful_jobs_history_limit,
                t('Failed Jobs History Limit')     => $this->cronJob->failed_jobs_history_limit,
                t('Last Schedule Time')            => $lastScheduleTime,
                t('Last Successful Time')          => $lastSuccessfulTime,
            ])),
            new Labels($this->cronJob->label)
        );
    }
}
