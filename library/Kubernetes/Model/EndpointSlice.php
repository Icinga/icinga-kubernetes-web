<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class EndpointSlice extends Model
{
    public function getTableName()
    {
        return 'endpoint_slice';
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
            'address_type',
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
            'address_type'     => t('Address Type'),
            'created'          => t('Created At')
        ];
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
        $relations->hasMany('endpoint', Endpoint::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('endpoint_slice_label');
    }
}
