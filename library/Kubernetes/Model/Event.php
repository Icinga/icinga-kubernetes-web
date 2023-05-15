<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Event extends Model
{
    public const STATE_WAITING = 'waiting';
    public const STATE_RUNNING = 'running';

    public const STATE_TERMINATED = 'terminated';

    public function getTableName()
    {
        return 'event';
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
            'reporting_controller',
            'reporting_instance',
            'action',
            'reason',
            'note',
            'type',
            'reference_kind',
            'reference_namespace',
            'reference_name',
            'created'
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
    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));
        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
//        $relations->belongsTo('pod', Pod::class);
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
