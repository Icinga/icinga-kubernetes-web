<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ReplicaSet extends Model
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

        $relations->hasMany('condition', ReplicaSetCondition::class);

        $relations->hasOne('owner', ReplicaSetOwner::class)->setJoinType('LEFT');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('replica_set_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('replica_set_annotation');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('uuid')
            ->setTargetForeignKey('owner_uuid')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');

        $relations
            ->belongsToMany('deployment', Deployment::class)
            ->through('replica_set_owner')
            ->setTargetCandidateKey('uuid')
            ->setTargetForeignKey('owner_uuid')
            ->setCandidateKey('uuid')
            ->setForeignKey('replica_set_uuid');

        $relations->hasMany('favorite', Favorite::class)
            ->setForeignKey('resource_uuid')
            ->setJoinType('LEFT');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'namespace'              => $this->translate('Namespace'),
            'name'                   => $this->translate('Name'),
            'uid'                    => $this->translate('UID'),
            'resource_version'       => $this->translate('Resource Version'),
            'min_ready_seconds'      => $this->translate('Min Ready Seconds'),
            'desired_replicas'       => $this->translate('Desired Replicas'),
            'actual_replicas'        => $this->translate('Actual Replicas'),
            'fully_labeled_replicas' => $this->translate('Fully Labeled Replicas'),
            'ready_replicas'         => $this->translate('Ready Replicas'),
            'available_replicas'     => $this->translate('Available Replicas'),
            'icinga_state'           => $this->translate('Icinga State'),
            'icinga_state_reason'    => $this->translate('Icinga State Reason'),
            'yaml'                   => $this->translate('YAML'),
            'created'                => $this->translate('Created At')
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
            'min_ready_seconds',
            'desired_replicas',
            'actual_replicas',
            'fully_labeled_replicas',
            'ready_replicas',
            'available_replicas',
            'icinga_state',
            'icinga_state_reason',
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
        return 'replica_set';
    }
}
