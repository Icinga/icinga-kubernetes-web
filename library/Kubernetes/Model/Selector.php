<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Selector extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations
            ->belongsToMany('service', Service::class)
            ->through('service_selector');
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
            'name',
            'value'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['name'];
    }

    public function getKeyName(): array
    {
        return ['uuid'];
    }

    public function getTableName(): string
    {
        return 'selector';
    }
}
