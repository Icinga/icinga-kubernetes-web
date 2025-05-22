<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class CronJob extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid',
            'cluster_uuid'
        ]));

        $behaviors->add(new BoolCast([
            'suspend'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_schedule_time',
            'last_successful_time',
            'created'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsToOne('cluster', Cluster::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('cron_job_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('cron_job_annotation');

        $relations
            ->belongsToMany('job', Job::class)
            ->through('job_owner');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'namespace'                     => $this->translate('Namespace'),
            'name'                          => $this->translate('Name'),
            'uid'                           => $this->translate('UID'),
            'resource_version'              => $this->translate('Resource Version'),
            'schedule'                      => $this->translate('Schedule'),
            'timezone'                      => $this->translate('Timezone'),
            'starting_deadline_seconds'     => $this->translate('Starting Deadline Seconds'),
            'concurrency_policy'            => $this->translate('Concurrency Policy'),
            'suspend'                       => $this->translate('Suspend'),
            'successful_jobs_history_limit' => $this->translate('Successful Jobs History Limit'),
            'failed_jobs_history_limit'     => $this->translate('Failed Jobs History Limit'),
            'active'                        => $this->translate('Active'),
            'last_schedule_time'            => $this->translate('Last Schedule Time'),
            'last_successful_time'          => $this->translate('Last Successful Time'),
            'icinga_state'                  => $this->translate('Icinga State'),
            'icinga_state_reason'           => $this->translate('Icinga State Reason'),
            'yaml'                          => $this->translate('YAML'),
            'created'                       => $this->translate('Created At')
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
            'schedule',
            'timezone',
            'starting_deadline_seconds',
            'concurrency_policy',
            'suspend',
            'successful_jobs_history_limit',
            'failed_jobs_history_limit',
            'active',
            'last_schedule_time',
            'last_successful_time',
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
        return 'cron_job';
    }
}
