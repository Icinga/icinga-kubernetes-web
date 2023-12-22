<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Event extends Model
{
    public function getTableName()
    {
        return 'event';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'namespace',
            'name',
            'uid',
            'resource_version',
            'reporting_controller',
            'reporting_instance',
            'action',
            'reason',
            'note',
            'type',
            'reference_kind',
            'reference_namespace',
            'reference_name',
            'first_seen',
            'last_seen',
            'count',
            'created'
        ];
    }

    public function getDefaultSort()
    {
        return ['last_seen desc'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'first_seen',
            'last_seen',
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
    }
}
