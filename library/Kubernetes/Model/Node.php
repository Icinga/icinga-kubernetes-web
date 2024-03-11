<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\I18n\Translation;
use ipl\Orm\Behavior\Binary;
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
        $behaviors->add(new Binary([
            'id'
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
            ->hasMany('pod', Pod::class)
            ->setCandidateKey('name')
            ->setForeignKey('node_name');
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'          => $this->translate('Namespace'),
            'name'               => $this->translate('Name'),
            'uid'                => $this->translate('UID'),
            'resource_version'   => $this->translate('Resource Version'),
            'pod_cidr'           => $this->translate('Pod CIDR'),
            'num_ips'            => $this->translate('Num IPs'),
            'unschedulable'      => $this->translate('Unschedulable'),
            'ready'              => $this->translate('Ready'),
            'cpu_capacity'       => $this->translate('CPU Capacity'),
            'cpu_allocatable'    => $this->translate('CPU Allocatable'),
            'memory_capacity'    => $this->translate('Memory Capacity'),
            'memory_allocatable' => $this->translate('Memory Allocatable'),
            'pod_capacity'       => $this->translate('Pod Capacity'),
            'created'            => $this->translate('Created At')
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
            'created'
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function getKeyName()
    {
        return ['id'];
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
