<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;
use ipl\Stdlib\Filter;

class ConfigMap extends Model
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

        $relations
            ->hasMany('label', Label::class)
            ->setForeignKey('resource_uuid');

        $relations
            ->hasMany('annotation', Annotation::class)
            ->setForeignKey('resource_uuid');
    }

    public function createVisibilityFilter(Filter\Chain $filter): void
    {
        $filter->add(Filter::unlike('deleted', '*'));
    }

    public function getColumnDefinitions(): array
    {
        return [
            'namespace'        => $this->translate('Namespace'),
            'name'             => $this->translate('Name'),
            'uid'              => $this->translate('UID'),
            'resource_version' => $this->translate('Resource Version'),
            'immutable'        => $this->translate('Immutable'),
            'created'          => $this->translate('Created At')
        ];
    }

    public function getColumns(): array
    {
        return [
            'uuid',
            'cluster_uuid',
            'namespace',
            'name',
            'uid',
            'resource_version',
            'immutable',
            'created'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['config_map.created desc'];
    }

    public function getKeyName(): array
    {
        return ['uuid'];
    }

    public function getSearchColumns(): array
    {
        return ['name'];
    }

    public function getTableName(): string
    {
        return 'config_map';
    }
}
