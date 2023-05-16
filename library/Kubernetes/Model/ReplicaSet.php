<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
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
        return 'id';
    }

    public function getColumns()
    {
        return [
            'id',
            'namespace',
            'name',
            'uid',
            'desired_replicas',
            'min_ready_seconds',
            'resource_version',
            'actual_replicas',
            'fully_labeled_replicas',
            'ready_replicas',
            'available_replicas',
            'created'
        ];
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

        $relations->hasMany('condition', ReplicaSetCondition::class);
    }
}
