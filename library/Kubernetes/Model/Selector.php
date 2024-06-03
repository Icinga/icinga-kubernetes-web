<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Selector extends Model
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
        return ['uuid'];
    }

    public function getTableName()
    {
        return 'selector';
    }
}
