<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class PersistentVolumeClaim extends Model
{
    use Translation;

    public const PHASE_BOUND = 'Bound';

    public const PHASE_LOST = 'Failed';

    public const PHASE_PENDING = 'Pending';

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

        $relations->hasMany('condition', PersistentVolumeClaimCondition::class);

        $relations
            ->belongsToMany('persistent_volume', PersistentVolume::class)
            ->through('persistent_volume_claim_ref')
            ->setTargetCandidateKey('uuid')
            ->setTargetForeignKey('persistent_volume_uuid')
            ->setCandidateKey('name')
            ->setForeignKey('name');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('pvc_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('pvc_annotation');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through(PodPvc::class)
            ->setTargetCandidateKey('uuid')
            ->setTargetForeignKey('pod_uuid')
            ->setCandidateKey('name')
            ->setForeignKey('claim_name');

        $relations->hasMany('favorite', Favorite::class)
            ->setForeignKey('resource_uuid')
            ->setJoinType('LEFT');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'namespace'            => $this->translate('Namespace'),
            'name'                 => $this->translate('Name'),
            'uid'                  => $this->translate('UID'),
            'resource_version'     => $this->translate('Resource Version'),
            'desired_access_modes' => $this->translate('Desired Access Modes'),
            'actual_access_modes'  => $this->translate('Actual Access Modes'),
            'minimum_capacity'     => $this->translate('Minimum Capacity'),
            'actual_capacity'      => $this->translate('Actual Capacity'),
            'phase'                => $this->translate('Phase'),
            'volume_name'          => $this->translate('Volume Name'),
            'volume_mode'          => $this->translate('Volume Mode'),
            'storage_class'        => $this->translate('Storage Class'),
            'yaml'                 => $this->translate('YAML'),
            'created'              => $this->translate('Created At')
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
            'desired_access_modes',
            'actual_access_modes',
            'minimum_capacity',
            'actual_capacity',
            'phase',
            'volume_name',
            'volume_mode',
            'storage_class',
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
        return 'pvc';
    }
}
