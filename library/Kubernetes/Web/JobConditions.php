<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

class JobConditions extends Conditions
{
    protected $job;

    public function __construct($job)
    {
        $this->job = $job;
    }

    protected function getConditions()
    {
        return $this->job->condition;
    }

	protected function getVisual($status, $type): array
	{
		if ($type === "Suspended") {
			return ['pending', 'circle'];
		} elseif ($type === "Complete" || $type === "SuccessCriteriaMet") {
			return ['success', 'check-circle'];
		} elseif ($type === "Failed" || $type === "FailureTarget") {
			return ['error', 'times-circle'];
		} else {
			return ['inactive', 'question-circle'];
		}
	}
}
