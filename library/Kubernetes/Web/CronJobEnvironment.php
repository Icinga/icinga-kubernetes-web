<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\CronJob;
use ipl\Html\Attributes;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Stdlib\Filter;
use Ramsey\Uuid\Uuid;

class CronJobEnvironment implements ValidHtml
{
    private CronJob $cronJob;

    public function __construct($cronJob)
    {
        $this->cronJob = $cronJob;
    }

    public function render(): ValidHtml
    {
        $childrenFilter = Filter::all(
            Filter::equal('namespace', $this->cronJob->namespace),
            Filter::equal('job.owner.owner_uuid', Uuid::fromBytes($this->cronJob->uuid)->toString()),
        );

        $jobs = $this->cronJob->job
            ->filter($childrenFilter)
            ->limit(3);

        return (new HtmlDocument())
            ->addHtml(
                new HtmlElement(
                    'h2',
                    Attributes::create(['class' => 'environment-widget-title']),
                    Text::create(t('Environment'))
                ),
                new Environment($this->cronJob, null, $jobs, null, $childrenFilter)
            );
    }
}
