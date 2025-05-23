<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web\Widget\Conditions;

use Icinga\Module\Kubernetes\Model\Deployment;

class DeploymentConditions extends Conditions
{
    protected const SORT_ORDER = ['ReplicaFailure', 'Available', 'Progressing'];

    protected Deployment $deployment;

    public function __construct(Deployment $deployment)
    {
        $this->deployment = $deployment;
    }

    protected function getConditions(): array
    {
        $conditions = [];
        foreach ($this->deployment->condition as $condition) {
            $conditions[] = $condition;
        }

        usort($conditions, function ($a, $b) {
            return array_search($a->type, static::SORT_ORDER) <=> array_search($b->type, static::SORT_ORDER);
        });

        return $conditions;
    }

    protected function getVisual($status, $type): array
    {
        if ($type === 'ReplicaFailure' && $status === 'true') {
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
