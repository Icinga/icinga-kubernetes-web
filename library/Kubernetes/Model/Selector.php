<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Selector extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsToMany('service', Service::class)
            ->through('service_selector');
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
        return ['id'];
    }

    public function getTableName()
    {
        return 'selector';
    }
}
