<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Endpoint extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));

        $behaviors->add(new BoolCast([
            'ready',
            'serving',
            'terminating'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('endpoint_slice', EndpointSlice::class);
    }

    public function getColumnDefinitions()
    {
        return [
            'node_name'    => $this->translate('Node Name'),
            'host_name'    => $this->translate('Host Name'),
            'port_name'    => $this->translate('Port Name'),
            'ready'        => $this->translate('Ready'),
            'serving'      => $this->translate('Serving'),
            'terminating'  => $this->translate('Terminating'),
            'address'      => $this->translate('Address'),
            'port'         => $this->translate('Port'),
            'protocol'     => $this->translate('Protocol'),
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
        return 'uuid';
    }

    public function getTableName()
    {
        return 'endpoint';
    }
}
