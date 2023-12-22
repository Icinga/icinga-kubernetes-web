<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\BoolCast;
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
        return ['id'];
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
            'namespace'          => t('Namespace'),
            'name'               => t('Name'),
            'uid'                => t('UID'),
            'resource_version'   => t('Resource Version'),
            'pod_cidr'           => t('Pod CIDR'),
            'num_ips'            => t('Num IPs'),
            'unschedulable'      => t('Unschedulable'),
            'ready'              => t('Ready'),
            'cpu_capacity'       => t('CPU Capacity'),
            'cpu_allocatable'    => t('CPU Allocatable'),
            'memory_capacity'    => t('Memory Capacity'),
            'memory_allocatable' => t('Memory Allocatable'),
            'pod_capacity'       => t('Pod Capacity'),
            'created'            => t('Created At')
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
}
