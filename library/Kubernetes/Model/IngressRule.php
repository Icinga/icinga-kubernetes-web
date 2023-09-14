<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class IngressRule extends Model
{
    public function getTableName()
    {
        return 'ingress_rule';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'ingress_id',
            'host',
            'path',
            'path_type',
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'host'         => t('Host'),
            'path'         => t('Path'),
            'path_type'    => t('Path Type'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(
            new Binary([
                'id',
                'ingress_id'
            ])
        );
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsTo('ingress', Ingress::class);
        $relations
            ->hasMany('backend_service', IngressBackendService::class);
        $relations
            ->hasMany('backend_resource', IngressBackendResource::class);
    }
}
