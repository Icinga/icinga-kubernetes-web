<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class PodVolume extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'pod_id'
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
        return ['pod_id', 'volume_name'];
    }

    public function getTableName()
    {
        return 'pod_volume';
    }
}
