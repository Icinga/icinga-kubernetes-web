<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class IngressBackendResource extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'ingress_uuid',
            'resource_uuid',
            'ingress_rule_uuid'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('ingress', Ingress::class);

        $relations->belongsTo('ingress_rule', IngressRule::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'api_group' => $this->translate('API Group'),
            'kind'      => $this->translate('Kind'),
            'name'      => $this->translate('Name')
        ];
    }

    public function getColumns(): array
    {
        return [
            'ingress_rule_uuid',
            'api_group',
            'kind',
            'name'
        ];
    }

    public function getKeyName(): array
    {
        return ['ingress_uuid', 'resource_uuid'];
    }

    public function getTableName(): string
    {
        return 'ingress_backend_resource';
    }
}
