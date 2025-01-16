<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Favorite extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'resource_uuid'
        ]));
    }

    public function createRelations(Relations $relations)
    {

    }

    public function getColumnDefinitions(): array
    {
        return [
            'resource_uuid' => $this->translate('Resource UUID'),
            'username'      => $this->translate('Username'),
        ];
    }

    public function getColumns(): array
    {
        return [
            'resource_uuid',
            'username',
        ];
    }

    public function getKeyName(): array
    {
        return ['resource_uuid', 'username'];
    }

    public function getTableName(): string
    {
        return 'favorite';
    }
}
