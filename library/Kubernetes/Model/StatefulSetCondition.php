<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class StatefulSetCondition extends Model
{
    public function getTableName()
    {
        return 'stateful_set_condition';
    }

    public function getKeyName()
    {
        return ['stateful_set_id', 'type'];
    }

    public function getColumns()
    {
        return [
            'type',
            'status',
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
            'stateful_set_id'
        ]));
        $behaviors->add(new MillisecondTimestamp([
            'last_transition'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('stateful_set', StatefulSet::class);
    }
}