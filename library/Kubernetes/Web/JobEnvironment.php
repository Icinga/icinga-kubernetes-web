<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Common\Database;
use Icinga\Module\Kubernetes\Model\CronJob;
use Icinga\Module\Kubernetes\Model\Job;
use Icinga\Module\Kubernetes\Model\JobOwner;
use Icinga\Module\Kubernetes\Model\Pod;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class JobEnvironment implements ValidHtml
{
    private Job $job;

    public function __construct($job)
    {
        $this->job = $job;
    }

    public function render(): ValidHtml
    {
        $jobOwner = JobOwner::on(Database::connection())
            ->filter(Filter::equal('job_uuid', Uuid::fromBytes($this->job->uuid)->toString()))->first();

        $parentsFilter = Filter::all();

        if ($jobOwner !== null) {
            $parentsFilter = Filter::equal('uuid', Uuid::fromBytes($jobOwner->owner_uuid)->toString());

            $cronJobs = CronJob::on(Database::connection())
                ->filter($parentsFilter)
                ->limit(3);
        } else {
            $cronJobs = null;
        }

        $childrenFilter = Filter::all(
            Filter::equal('namespace', $this->job->namespace),
            Filter::equal('pod.owner.owner_uuid', Uuid::fromBytes($this->job->uuid)->toString())
        );

        $pods = Pod::on(Database::connection())
            ->filter($childrenFilter)
            ->limit(3);

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    Attributes::create(['class' => 'environment-widget-title']),
                    Text::create(t('Environment'))
                ),
                new Environment($this->job, $cronJobs, $pods, $parentsFilter, $childrenFilter)
            );
    }
}
