<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\Job;

class JobConditions extends Conditions
{
    protected Job $job;

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    protected function getConditions(): iterable
    {
        return $this->job->condition;
    }

    protected function getVisual($status, $type): array
    {
        return match ($type) {
            'Suspended'                      => ['pending', 'circle'],
            'Complete', 'SuccessCriteriaMet' => ['success', 'check-circle'],
            'Failed', 'FailureTarget'        => ['error', 'times-circle'],
            default                          => ['inactive', 'question-circle'],
        };
    }
}
