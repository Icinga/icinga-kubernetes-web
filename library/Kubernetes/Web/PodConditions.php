<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use DateTime;
use Icinga\Module\Kubernetes\Model\Pod;
use Icinga\Module\Kubernetes\Model\PodCondition;

class PodConditions extends Conditions
{
    protected const SORT_ORDER = [
        'PodScheduled',
        'Initialized',
        'DisruptionTarget',
        'Completed', // Custom condition
        'PodReadyToStartContainers',
        'ContainersReady',
        'Ready'
    ];

    protected Pod $pod;

    public function __construct(Pod $pod)
    {
        $this->pod = $pod;
    }

    protected function getConditions(): array
    {
        $conditions = iterator_to_array($this->pod->condition);

        if ($this->pod->phase === 'Succeeded') {
            $conditions[] = $this->createCompletedCondition();
        }

        usort($conditions, function ($a, $b) {
            return array_search($a->type, static::SORT_ORDER) <=> array_search($b->type, static::SORT_ORDER);
        });

        foreach ($conditions as $i => $condition) {
            if (
                $condition->status === 'true' &&
                ($condition->type === 'Completed' || $condition->type === 'DisruptionTarget')
            ) {
                if ($condition->type === 'Completed') {
                    $condition->last_transition = $conditions[$i - 1]->last_transition;
                }

                return array_reverse(array_slice($conditions, 0, $i + 1));
            }
        }

        return array_reverse($conditions);
    }

    private function createCompletedCondition(): PodCondition
    {
        $completed = new PodCondition();
        $completed->type = 'Completed';
        $completed->reason = 'All containers have been terminated successfully and will not be restarted.';
        $completed->message = '';
        $completed->status = 'true';
        $completed->last_transition = new DateTime();

        return $completed;
    }

    protected function getVisual($status, $type): array
    {
        if ($status === 'true' && $type === 'DisruptionTarget') {
            return ['error', 'times-circle'];
        } else {
            return match ($status) {
                'true'  => ['success', 'check-circle'],
                'false' => ['error', 'times-circle'],
                default => ['unknown', 'question-circle'],
            };
        }
    }
}
