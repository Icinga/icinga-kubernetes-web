<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ServiceSelector extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid',
            'service_uuid',
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('service', Service::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'name'  => $this->translate('Name'),
            'value' => $this->translate('Value')
        ];
    }

    public function getColumns(): array
    {
        return [
            'service_uuid',
            'name',
            'value'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['service_selector.name'];
    }

    public function getKeyName(): array
    {
        return ['uuid'];
    }

    public function getTableName(): string
    {
        return 'service_selector';
    }
}
