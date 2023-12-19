<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Job extends Model
{
    public function getTableName()
    {
        return 'job';
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
                'created'
            ])
        );
    }

    public function createRelations(Relations $relations)
    {
        $relations->hasMany('condition',  JobCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('job_label');

        $relations
            ->belongsToMany('pods', Pod::class)
            ->through('pod_owner');
    }
}
