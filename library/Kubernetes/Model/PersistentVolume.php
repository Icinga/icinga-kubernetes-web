<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class PersistentVolume extends Model
{
    use Translation;

    public const PHASE_AVAILABLE = 'Available';

    public const PHASE_BOUND = 'Bound';

    public const PHASE_FAILED = 'Failed';

    public const PHASE_PENDING = 'Pending';

    public const PHASE_RELEASED = 'Released';

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
            ->belongsToMany('pvc', PersistentVolumeClaim::class)
            ->through('persistent_volume_claim_ref')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('uuid')
            ->setForeignKey('persistent_volume_uuid');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('persistent_volume_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('persistent_volume_annotation');

        $relations->hasMany('favorite', Favorite::class)
            ->setForeignKey('resource_uuid')
            ->setJoinType('LEFT');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'namespace'          => $this->translate('Namespace'),
            'name'               => $this->translate('Name'),
            'uid'                => $this->translate('UID'),
            'resource_version'   => $this->translate('Resource Version'),
            'capacity'           => $this->translate('Capacity'),
            'phase'              => $this->translate('Phase'),
            'access_modes'       => $this->translate('Access Modes'),
            'volume_mode'        => $this->translate('Volume Mode'),
            'volume_source_type' => $this->translate('Volume Source Type'),
            'storage_class'      => $this->translate('Storage Class'),
            'reclaim_policy'     => $this->translate('Reclaim Policy'),
            'yaml'               => $this->translate('YAML'),
            'created'            => $this->translate('Created At')
        ];
    }

    public function getColumns(): array
    {
        return [
            'cluster_uuid',
            'namespace',
            'name',
            'uid',
            'resource_version',
            'capacity',
            'phase',
            'access_modes',
            'volume_mode',
            'volume_source_type',
            'storage_class',
            'reclaim_policy',
            'yaml',
            'created'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['persistent_volume.created desc'];
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
        return 'persistent_volume';
    }
}
