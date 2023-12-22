<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ReplicaSet extends Model
{
    public function getTableName()
    {
        return 'replica_set';
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
            'min_ready_seconds',
            'desired_replicas',
            'actual_replicas',
            'fully_labeled_replicas',
            'ready_replicas',
            'available_replicas',
            'created'
        ];
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

    public function getColumnDefinitions()
    {
        return [
            'namespace'              => t('Namespace'),
            'name'                   => t('Name'),
            'uid'                    => t('UID'),
            'resource_version'       => t('Resource Version'),
            'min_ready_seconds'      => t('Min Ready Seconds'),
            'desired_replicas'       => t('Desired Replicas'),
            'actual_replicas'        => t('Actual Replicas'),
            'fully_labeled_replicas' => t('Fully Labeled Replicas'),
            'ready_replicas'         => t('Ready Replicas'),
            'available_replicas'     => t('Available Replicas'),
            'created'                => t('Created At')
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

    public function createRelations(Relations $relations)
    {
        $relations->hasMany('condition', ReplicaSetCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('replica_set_label');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');

        $relations
            ->belongsToMany('deployment', Deployment::class)
            ->through('replica_set_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('replica_set_id');
    }
}
