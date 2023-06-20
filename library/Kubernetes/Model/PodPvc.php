<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class PodPvc extends Model
{
    public function getTableName()
    {
        return 'pod_pvc';
    }

    public function getKeyName()
    {
        return ['pod_id', 'volume_name', 'claim_name'];
    }

    public function getColumns()
    {
        return [
            'volume_name',
            'claim_name',
            'read_only'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'claim_name' => t('Claim Name')
        ];
    }

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
}
