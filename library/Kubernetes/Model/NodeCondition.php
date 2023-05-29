<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class NodeCondition extends Model
{
    public function getTableName()
    {
        return 'node_condition';
    }

    public function getKeyName()
    {
        return ['node_id', 'type'];
    }

    public function getColumns()
    {
        return [
            'status',
            'last_heartbeat',
            'last_transition',
            'message',
            'reason'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'type'            => t('Type'),
            'status'          => t('Status'),
            'last_heartbeat'      => t('Last Heartbeat'),
            'last_transition' => t('Last Transition'),
            'message'         => t('Message'),
            'reason'          => t('Reason')
        ];
    }
//
//    public function getSearchColumns()
//    {
//        return ['severity'];
//    }
//
    public function getDefaultSort()
    {
        return ['last_transition desc'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'node_id'
        ]));
        $behaviors->add(new MillisecondTimestamp([
            'last_heartbeat',
            'last_transition'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('node', Node::class);
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
