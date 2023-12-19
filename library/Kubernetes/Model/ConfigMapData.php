<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ConfigMapData extends Model
{
    public function getTableName()
    {
        return 'config_map_data';
    }

    public function getKeyName()
    {
        return ['config_map_id', 'data_id'];
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
            ->belongsTo('config_map', ConfigMap::class);
    }
}
