<?php

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
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
        return 'id';
    }

    public function getColumns()
    {
        return [
            'id',
            'namespace',
            'name',
            'uid ',
            'resource_version',
            'update_strategy',
            'min_ready_seconds',
            'desired_number_scheduled',
            'current_number_scheduled',
            'number_misscheduled',
            'number_ready',
            'update_number_scheduled',
            'number_available',
            'number_unavailable',
            'created'

        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return ['namespace', 'created desc'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {

        $behaviors->add(new Binary([
            'id'
        ]));
        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        // TODO: Add relations
    }
}
