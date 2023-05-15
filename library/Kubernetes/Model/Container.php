<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Container extends Model
{
    public const STATE_WAITING = 'waiting';
    public const STATE_RUNNING = 'running';

    public const STATE_TERMINATED = 'terminated';

    public function getTableName()
    {
        return 'container';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'pod_id',
            'name',
            'image',
            'cpu_limits',
            'cpu_requests',
            'memory_limits',
            'memory_requests',
            'state',
            'state_details',
            'ready',
            'started',
            'restart_count',
            'logs'
        ];
    }

//    public function getColumnDefinitions()
//    {
//        return [
//            'object_id'     => t('Object Id'),
//            'started_at'    => t('Started At'),
//            'recovered_at'  => t('Recovered At'),
//            'severity'      => t('Severity')
//        ];
//    }
//
//    public function getSearchColumns()
//    {
//        return ['severity'];
//    }
//
//    public function getDefaultSort()
//    {
//        return ['incident.started_at desc'];
//    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id',
            'pod_id'
        ]));
        $behaviors->add(new BoolCast([
            'ready',
            'started'
        ]));
        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('pod', Pod::class);
//
//        $relations
//            ->belongsToMany('event', Event::class)
//            ->through('incident_event');
//
//        $relations->belongsToMany('contact', Contact::class)
//            ->through('incident_contact');
//
//        $relations->hasMany('incident_contact', IncidentContact::class);
//        $relations->hasMany('incident_history', IncidentHistory::class);
    }
}
