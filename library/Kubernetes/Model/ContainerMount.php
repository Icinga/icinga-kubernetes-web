<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ContainerMount extends Model
{
    public function getTableName()
    {
        return 'container_mount';
    }

    public function getKeyName()
    {
        return ['container_id', 'volume_name'];
    }

    public function getColumns()
    {
        return [
            'pod_id',
            'path',
            'sub_path',
            'read_only'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'volume_name' => t('Volume Name'),
            'path'        => t('Mount Path'),
            'sub_path'    => t('Sub Path'),
            'read_only'   => t('Read Only')
        ];
    }

    public function getDefaultSort()
    {
        return ['volume_name desc'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'container_id',
            'pod_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('container', Container::class);

        $relations->belongsTo('pod', Pod::class);
    }
}
