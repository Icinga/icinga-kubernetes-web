<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Pod extends Model
{
    public const PHASE_PENDING = 'pending';
    public const PHASE_RUNNING = 'running';

    public const PHASE_SUCCEEDED = 'succeeded';

    public const PHASE_FAILED = 'failed';

    public function getTableName()
    {
        return 'pod';
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
            'node_name',
            'nominated_node_name',
            'ip',
            'phase',
            'restart_policy',
            'cpu_limits',
            'cpu_requests',
            'memory_limits',
            'memory_requests',
            'reason',
            'message',
            'qos',
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
        return ['namespace', 'created desc'];
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
        $relations->hasMany('container', Container::class);

        $relations->hasMany('condition', PodCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('pod_label');

        $relations
            ->belongsTo('node', Node::class)
            ->setCandidateKey('node_name')
            ->setForeignKey('name');

        $relations
            ->belongsToMany('stateful_set', StatefulSet::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');
//
//        $relations->belongsToMany('contact', Contact::class)
//            ->through('incident_contact');
//
//        $relations->hasMany('incident_contact', IncidentContact::class);
//        $relations->hasMany('incident_history', IncidentHistory::class);
    }
}
