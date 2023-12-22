<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class IngressBackendService extends Model
{
    public function getTableName()
    {
        return 'ingress_backend_service';
    }

    public function getKeyName()
    {
        return ['ingress_id', 'service_id'];
    }

    public function getColumns()
    {
        return [
            'ingress_rule_id',
            'service_name',
            'service_port_name',
            'service_port_number',
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'service_name'        => t('Service Name'),
            'service_port_name'   => t('Service Port Name'),
            'service_port_number' => t('Service Port Number')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'ingress_id',
            'service_id',
            'ingress_rule_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('ingress', Ingress::class);

        $relations->belongsTo('ingress_rule', IngressRule::class);
    }
}
