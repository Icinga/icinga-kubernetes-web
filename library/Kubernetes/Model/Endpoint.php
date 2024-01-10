<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Endpoint extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('endpoint_slice', EndpointSlice::class);
    }

    public function getColumnDefinitions()
    {
        return [
            'host_name'    => $this->translate('Host Name'),
            'node_name'    => $this->translate('Node Name'),
            'ready'        => $this->translate('Ready'),
            'serving'      => $this->translate('Serving'),
            'terminating'  => $this->translate('Terminating'),
            'address'      => $this->translate('Address'),
            'protocol'     => $this->translate('Protocol'),
            'port'         => $this->translate('Port'),
            'port_name'    => $this->translate('Port Name'),
            'app_protocol' => $this->translate('App Protocol')
        ];
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

    public function getKeyName()
    {
        return 'id';
    }

    public function getTableName()
    {
        return 'endpoint';
    }
}
