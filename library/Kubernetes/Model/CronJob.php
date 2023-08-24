<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class CronJob extends Model
{
    public function getTableName()
    {
        return 'cron_job';
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

    public function getColumnDefinitions()
    {
        return [
            'namespace' => t('Namespace'),
            'name'      => t('Name'),
            'created'   => t('Created At')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return ['namespace', 'created desc'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(
            new Binary([
                'id'
            ])
        );
        $behaviors->add(
            new MillisecondTimestamp([
                'last_schedule_time',
                'last_successful_time',
                'created'
            ])
        );
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsToMany('label', Label::class)
            ->through('cron_job_label');
    }
}
