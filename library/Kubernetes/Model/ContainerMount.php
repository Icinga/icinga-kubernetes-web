<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ContainerMount extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'container_uuid',
            'pod_uuid'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('container', Container::class);

        $relations->belongsTo('pod', Pod::class);
    }

    public function getColumnDefinitions()
    {
        return [
            'volume_name' => $this->translate('Volume Name'),
            'path'        => $this->translate('Mount Path'),
            'sub_path'    => $this->translate('Sub Path'),
            'read_only'   => $this->translate('Read Only')
        ];
    }

    public function getColumns()
    {
        return [
            'pod_uuid',
            'path',
            'sub_path',
            'read_only'
        ];
    }

    public function getDefaultSort()
    {
        return ['volume_name desc'];
    }

    public function getKeyName()
    {
        return ['container_uuid', 'volume_name'];
    }

    public function getTableName()
    {
        return 'container_mount';
    }
}
