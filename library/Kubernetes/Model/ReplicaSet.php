<?php

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ReplicaSet extends Model
{
    public function getTableName()
    {
        return 'replica_set';
    }

    public function getKeyName()
    {
        return 'uid';
    }

    public function getColumns()
    {
        return [
            'namespace',
            'name',
            'uid',
            'desired_replicas',
            'actual_replicas',
            'min_ready_seconds',
            'fully_labeled_replicas',
            'replicas',
            'ready_replicas',
            'available_replicas',
            'created',
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
    }
}