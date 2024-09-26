<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ContainerMount extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'container_uuid',
            'pod_uuid'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('container', Container::class);

        $relations->belongsTo('pod', Pod::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'volume_name' => $this->translate('Volume Name'),
            'path'        => $this->translate('Mount Path'),
            'sub_path'    => $this->translate('Sub Path'),
            'read_only'   => $this->translate('Read Only')
        ];
    }

    public function getColumns(): array
    {
        return [
            'pod_uuid',
            'path',
            'sub_path',
            'read_only'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['volume_name desc'];
    }

    public function getKeyName(): array
    {
        return ['container_uuid', 'volume_name'];
    }

    public function getTableName(): string
    {
        return 'container_mount';
    }
}
