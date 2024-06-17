<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Data extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsToMany('config_map', ConfigMap::class)
            ->through('config_map_data');

        $relations
            ->belongsToMany('secret', Secret::class)
            ->through('secret_data');
    }

    public function getColumnDefinitions()
    {
        return [
            'name'  => $this->translate('Name'),
            'value' => $this->translate('Value')
        ];
    }

    public function getColumns()
    {
        return [
            'name',
            'value'
        ];
    }

    public function getDefaultSort()
    {
        return ['name'];
    }

    public function getKeyName()
    {
        return ['uuid'];
    }

    public function getTableName()
    {
        return 'data';
    }
}
