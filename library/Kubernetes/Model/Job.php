<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Job extends Model
{
    use Translation;

    public function getCompletions(): int|string
    {
        return $this->completions ?? $this->translate('Any');
    }

    public function getStartTime(): string
    {
        if ($this->start_time !== null) {
            return $this->start_time->format('Y-m-d H:i:s');
        }

        return $this->translate('Not started');
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid',
            'cluster_uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'start_time',
            'created'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsToOne('cluster', Cluster::class);

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
            ->setTargetCandidateKey('uuid')
            ->setTargetForeignKey('owner_uuid')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');

        $relations->hasOne('owner', JobOwner::class)->setJoinType('LEFT');

        $relations
            ->belongsToMany('cron_job', CronJob::class)
            ->through('job_owner')
            ->setTargetForeignKey('owner_uuid');
    }

    public function getColumnDefinitions(): array
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
            'icinga_state'               => $this->translate('Icinga State'),
            'icinga_state_reason'        => $this->translate('Icinga State Reason'),
            'yaml'                       => $this->translate('YAML'),
            'created'                    => $this->translate('Created At')
        ];
    }

    public function getColumns(): array
    {
        return [
            'cluster_uuid',
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
        return 'job';
    }
}
