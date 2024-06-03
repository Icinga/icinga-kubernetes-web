<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

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
        $relations->hasMany('condition', ReplicaSetCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('replica_set_label');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');

        $relations
            ->belongsToMany('deployment', Deployment::class)
            ->through('replica_set_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('uuid')
            ->setForeignKey('replica_set_uuid');
    }

    public function getColumnDefinitions()
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
            'created'                => $this->translate('Created At')
        ];
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
        return 'replica_set';
    }
}
