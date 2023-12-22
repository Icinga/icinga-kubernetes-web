<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class StatefulSet extends Model
{
    public function getTableName()
    {
        return 'stateful_set';
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
            'service_name',
            'pod_management_policy',
            'update_strategy',
            'min_ready_seconds',
            'ordinals',
            'desired_replicas',
            'actual_replicas',
            'ready_replicas',
            'current_replicas',
            'updated_replicas',
            'available_replicas',
            'created',
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'             => t('Namespace'),
            'name'                  => t('Name'),
            'uid'                   => t('UID'),
            'resource_version'      => t('Resource Version'),
            'service_name'          => t('Service Name'),
            'pod_management_policy' => t('Pod Management Policy'),
            'update_strategy'       => t('Update Strategy'),
            'min_ready_seconds'     => t('Min Ready Seconds'),
            'ordinals'              => t('Ordinals'),
            'desired_replicas'      => t('Desired Replicas'),
            'actual_replicas'       => t('Actual Replicas'),
            'ready_replicas'        => t('Ready Replicas'),
            'current_replicas'      => t('Current Replicas'),
            'updated_replicas'      => t('Updated Replicas'),
            'available_replicas'    => t('Available Replicas'),
            'created'               => t('Created At')
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
        $relations->hasMany('condition', StatefulSetCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('stateful_set_label');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');
    }
}
