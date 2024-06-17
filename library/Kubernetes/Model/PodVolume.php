<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class PodVolume extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'pod_uuid'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('pod', Pod::class);
    }

    public function getColumnDefinitions()
    {
        return [
            'volume_name' => $this->translate('Volume Name'),
            'type'        => $this->translate('Type'),
            'source'      => $this->translate('Source')
        ];
    }

    public function getColumns()
    {
        return [
            'type',
            'source'
        ];
    }

    public function getKeyName()
    {
        return ['pod_uuid', 'volume_name'];
    }

    public function getTableName()
    {
        return 'pod_volume';
    }
}
