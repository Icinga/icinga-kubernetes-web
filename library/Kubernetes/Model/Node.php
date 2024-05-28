<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Node extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));

        $behaviors->add(new BoolCast([
            'ready'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->hasMany('condition', NodeCondition::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('node_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('node_annotation');

        $relations
            ->hasMany('pod', Pod::class)
            ->setCandidateKey('name')
            ->setForeignKey('node_name');
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'                 => $this->translate('Namespace'),
            'name'                      => $this->translate('Name'),
            'uid'                       => $this->translate('UID'),
            'resource_version'          => $this->translate('Resource Version'),
            'pod_cidr'                  => $this->translate('Pod CIDR'),
            'num_ips'                   => $this->translate('Num IPs'),
            'unschedulable'             => $this->translate('Unschedulable'),
            'ready'                     => $this->translate('Ready'),
            'cpu_capacity'              => $this->translate('CPU Capacity'),
            'cpu_allocatable'           => $this->translate('CPU Allocatable'),
            'memory_capacity'           => $this->translate('Memory Capacity'),
            'memory_allocatable'        => $this->translate('Memory Allocatable'),
            'pod_capacity'              => $this->translate('Pod Capacity'),
            'roles'                     => $this->translate('Roles'),
            'machine_id'                => $this->translate('Machine ID'),
            'system_uuid'               => $this->translate('System UUID'),
            'boot_id'                   => $this->translate('Boot ID'),
            'kernel_version'            => $this->translate('Kernel Version'),
            'os_image'                  => $this->translate('OS Image'),
            'operating_system'          => $this->translate('Operating System'),
            'architecture'              => $this->translate('Architecture'),
            'container_runtime_version' => $this->translate('Container Runtime Version'),
            'kubelet_version'           => $this->translate('Kubelet Version'),
            'kube_proxy_version'        => $this->translate('KubeProxy Version'),
            'icinga_state'              => $this->translate('Icinga State'),
            'icinga_state_reason'       => $this->translate('Icinga State Reason'),
            'created'                   => $this->translate('Created At')
        ];
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
            'roles',
            'machine_id',
            'system_uuid',
            'boot_id',
            'kernel_version',
            'os_image',
            'operating_system',
            'architecture',
            'container_runtime_version',
            'kubelet_version',
            'kube_proxy_version',
            'icinga_state',
            'icinga_state_reason',
            'created'
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function getKeyName()
    {
        return ['uuid'];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getTableName()
    {
        return 'node';
    }
}
