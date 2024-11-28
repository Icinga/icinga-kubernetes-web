<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Ingress extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->hasMany('backend_resource', IngressBackendResource::class);

        $relations->hasMany('backend_service', IngressBackendService::class);

        $relations->hasMany('ingress_rule', IngressRule::class);

        $relations->hasMany('ingress_tls', IngressTls::class);

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('ingress_annotation');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'namespace'        => $this->translate('Namespace'),
            'name'             => $this->translate('Name'),
            'uid'              => $this->translate('UID'),
            'resource_version' => $this->translate('Resource Version'),
            'yaml'             => $this->translate('YAML'),
            'created'          => $this->translate('Created At')
        ];
    }

    public function getColumns(): array
    {
        return [
            'namespace',
            'name',
            'uid',
            'resource_version',
            'yaml',
            'created'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['created desc'];
    }

    public function getKeyName(): string
    {
        return 'uuid';
    }

    public function getSearchColumns(): array
    {
        return ['name'];
    }

    public function getTableName(): string
    {
        return 'ingress';
    }
}
