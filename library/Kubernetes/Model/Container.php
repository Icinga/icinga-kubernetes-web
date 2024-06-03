<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Container extends Model
{
    use Translation;

    public const STATE_RUNNING = 'running';

    public const STATE_TERMINATED = 'terminated';

    public const STATE_WAITING = 'waiting';

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'uuid',
            'pod_uuid'
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

    public function getColumnDefinitions()
    {
        return [
            'name'            => $this->translate('Name'),
            'image'           => $this->translate('Image'),
            'cpu_limits'      => $this->translate('CPU Limits'),
            'cpu_requests'    => $this->translate('CPU Requests'),
            'memory_limits'   => $this->translate('Memory Limits'),
            'memory_requests' => $this->translate('Memory Requests'),
            'state'           => $this->translate('State'),
            'ready'           => $this->translate('Ready'),
            'started'         => $this->translate('Started At'),
            'restart_count'   => $this->translate('Restart Count')
        ];
    }

    public function getColumns()
    {
        return [
            'pod_uuid',
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

    public function getDefaultSort()
    {
        return ['name'];
    }

    public function getKeyName()
    {
        return 'uuid';
    }

    public function getTableName()
    {
        return 'container';
    }
}
