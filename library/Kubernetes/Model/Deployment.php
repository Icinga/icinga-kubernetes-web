<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Deployment extends Model
{
    use Translation;

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

    public function getColumnDefinitions()
    {
        return [
            'namespace'                 => $this->translate('Namespace'),
            'name'                      => $this->translate('Name'),
            'uid'                       => $this->translate('UID'),
            'resource_version'          => $this->translate('Resource Version'),
            'desired_replicas'          => $this->translate('Desired Replicas'),
            'strategy'                  => $this->translate('Strategy'),
            'min_ready_seconds'         => $this->translate('Min Ready Seconds'),
            'progress_deadline_seconds' => $this->translate('Progress Deadline Seconds'),
            'paused'                    => $this->translate('Paused'),
            'actual_replicas'           => $this->translate('Actual Replicas'),
            'updated_replicas'          => $this->translate('Updated Replicas'),
            'ready_replicas'            => $this->translate('Ready Replicas'),
            'available_replicas'        => $this->translate('Available Replicas'),
            'unavailable_replicas'      => $this->translate('Unavailable Replicas'),
            'created'                   => $this->translate('Created At')
        ];
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
            'created'
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getTableName()
    {
        return 'deployment';
    }
}
