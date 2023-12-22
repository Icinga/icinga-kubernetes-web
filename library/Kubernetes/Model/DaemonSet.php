<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class DaemonSet extends Model
{
    public function getTableName()
    {
        return 'daemon_set';
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
            'update_strategy',
            'min_ready_seconds',
            'desired_number_scheduled',
            'current_number_scheduled',
            'number_misscheduled',
            'number_ready',
            'update_number_scheduled',
            'number_available',
            'number_unavailable',
            'created'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'                => t('Namespace'),
            'name'                     => t('Name'),
            'uid'                      => t('UID'),
            'resource_version'         => t('Resource Version'),
            'update_strategy'          => t('Update Strategy'),
            'min_ready_seconds'        => t('Min Ready Seconds'),
            'desired_number_scheduled' => t('Desired Number Scheduled'),
            'current_number_scheduled' => t('Current Number Scheduled'),
            'number_misscheduled'      => t('Number Misscheduled'),
            'number_ready'             => t('Number Ready'),
            'update_number_scheduled'  => t('Update Number Scheduled'),
            'number_available'         => t('Number Available'),
            'number_unavailable'       => t('Number Unavailable'),
            'created'                  => t('Created At')
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
        $relations->hasMany('condition', DaemonSetCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('daemon_set_label');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');
    }
}
