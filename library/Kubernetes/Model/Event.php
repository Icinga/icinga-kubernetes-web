<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Event extends Model
{
    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'uuid'
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
            'yaml',
            'created'
        ];
    }

    public function getDefaultSort()
    {
        return ['last_seen desc'];
    }

    public function getKeyName()
    {
        return 'uuid';
    }

    public function getTableName()
    {
        return 'event';
    }
}
