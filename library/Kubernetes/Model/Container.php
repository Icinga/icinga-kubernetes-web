<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Container extends Model
{
    use Translation;

    public const STATE_RUNNING = 'Running';

    public const STATE_TERMINATED = 'Terminated';

    public const STATE_WAITING = 'Waiting';

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid',
            'pod_uuid'
        ]));

        $behaviors->add(new BoolCast([
            'ready',
            'started'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->hasMany('mount', ContainerMount::class);

        $relations
            ->hasOne('log', ContainerLog::class)
            ->setJoinType('LEFT');

        $relations->belongsTo('pod', Pod::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'name'                => $this->translate('Name'),
            'image'               => $this->translate('Image'),
            'image_pull_policy'   => $this->translate('Image Pull Policy'),
            'cpu_limits'          => $this->translate('CPU Limits'),
            'cpu_requests'        => $this->translate('CPU Requests'),
            'memory_limits'       => $this->translate('Memory Limits'),
            'memory_requests'     => $this->translate('Memory Requests'),
            'state'               => $this->translate('State'),
            'ready'               => $this->translate('Ready'),
            'started'             => $this->translate('Started At'),
            'restart_count'       => $this->translate('Restart Count'),
            'icinga_state'        => $this->translate('Icinga State'),
            'icinga_state_reason' => $this->translate('Icinga State Reason')
        ];
    }

    public function getColumns(): array
    {
        return [
            'pod_uuid',
            'name',
            'image_pull_policy',
            'image',
            'cpu_limits',
            'cpu_requests',
            'memory_limits',
            'memory_requests',
            'state',
            'state_details',
            'ready',
            'started',
            'restart_count',
            'icinga_state',
            'icinga_state_reason'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['container.name'];
    }

    public function getKeyName(): string
    {
        return 'uuid';
    }

    public function getTableName(): string
    {
        return 'container';
    }
}
