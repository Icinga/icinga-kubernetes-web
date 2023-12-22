<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class PersistentVolume extends Model
{
    public const PHASE_AVAILABLE = 'available';

    public const PHASE_BOUND = 'bound';

    public const PHASE_FAILED = 'failed';

    public const PHASE_PENDING = 'pending';

    public const PHASE_RELEASED = 'released';

    public const DEFAULT_VOLUME_MODE = 'filesystem';

    public function getVolumeMode(): string
    {
        return $this->volume_mode ?? static::DEFAULT_VOLUME_MODE;
    }

    public function getTableName()
    {
        return 'persistent_volume';
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
            'capacity',
            'phase',
            'access_modes',
            'volume_mode',
            'volume_source_type',
            'storage_class',
            'reclaim_policy',
            'created'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'          => t('Namespace'),
            'name'               => t('Name'),
            'uid'                => t('UID'),
            'resource_version'   => t('Resource Version'),
            'capacity'           => t('Capacity'),
            'phase'              => t('Phase'),
            'access_modes'       => t('Access Modes'),
            'volume_mode'        => t('Volume Mode'),
            'volume_source_type' => t('Volume Source Type'),
            'storage_class'      => t('Storage Class'),
            'reclaim_policy'     => t('Reclaim Policy'),
            'created'            => t('Created At')
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
        $relations
            ->belongsToMany('pvc', PersistentVolumeClaim::class)
            ->through('persistent_volume_claim_ref')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('persistent_volume_id');
    }
}
