<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Secret extends Model
{
    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsToMany('data', Data::class)
            ->through('secret_data');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('secret_label');
    }

    public function getColumns()
    {
        return [
            'namespace',
            'name',
            'uid',
            'resource_version',
            'type',
            'immutable',
            'created'
        ];
    }

    public function getKeyName()
    {
        return ['uuid'];
    }

    public function getTableName()
    {
        return 'secret';
    }
}
