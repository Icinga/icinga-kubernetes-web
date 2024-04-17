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

class Job extends Model
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
        $relations->hasMany('condition', JobCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('job_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('job_annotation');

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
            'namespace'                  => $this->translate('Namespace'),
            'name'                       => $this->translate('Name'),
            'uid'                        => $this->translate('UID'),
            'resource_version'           => $this->translate('Resource Version'),
            'parallelism'                => $this->translate('Parallelism'),
            'completions'                => $this->translate('Completions'),
            'active_deadline_seconds'    => $this->translate('Active Deadline Seconds'),
            'backoff_limit'              => $this->translate('Backoff Limit'),
            'ttl_seconds_after_finished' => $this->translate('TTL Seconds After Finished'),
            'completion_mode'            => $this->translate('Completion Mode'),
            'suspend'                    => $this->translate('Suspend'),
            'start_time'                 => $this->translate('Start Time'),
            'completion_time'            => $this->translate('Completion Time'),
            'active'                     => $this->translate('Active'),
            'succeeded'                  => $this->translate('Succeeded'),
            'failed'                     => $this->translate('Failed'),
            'yaml'                       => $this->translate('YAML'),
            'created'                    => $this->translate('Created At')
        ];
    }

    public function getColumns()
    {
        return [
            'namespace',
            'name',
            'uid',
            'resource_version',
            'parallelism',
            'completions',
            'active_deadline_seconds',
            'backoff_limit',
            'ttl_seconds_after_finished',
            'completion_mode',
            'suspend',
            'start_time',
            'completion_time',
            'active',
            'succeeded',
            'failed',
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
        return 'job';
    }
}
