<?php

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class DaemonSet extends Model
{
    public function getTableName()
    {
        return 'daemon_set';
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
            'min_ready_seconds',
            'current_number_scheduled',
            'number_misscheduled',
            'desired_number_scheduled',
            'number_ready',
            'collision_count',
            'created'
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
