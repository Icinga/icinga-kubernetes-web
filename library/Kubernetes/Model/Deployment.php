<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Deployment extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->hasMany('condition', DeploymentCondition::class);

        $relations->hasOne('owner', DeploymentOwner::class)->setJoinType('LEFT');

        $relations
            ->belongsToMany('replica_set', ReplicaSet::class)
            ->through('replica_set_owner')
            ->setTargetCandidateKey('uuid')
            ->setTargetForeignKey('owner_uuid')
            ->setCandidateKey('uuid')
            ->setForeignKey('replica_set_uuid');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('uuid')
            ->setTargetForeignKey('owner_uuid')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('deployment_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('deployment_annotation');
    }

    public function getColumnDefinitions(): array
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
            'icinga_state'              => $this->translate('Icinga State'),
            'icinga_state_reason'       => $this->translate('Icinga State Reason'),
            'yaml'                      => $this->translate('YAML'),
            'created'                   => $this->translate('Created At')
        ];
    }

    public function getColumns(): array
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
        return 'deployment';
    }
}
