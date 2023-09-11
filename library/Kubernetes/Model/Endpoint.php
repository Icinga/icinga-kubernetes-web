<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Endpoint extends Model
{
    public function getTableName()
    {
        return 'endpoint';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'host_name',
            'node_name',
            'ready',
            'serving',
            'terminating',
            'address',
            'protocol',
            'port',
            'port_name',
            'app_protocol'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'host_name'    => t('Host Name'),
            'node_name'    => t('Node Name'),
            'ready'        => t('Ready'),
            'serving'      => t('Serving'),
            'terminating'  => t('Terminating'),
            'address'      => t('Address'),
            'protocol'     => t('Protocol'),
            'port'         => t('Port'),
            'port_name'    => t('Port Name'),
            'app_protocol' => t('App Protocol')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(
            new Binary([
                'id'
            ])
        );
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsTo('endpoint_slice', EndpointSlice::class);
    }
}
