<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class EndpointSlice extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid',
            'cluster_uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsToOne('cluster', Cluster::class);

        $relations->hasMany('endpoint', Endpoint::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('endpoint_slice_label');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'namespace'        => $this->translate('Namespace'),
            'name'             => $this->translate('Name'),
            'uid'              => $this->translate('UID'),
            'resource_version' => $this->translate('Resource Version'),
            'address_type'     => $this->translate('Address Type'),
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
            'address_type',
            'created'
        ];
    }

    public function getKeyName(): string
    {
        return 'uuid';
    }

    public function getTableName(): string
    {
        return 'endpoint_slice';
    }
}
