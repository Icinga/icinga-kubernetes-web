<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

class ReplicaSetConditions extends Conditions
{
	protected $replicaSet;

	public function __construct($replicaSet)
	{
		$this->replicaSet = $replicaSet;
	}

	protected function getConditions()
	{
		return $this->replicaSet->condition;
	}

	protected function getVisual($status, $type): array
	{
		if ($type === "ReplicaFailure" && $status === "true") {
			return ['error', 'times-circle'];
		} else {
			return ['unknown', 'question-circle'];
		}
	}
}
