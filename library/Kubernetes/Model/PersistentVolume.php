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
    public const PHASE_PENDING = 'pending';

    public const PHASE_AVAILABLE = 'available';

    public const PHASE_BOUND = 'bound';

    public const PHASE_RELEASED = 'released';

    public const PHASE_FAILED = 'failed';

    public const DEFAULT_VOLUME_MODE = 'filesystem';

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
            'storage_class',
            'created'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'name'    => t('Name'),
            'phase'   => t('Phase'),
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
        $behaviors->add(new Binary(['id']));
        $behaviors->add(new MillisecondTimestamp(['created']));
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
