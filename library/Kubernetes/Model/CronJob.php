<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class CronJob extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'last_schedule_time',
            'last_successful_time',
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsToMany('label', Label::class)
            ->through('cron_job_label');
    }

    public function getColumnDefinitions()
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
            'created'                       => $this->translate('Created At')
        ];
    }

    public function getColumns()
    {
        return [
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
        return 'cron_job';
    }
}
