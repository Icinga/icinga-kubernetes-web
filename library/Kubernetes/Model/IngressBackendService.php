<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class IngressBackendService extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'ingress_uuid',
            'service_uuid',
            'ingress_rule_uuid'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('ingress', Ingress::class);

        $relations->belongsTo('ingress_rule', IngressRule::class);
    }

    public function getColumnDefinitions()
    {
        return [
            'service_name'        => $this->translate('Service Name'),
            'service_port_name'   => $this->translate('Service Port Name'),
            'service_port_number' => $this->translate('Service Port Number')
        ];
    }

    public function getColumns()
    {
        return [
            'ingress_rule_uuid',
            'service_name',
            'service_port_name',
            'service_port_number'
        ];
    }

    public function getKeyName()
    {
        return ['ingress_uuid', 'service_uuid'];
    }

    public function getTableName()
    {
        return 'ingress_backend_service';
    }
}
