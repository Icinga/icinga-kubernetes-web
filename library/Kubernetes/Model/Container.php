<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Container extends Model
{
    public const STATE_WAITING = 'waiting';

    public const STATE_RUNNING = 'running';

    public const STATE_TERMINATED = 'terminated';

    public function getTableName()
    {
        return 'container';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'pod_id',
            'name',
            'image',
            'cpu_limits',
            'cpu_requests',
            'memory_limits',
            'memory_requests',
            'state',
            'state_details',
            'ready',
            'started',
            'restart_count'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'name'            => t('Name'),
            'image'           => t('Image'),
            'cpu_limits'      => t('CPU Limits'),
            'cpu_requests'    => t('CPU Requests'),
            'memory_limits'   => t('Memory Limits'),
            'memory_requests' => t('Memory Requests'),
            'state'           => t('State'),
            'ready'           => t('Ready'),
            'started'         => t('Started At'),
            'restart_count'   => t('Restart Count')
        ];
    }

    public function getDefaultSort()
    {
        return ['name'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'pod_id'
        ]));

        $behaviors->add(new BoolCast([
            'ready',
            'started'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->hasMany('mount', ContainerMount::class);

        $relations
            ->hasOne('log', ContainerLog::class)
            ->setJoinType('LEFT');

        $relations->belongsTo('pod', Pod::class);
    }
}
