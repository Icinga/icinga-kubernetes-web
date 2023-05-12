<?php

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Deployment extends Model
{
    public function getTableName()
    {
        return 'deployment';
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
            'strategy',
            'paused',
            'replicas',
            'ready_replicas',
            'available_replicas',
            'unavailable_replicas',
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
