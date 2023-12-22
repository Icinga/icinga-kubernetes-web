<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ConfigMap extends Model
{
    public function getTableName()
    {
        return 'config_map';
    }

    public function getKeyName()
    {
        return ['id'];
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

    public function getColumnDefinitions()
    {
        return [
            'namespace'        => t('Namespace'),
            'name'             => t('Name'),
            'uid'              => t('UID'),
            'resource_version' => t('Resource Version'),
            'immutable'        => t('Immutable'),
            'created'          => t('Created At')
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

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
}
