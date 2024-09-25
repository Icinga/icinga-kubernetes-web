<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class IngressRule extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid',
            'ingress_uuid'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('ingress', Ingress::class);

        $relations->hasMany('backend_service', IngressBackendService::class);

        $relations->hasMany('backend_resource', IngressBackendResource::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'host'      => $this->translate('Host'),
            'path'      => $this->translate('Path'),
            'path_type' => $this->translate('Path Type')
        ];
    }

    public function getColumns(): array
    {
        return [
            'ingress_uuid',
            'host',
            'path',
            'path_type'
        ];
    }

    public function getKeyName(): string
    {
        return 'uuid';
    }

    public function getTableName(): string
    {
        return 'ingress_rule';
    }
}
