<?php

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
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
        return 'id';
    }

    public function getColumns()
    {
        return [
            'id',
            'namespace',
            'name',
            'uid',
            'resource_version',
            'desired_replicas',
            'strategy',
            'min_ready_seconds',
            'progress_deadline_seconds',
            'paused',
            'actual_replicas',
            'updated_replicas',
            'ready_replicas',
            'available_replicas',
            'unavailable_replicas',
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
        $relations->hasMany('conditions', DeploymentCondition::class);
    }
}
