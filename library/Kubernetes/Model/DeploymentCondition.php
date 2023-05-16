<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

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
        return ['deployment_id', 'type'];
    }

    public function getColumns()
    {
        return [
            'status',
            'last_update',
            'last_transition',
            'message',
            'reason'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'type'            => t('Type'),
            'status'          => t('Status'),
            'last_update'     => t('Last Update'),
            'last_transition' => t('Last Transition'),
            'message'         => t('Message'),
            'reason'          => t('Reason')
        ];
    }

    public function getDefaultSort()
    {
        return ['last_transition desc'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'deployment_id'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_update',
            'last_transition'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('deployment', Deployment::class);
    }
}
