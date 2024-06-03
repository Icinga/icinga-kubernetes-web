<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class StatefulSet extends Model
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
        $relations->hasMany('condition', StatefulSetCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('stateful_set_label');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'             => $this->translate('Namespace'),
            'name'                  => $this->translate('Name'),
            'uid'                   => $this->translate('UID'),
            'resource_version'      => $this->translate('Resource Version'),
            'service_name'          => $this->translate('Service Name'),
            'pod_management_policy' => $this->translate('Pod Management Policy'),
            'update_strategy'       => $this->translate('Update Strategy'),
            'min_ready_seconds'     => $this->translate('Min Ready Seconds'),
            'ordinals'              => $this->translate('Ordinals'),
            'desired_replicas'      => $this->translate('Desired Replicas'),
            'actual_replicas'       => $this->translate('Actual Replicas'),
            'ready_replicas'        => $this->translate('Ready Replicas'),
            'current_replicas'      => $this->translate('Current Replicas'),
            'updated_replicas'      => $this->translate('Updated Replicas'),
            'available_replicas'    => $this->translate('Available Replicas'),
            'created'               => $this->translate('Created At')
        ];
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
        return 'stateful_set';
    }
}
