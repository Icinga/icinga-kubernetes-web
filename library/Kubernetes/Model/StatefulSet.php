<?php

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class StatefulSet extends Model
{
    public function getTableName()
    {
        return 'stateful_set';
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
            'replicas',
            'service_name',
            'ready_replicas',
            'current_replicas',
            'updated_replicas',
            'available_replicas',
            'current_revision',
            'update_revision',
            'collision_count',
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
