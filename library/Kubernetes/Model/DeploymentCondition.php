<?php

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class DeploymentCondition extends Model
{
    public function getTableName()
    {
        return 'deployment_condition';
    }

    public function getKeyName()
    {
        return 'deployment_id';
    }

    public function getColumns()
    {
        return [
            'deployment_id',
            'type',
            'status',
            'last_update',
            'last_transition',
            'reason',
            'message',
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'deployment_id'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_update'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_transition'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('deployment', Deployment::class);
    }
}