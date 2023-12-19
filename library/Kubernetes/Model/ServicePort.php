<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ServicePort extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'service_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('service', Service::class);
    }

    public function getColumnDefinitions()
    {
        return [
            'name'         => $this->translate('Name'),
            'port'         => $this->translate('Port'),
            'target_port'  => $this->translate('Target Port'),
            'protocol'     => $this->translate('Protocol'),
            'app_protocol' => $this->translate('App Protocol'),
            'node_port'    => $this->translate('Node Port')
        ];
    }

    public function getColumns()
    {
        return [
            'protocol',
            'app_protocol',
            'port',
            'target_port',
            'node_port'
        ];
    }

    public function getKeyName()
    {
        return ['service_id', 'name'];
    }

    public function getTableName()
    {
        return 'service_port';
    }
}
