<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Ingress extends Model
{
    public function getTableName()
    {
        return 'ingress';
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
            'created'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'  => t('Namespace'),
            'name'       => t('Name'),
            'created'    => t('Created At')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'created desc';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(
            new Binary([
                'id'
            ])
        );
        $behaviors->add(
            new MillisecondTimestamp([
                'created'
            ])
        );
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->hasMany('ingress_tls', IngressTls::class);

        $relations
            ->hasMany('backend_service', IngressBackendService::class);

        $relations
            ->hasMany('backend_resource', IngressBackendResource::class);

        $relations
            ->hasMany('ingress_rule', IngressRule::class);
    }
}
