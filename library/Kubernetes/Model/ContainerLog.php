<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ContainerLog extends Model
{
    public function getTableName()
    {
        return 'container_log';
    }

    public function getKeyName()
    {
        return ['container_id', 'pod_id'];
    }

    public function getColumns()
    {
        return [
            'last_update',
            'logs'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'last_update' => t('Last Update'),
            'logs'        => t('Logs')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'container_id',
            'pod_id'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_update'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('container', Container::class);

        $relations->belongsTo('pod', Pod::class);
    }
}
