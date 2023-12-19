<?php

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class StatefulSet extends Model
{
    public const STATE_DEGRADED = 'degraded';

    public const STATE_HEALTHY = 'healthy';

    public const STATE_UNHEALTHY = 'unhealthy';

    public function getTableName()
    {
        return 'stateful_set';
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
            'desired_replicas',
            'service_name',
            'pod_management_policy',
            'update_strategy',
            'min_ready_seconds',
            'ordinals',
            'actual_replicas',
            'ready_replicas',
            'current_replicas',
            'updated_replicas',
            'available_replicas',
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
            ->belongsToMany('label', Label::class)
            ->through('stateful_set_label');

        $relations->hasMany('condition', StatefulSetCondition::class);
    }
}
