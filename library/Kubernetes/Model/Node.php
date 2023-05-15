<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Node extends Model
{
    public function getTableName()
    {
        return 'node';
    }

    public function getKeyName()
    {
        return ['namespace', 'name'];
    }

    public function getColumns()
    {
        return [
            'namespace',
            'name',
            'uid',
            'resource_version',
            'pod_cidr',
            'num_ips',
            'unschedulable',
            'ready',
            'cpu_capacity',
            'cpu_allocatable',
            'memory_capacity',
            'memory_allocatable',
            'pod_capacity',
            'created'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'name'  => t('Name'),
            'value' => t('Value')
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
    }
//
//    public function getSearchColumns()
//    {
//        return ['severity'];
//    }
//
//    public function getDefaultSort()
//    {
//        return ['last_transition desc'];
//    }

    public function createBehaviors(Behaviors $behaviors)
    {
//        $behaviors->add(new Binary([
//            'id'
//        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->hasMany('pod', Pod::class)
            ->setCandidateKey('name')
            ->setForeignKey('node_name');
//
//        $relations->belongsToMany('contact', Contact::class)
//            ->through('incident_contact');
//
//        $relations->hasMany('incident_contact', IncidentContact::class);
//        $relations->hasMany('incident_history', IncidentHistory::class);
    }
}
