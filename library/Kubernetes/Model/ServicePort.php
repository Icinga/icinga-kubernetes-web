<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ServicePort extends Model
{
    public function getTableName()
    {
        return 'service_port';
    }

    public function getKeyName()
    {
        return 'service_id';
    }

    public function getColumns()
    {
        return [
            'name',
            'protocol',
            'app_protocol',
            'port',
            'target_port',
            'node_port'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'name'         => t('Name'),
            'protocol'     => t('Protocol'),
            'app_protocol' => t('App Protocol'),
            'port'         => t('Port'),
            'target_port'  => t('Target Port'),
            'node_port'    => t('Node Port')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(
            new Binary([
                'service_id'
            ])
        );
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsTo('service', Service::class);
    }
}
