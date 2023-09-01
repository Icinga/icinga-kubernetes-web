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
            'created',
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
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
        $relations
            ->belongsToMany('pods', Pod::class)
            ->through('pod_owner');

        $relations
            ->belongsToMany('replica_sets', ReplicaSet::class)
            ->through('replica_set_owner');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('deployment_label');

        $relations->hasMany('condition', DeploymentCondition::class);
    }
}
