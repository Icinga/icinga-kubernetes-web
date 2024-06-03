<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

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

    public const DEFAULT_VOLUME_MODE = 'filesystem';

    public const PHASE_AVAILABLE = 'available';

    public const PHASE_BOUND = 'bound';

    public const PHASE_FAILED = 'failed';

    public const PHASE_PENDING = 'pending';

    public const PHASE_RELEASED = 'released';

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'uuid'
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
            ->setCandidateKey('uuid')
            ->setForeignKey('persistent_volume_uuid');
    }

    public function getColumnDefinitions()
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
            'created'            => $this->translate('Created At')
        ];
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

    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function getKeyName()
    {
        return 'uuid';
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getTableName()
    {
        return 'persistent_volume';
    }

    public function getVolumeMode(): string
    {
        return $this->volume_mode ?? static::DEFAULT_VOLUME_MODE;
    }
}
