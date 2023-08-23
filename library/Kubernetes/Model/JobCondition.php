<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class JobCondition extends Model
{
    public function getTableName()
    {
        return 'job_condition';
    }

    public function getKeyName()
    {
        return ['job_id', 'type'];
    }

    public function getColumns()
    {
        return [
            'status',
            'last_probe',
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
            'last_probe'      => t('Last Probe'),
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
        $behaviors->add(
            new Binary([
                'job_id'
            ])
        );
        $behaviors->add(
            new MillisecondTimestamp([
                'last_probe',
                'last_transition'
            ])
        );
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('job', Job::class);
    }
}
