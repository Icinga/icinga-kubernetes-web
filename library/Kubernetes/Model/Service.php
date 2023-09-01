<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Service extends Model
{
    public function getTableName()
    {
        return 'service';
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
            'type',
            'cluster_ip',
            'cluster_ips',
            'external_ips',
            'session_affinity',
            'external_name',
            'external_traffic_policy',
            'health_check_node_port',
            'publish_not_ready_addresses',
            'ip_families',
            'ip_family_policy',
            'allocate_load_balancer_node_ports',
            'load_balancer_class',
            'internal_traffic_policy',
            'created'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'  => t('Namespace'),
            'name'       => t('Name'),
            'type'       => t('Type'),
            'cluster_ip' => t('Cluster IP'),
            'created'    => t('Created At')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'created desc';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(
            new Binary([
                'id'
            ])
        );
        $behaviors->add(
            new MillisecondTimestamp([
                'created'
            ])
        );
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsToMany('label', Label::class)
            ->through('service_label');

        $relations
            ->belongsToMany('selector', Selector::class)
            ->through('service_selector');

        $relations
            ->hasMany('service_port', ServicePort::class);

        $relations
            ->hasMany('service_condition', ServiceCondition::class);
    }
}
