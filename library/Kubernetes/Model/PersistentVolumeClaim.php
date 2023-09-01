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
    public const PHASE_PENDING = 'pending';

    public const PHASE_BOUND = 'bound';

    public const PHASE_LOST = 'failed';

    public const DEFAULT_VOLUME_MODE = 'filesystem';

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
            'namespace' => t('Namespace'),
            'name' => t('Name'),
            'phase' => t('Phase'),
            'created' => t('Created At')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
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
        $relations->hasMany('condition', PersistentVolumeClaimCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('pvc_label');

        $relations
            ->belongsTo('pod', Pod::class);

        $relations
            ->belongsToMany('persistent_volume', PersistentVolume::class)
            ->through('persistent_volume_claim_ref')
            ->setTargetCandidateKey('id')
            ->setTargetForeignKey('persistent_volume_id')
            ->setCandidateKey('name')
            ->setForeignKey('name');
    }
}
