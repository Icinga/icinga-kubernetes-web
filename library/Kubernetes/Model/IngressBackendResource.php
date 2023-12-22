<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class IngressBackendResource extends Model
{
    public function getTableName()
    {
        return 'ingress_backend_resource';
    }

    public function getKeyName()
    {
        return ['ingress_id', 'resource_id'];
    }

    public function getColumns()
    {
        return [
            'ingress_rule_id',
            'api_group',
            'kind',
            'name',
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'api_group' => t('API Group'),
            'kind'      => t('Kind'),
            'name'      => t('Name')
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'ingress_id',
            'resource_id',
            'ingress_rule_id'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('ingress', Ingress::class);

        $relations->belongsTo('ingress_rule', IngressRule::class);
    }
}
