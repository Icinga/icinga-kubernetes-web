<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Pod extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->hasMany('condition', PodCondition::class);

        $relations->hasMany('pod_volume', PodVolume::class);

        $relations->hasOne('owner', PodOwner::class)->setJoinType('LEFT');

        $relations
            ->belongsToMany('pvc', PersistentVolumeClaim::class)
            ->through(PodPvc::class)
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('claim_name')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('pod_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('pod_annotation');

        $relations->hasMany('sidecar_container', SidecarContainer::class);

        $relations->hasMany('init_container', InitContainer::class);

        $relations->hasMany('container', Container::class);

        $relations->hasMany('container_mount', ContainerMount::class);

        $relations
            ->belongsToMany('deployment', Deployment::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');

        $relations
            ->belongsToMany('replica_set', ReplicaSet::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');

        $relations
            ->belongsToMany('daemon_set', DaemonSet::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');

        $relations
            ->belongsToMany('stateful_set', StatefulSet::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');

        $relations
            ->belongsToMany('job', Job::class)
            ->through('pod_owner')
            ->setTargetCandidateKey('name')
            ->setTargetForeignKey('name')
            ->setCandidateKey('uuid')
            ->setForeignKey('pod_uuid');

        $relations
            ->belongsTo('node', Node::class)
            ->setCandidateKey('node_name')
            ->setForeignKey('name')
            ->setJoinType('LEFT');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'namespace'           => $this->translate('Namespace'),
            'name'                => $this->translate('Name'),
            'uid'                 => $this->translate('UID'),
            'resource_version'    => $this->translate('Resource Version'),
            'node_name'           => $this->translate('Node Name'),
            'nominated_node_name' => $this->translate('Nominated Node Name'),
            'ip'                  => $this->translate('IP'),
            'phase'               => $this->translate('Phase'),
            'icinga_state'        => $this->translate('Icinga State'),
            'icinga_state_reason' => $this->translate('Icinga State Reason'),
            'restart_policy'      => $this->translate('Restart Policy'),
            'cpu_limits'          => $this->translate('CPU Limits'),
            'cpu_requests'        => $this->translate('CPU Requests'),
            'memory_limits'       => $this->translate('Memory Limits'),
            'memory_requests'     => $this->translate('Memory Requests'),
            'reason'              => $this->translate('Phase Reason'),
            'message'             => $this->translate('Phase Message'),
            'qos'                 => $this->translate('Quality of Service'),
            'yaml'                => $this->translate('YAML'),
            'created'             => $this->translate('Created At')
        ];
    }

    public function getColumns(): array
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
            'icinga_state',
            'icinga_state_reason',
            'restart_policy',
            'cpu_limits',
            'cpu_requests',
            'memory_limits',
            'memory_requests',
            'reason',
            'message',
            'qos',
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
        return 'pod';
    }
}
