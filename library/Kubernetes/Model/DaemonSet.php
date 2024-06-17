<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class DaemonSet extends Model
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
        $relations->hasMany('condition', DaemonSetCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('daemon_set_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('daemon_set_annotation');

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
            'namespace'                => $this->translate('Namespace'),
            'name'                     => $this->translate('Name'),
            'uid'                      => $this->translate('UID'),
            'resource_version'         => $this->translate('Resource Version'),
            'update_strategy'          => $this->translate('Update Strategy'),
            'min_ready_seconds'        => $this->translate('Min Ready Seconds'),
            'desired_number_scheduled' => $this->translate('Desired Number Scheduled'),
            'current_number_scheduled' => $this->translate('Current Number Scheduled'),
            'number_misscheduled'      => $this->translate('Number Misscheduled'),
            'number_ready'             => $this->translate('Number Ready'),
            'update_number_scheduled'  => $this->translate('Update Number Scheduled'),
            'number_available'         => $this->translate('Number Available'),
            'number_unavailable'       => $this->translate('Number Unavailable'),
            'icinga_state'             => $this->translate('Icinga State'),
            'icinga_state_reason'      => $this->translate('Icinga State Reason'),
            'yaml'                    => $this->translate('YAML'),
            'created'                  => $this->translate('Created At')
        ];
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
            'icinga_state',
            'icinga_state_reason',
            'yaml',
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
        return 'daemon_set';
    }
}
