<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Icingadb\Model\Behavior\BoolCast;
use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

class Instance extends Model
{
    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new MillisecondTimestamp([
            'kubernetes_heartbeat',
            'heartbeat'
        ]));

        $behaviors->add(new BoolCast([
            'kubernetes_api_reachable'
        ]));

        $behaviors->add(new Uuid([
            'uuid'
        ]));
    }

    public function getColumns(): array
    {
        return [
            'version',
            'kubernetes_version',
            'kubernetes_heartbeat',
            'kubernetes_api_reachable',
            'message',
            'heartbeat'
        ];
    }

    public function getKeyName(): string
    {
        return 'uuid';
    }

    public function getTableName(): string
    {
        return 'kubernetes_instance';
    }
}
