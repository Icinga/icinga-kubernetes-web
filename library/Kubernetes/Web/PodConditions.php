<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\PodCondition;

class PodConditions extends Conditions
{
    protected const SORT_ORDER = [
        "PodScheduled",
        "Initialized",
        "DisruptionTarget",
        "Completed", // Custom condition
        "PodReadyToStartContainers",
        "ContainersReady",
        "Ready"
    ];

    protected $pod;

    public function __construct($pod)
    {
        $this->pod = $pod;
    }

    protected function getConditions()
    {
        return $this->processConditions();
    }

    private function processConditions()
    {
        $conditions = iterator_to_array($this->pod->condition);

        if ($this->pod->phase === "succeeded") {
            $conditions[] = $this->createCompletedCondition();
        }

        usort($conditions, function ($a, $b) {
            return array_search($a->type, static::SORT_ORDER) <=> array_search($b->type, static::SORT_ORDER);
        });

        foreach ($conditions as $i => $condition) {
            if ($condition->status === "True" && ($condition->type === "Completed" || $condition->type === "DisruptionTarget")) {
                // TODO(el): Only set last_transistion if condition->type is "Completed".
                $condition->last_transition = $conditions[$i-1]->last_transition;

                return array_reverse(array_slice($conditions, 0, $i+1));
            }
        }

        return array_reverse($conditions);
    }

    private function createCompletedCondition(): PodCondition
    {
        $completed = new PodCondition();
        $completed->type = "Completed";
        $completed->reason = "All containers have been terminated successfully and will not be restarted.";
        $completed->message = "";
        $completed->status = "true";

        return $completed;
    }

    protected function getVisual($status, $type): array
    {
		if ($status === "true" && $type === "DisruptionTarget") {
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
