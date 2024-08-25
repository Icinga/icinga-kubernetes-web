<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Web;

use Icinga\Module\Kubernetes\Model\ReplicaSet;

class ReplicaSetConditions extends Conditions
{
    protected ReplicaSet $replicaSet;

    public function __construct(ReplicaSet $replicaSet)
    {
        $this->replicaSet = $replicaSet;
    }

    protected function getConditions(): iterable
    {
        return $this->replicaSet->condition;
    }

    protected function getVisual($status, $type): array
    {
        if ($type === 'ReplicaFailure' && $status === 'true') {
            return ['error', 'times-circle'];
        } else {
            return ['unknown', 'question-circle'];
        }
    }
}
