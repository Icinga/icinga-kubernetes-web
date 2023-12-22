<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class PersistentVolumeClaim extends Model
{
    public const PHASE_BOUND = 'bound';

    public const PHASE_LOST = 'failed';

    public const PHASE_PENDING = 'pending';

    public const DEFAULT_VOLUME_MODE = 'filesystem';

    public function getVolumeMode(): string
    {
        return $this->volume_mode ?? static::DEFAULT_VOLUME_MODE;
    }

    public function getTableName()
    {
        return 'pvc';
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
            'desired_access_modes',
            'actual_access_modes',
            'minimum_capacity',
            'actual_capacity',
            'phase',
            'volume_name',
            'volume_mode',
            'storage_class',
            'created'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'            => t('Namespace'),
            'name'                 => t('Name'),
            'uid'                  => t('UID'),
            'resource_version'     => t('Resource Version'),
            'desired_access_modes' => t('Desired Access Modes'),
            'actual_access_modes'  => t('Actual Access Modes'),
            'minimum_capacity'     => t('Minimum Capacity'),
            'actual_capacity'      => t('Actual Capacity'),
            'phase'                => t('Phase'),
            'volume_name'          => t('Volume Name'),
            'volume_mode'          => t('Volume Mode'),
            'storage_class'        => t('Storage Class'),
            'created'              => t('Created At')
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->hasMany('condition', PersistentVolumeClaimCondition::class);

        $relations
            ->belongsToMany('persistent_volume', PersistentVolume::class)
            ->through('persistent_volume_claim_ref')
            ->setTargetCandidateKey('id')
            ->setTargetForeignKey('persistent_volume_id')
            ->setCandidateKey('name')
            ->setForeignKey('name');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('pvc_label');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through(PodPvc::class)
            ->setTargetCandidateKey('id')
            ->setTargetForeignKey('pod_id')
            ->setCandidateKey('name')
            ->setForeignKey('claim_name');
    }
}
