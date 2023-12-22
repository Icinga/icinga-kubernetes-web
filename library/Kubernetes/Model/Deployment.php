<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Deployment extends Model
{
    public function getTableName()
    {
        return 'deployment';
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
            'desired_replicas',
            'strategy',
            'min_ready_seconds',
            'progress_deadline_seconds',
            'paused',
            'actual_replicas',
            'updated_replicas',
            'ready_replicas',
            'available_replicas',
            'unavailable_replicas',
            'created',
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'                 => t('Namespace'),
            'name'                      => t('Name'),
            'uid'                       => t('UID'),
            'resource_version'          => t('Resource Version'),
            'desired_replicas'          => t('Desired Replicas'),
            'strategy'                  => t('Strategy'),
            'min_ready_seconds'         => t('Min Ready Seconds'),
            'progress_deadline_seconds' => t('Progress Deadline Seconds'),
            'paused'                    => t('Paused'),
            'actual_replicas'           => t('Actual Replicas'),
            'updated_replicas'          => t('Updated Replicas'),
            'ready_replicas'            => t('Ready Replicas'),
            'available_replicas'        => t('Available Replicas'),
            'unavailable_replicas'      => t('Unavailable Replicas'),
            'created'                   => t('Created At')
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
        $relations->hasMany('condition', DeploymentCondition::class);

        $relations
            ->belongsToMany('replica_set', ReplicaSet::class)
            ->through('replica_set_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('replica_set_id');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('deployment_label');
    }
}
