<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ServiceCondition extends Model
{
    public function getTableName()
    {
        return 'service_condition';
    }

    public function getKeyName()
    {
        return 'service_id';
    }

    public function getColumns()
    {
        return [
            'type',
            'status',
            'observed_generation',
            'last_transition',
            'reason',
            'message'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'type'                => t('Type'),
            'status'              => t('Status'),
            'observed_generation' => t('Observed Generation'),
            'last_transition'     => t('Last Transition'),
            'reason'              => t('Reason'),
            'message'             => t('Message')
        ];
    }

    public function getDefaultSort()
    {
        return 'last_transition desc';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(
            new Binary([
                'service_id'
            ])
        );
        $behaviors->add(
            new MillisecondTimestamp([
                'last_transition'
            ])
        );
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsTo('service', Service::class);
    }
}
