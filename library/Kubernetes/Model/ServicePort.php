<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ServicePort extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'service_uuid'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('service', Service::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'name'         => $this->translate('Name'),
            'port'         => $this->translate('Port'),
            'target_port'  => $this->translate('Target Port'),
            'protocol'     => $this->translate('Protocol'),
            'app_protocol' => $this->translate('App Protocol'),
            'node_port'    => $this->translate('Node Port')
        ];
    }

    public function getColumns(): array
    {
        return [
            'protocol',
            'app_protocol',
            'port',
            'target_port',
            'node_port'
        ];
    }

    public function getKeyName(): array
    {
        return ['service_uuid', 'name'];
    }

    public function getTableName(): string
    {
        return 'service_port';
    }
}
