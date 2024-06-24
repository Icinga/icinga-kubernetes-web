<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\PodCondition;

class PodConditions extends Conditions
{
    protected const SORT_ORDER = ["Ready", "ContainersReady", "PodReadyToStartContainers", "Initialized", "PodScheduled"];

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
        $processedConditions = [];
        $isCompletedConditionAdded = false;

        foreach ($this->pod->condition as $condition) {
            if ($condition->type === "Ready") {
                $lastTransition = $condition->last_transition;
            }

            if ($this->pod->phase === "succeeded") {
                if (! $isCompletedConditionAdded && isset($lastTransition)) {
                    $completedCondition = $this->createCompletedCondition($lastTransition);
                    $processedConditions[] = $completedCondition;
                    $isCompletedConditionAdded = true;
                }
                if (in_array($condition->type, ["PodScheduled", "Initialized"])) {
                    $processedConditions[] = $condition;
                }
            } else {
                $processedConditions[] = $condition;
            }
        }

        usort($processedConditions, function ($a, $b) {
            return array_search($a->type, static::SORT_ORDER) <=> array_search($b->type, static::SORT_ORDER);
        });

        return $processedConditions;
    }

    private function createCompletedCondition($lastTransition): PodCondition
    {
        $completed = new PodCondition();
        $completed->type = "Completed";
        $completed->reason = "All containers have been terminated successfully and will not be restarted.";
        $completed->message = "";
        $completed->status = "True";
        $completed->last_transition = $lastTransition;

        return $completed;
    }

    protected function getVisual($status, $type): array
    {
        switch ($status) {
            case "True":
                return ['success', 'check-circle'];
            case "False":
                return ['error', 'times-circle'];
            default:
                return ['unknown', 'question-circle'];
        }
    }
}
