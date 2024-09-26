<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ContainerLog extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'container_uuid',
            'pod_uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_update'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('sidecar_container', SidecarContainer::class);

        $relations->belongsTo('container', Container::class);

        $relations->belongsTo('pod', Pod::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'last_update' => $this->translate('Last Update'),
            'logs'        => $this->translate('Logs')
        ];
    }

    public function getColumns(): array
    {
        return [
            'last_update',
            'logs'
        ];
    }

    public function getKeyName(): array
    {
        return ['container_uuid', 'pod_uuid'];
    }

    public function getTableName(): string
    {
        return 'container_log';
    }
}
