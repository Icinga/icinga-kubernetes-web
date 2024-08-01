<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class StatefulSetOwner extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'stateful_set_uuid'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('stateful_set', StatefulSet::class);
    }

    public function getColumnDefinitions()
    {
        return [
            'kind'                  => $this->translate('Kind'),
            'name'                  => $this->translate('Name'),
            'uid'                   => $this->translate('UID'),
            'controller'            => $this->translate('Controller'),
            'block_owner_deletion'  => $this->translate('Block Owner Deletion'),
        ];
    }

    public function getColumns()
    {
        return [
            'stateful_set_uuid',
            'owner_uuid',
            'kind',
            'name',
            'uid',
            'controller',
            'block_owner_deletion'
        ];
    }

    public function getDefaultSort()
    {
        return ['name asc'];
    }

    public function getKeyName()
    {
        return ['stateful_set_uuid', 'owner_uuid'];
    }

    public function getTableName()
    {
        return 'stateful_set_owner';
    }
}
