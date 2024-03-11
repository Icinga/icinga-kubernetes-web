<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ConfigMap extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsToMany('data', Data::class)
            ->through('config_map_data');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('config_map_label');
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'        => $this->translate('Namespace'),
            'name'             => $this->translate('Name'),
            'uid'              => $this->translate('UID'),
            'resource_version' => $this->translate('Resource Version'),
            'immutable'        => $this->translate('Immutable'),
            'created'          => $this->translate('Created At')
        ];
    }

    public function getColumns()
    {
        return [
            'id',
            'namespace',
            'name',
            'uid',
            'resource_version',
            'immutable',
            'created'
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function getKeyName()
    {
        return ['id'];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getTableName()
    {
        return 'config_map';
    }
}
