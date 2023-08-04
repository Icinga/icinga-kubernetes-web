<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class SecretData extends Model
{
    public function getTableName()
    {
        return 'secret_data';
    }

    public function getKeyName()
    {
        return ['secret_id', 'data_id'];
    }

    public function getColumns()
    {
        return [
            'name',
            'value'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'name'  => t('Name'),
            'value' => t('Value')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsTo('secret', Secret::class);
    }
}
