<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

class DeploymentConditions extends Conditions
{
	protected const SORT_ORDER = ["ReplicaFailure", "Progressing", "Available"];

	protected $deployment;

	public function __construct($deployment)
	{
		$this->deployment = $deployment;
	}

	protected function getConditions()
	{
		return $this->processConditions();
	}

	private function processConditions()
	{
		$processedConditions = [];
		foreach ($this->deployment->condition as $condition) {
			$processedConditions[] = $condition;
		}

		usort($processedConditions, function ($a, $b) {
			return array_search($a->type, static::SORT_ORDER) <=> array_search($b->type, static::SORT_ORDER);
		});

		return $processedConditions;
	}

	protected function getVisual($status, $type): array
	{
		if ($type === "ReplicaFailure" && $status === "true") {
			return ['error', 'times-circle'];
		} else {
			switch ($status) {
				case "true":
					return ['success', 'check-circle'];
				case "false":
					return ['error', 'times-circle'];
				default:
					return ['unknown', 'question-circle'];
			}
		}
	}
}
