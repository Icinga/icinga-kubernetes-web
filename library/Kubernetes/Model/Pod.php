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
    public const PHASE_FAILED = 'failed';

    public const PHASE_PENDING = 'pending';

    public const PHASE_RUNNING = 'running';

    public const PHASE_SUCCEEDED = 'succeeded';

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

    public function getColumnDefinitions()
    {
        return [
            'namespace'           => t('Namespace'),
            'name'                => t('Name'),
            'uid'                 => t('UID'),
            'resource_version'    => t('Resource Version'),
            'node_name'           => t('Node Name'),
            'nominated_node_name' => t('Nominated Node Name'),
            'ip'                  => t('IP'),
            'phase'               => t('Phase'),
            'restart_policy'      => t('Restart Policy'),
            'cpu_limits'          => t('CPU Limits'),
            'cpu_requests'        => t('CPU Requests'),
            'memory_limits'       => t('Memory Limits'),
            'memory_requests'     => t('Memory Requests'),
            'reason'              => t('Phase Reason'),
            'message'             => t('Phase Message'),
            'qos'                 => t('Quality of Service'),
            'created'             => t('Created At')
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function getSearchColumns()
    {
        return ['name'];
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
        $relations->hasMany('condition', PodCondition::class);

        $relations->hasMany('pod_volume', PodVolume::class);

        $relations
            ->belongsToMany('pvc', PersistentVolumeClaim::class)
            ->through(PodPvc::class)
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('claim_name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('pod_label');

        $relations->hasMany('container', Container::class);

        $relations->hasMany('container_mount', ContainerMount::class);

        $relations
            ->belongsToMany('deployment', Deployment::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');

        $relations
            ->belongsToMany('replica_set', ReplicaSet::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');

        $relations
            ->belongsToMany('daemon_set', DaemonSet::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');

        $relations
            ->belongsToMany('stateful_set', StatefulSet::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');

        $relations
            ->belongsToMany('job', Job::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('id')
            ->setForeignKey('pod_id');

        $relations
            ->belongsTo('node', Node::class)
            ->setCandidateKey('node_name')
            ->setForeignKey('name')
            ->setJoinType('LEFT');
    }
}
