<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\BoolCast;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Service extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid',
            'cluster_uuid'
        ]));

        $behaviors->add(new BoolCast([
            'publish_not_ready_addresses',
            'allocate_load_balancer_node_ports'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsToOne('cluster', Cluster::class);

        $relations->hasMany('condition', ServiceCondition::class);

        $relations->hasMany('port', ServicePort::class);

        $relations
            ->belongsToMany('selector', Selector::class)
            ->through('service_selector');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('service_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('service_annotation');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('service_pod');

        $relations->hasMany('favorite', Favorite::class)
            ->setForeignKey('resource_uuid')
            ->setJoinType('LEFT');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'namespace'                         => $this->translate('Namespace'),
            'name'                              => $this->translate('Name'),
            'uid'                               => $this->translate('UID'),
            'resource_version'                  => $this->translate('Resource Version'),
            'type'                              => $this->translate('Type'),
            'cluster_ip'                        => $this->translate('Cluster IP'),
            'cluster_ips'                       => $this->translate('Cluster IPs'),
            'external_ips'                      => $this->translate('External IPs'),
            'session_affinity'                  => $this->translate('Session Affinity'),
            'external_name'                     => $this->translate('External Name'),
            'external_traffic_policy'           => $this->translate('External Traffic Policy'),
            'health_check_node_port'            => $this->translate('Health Check Node Port'),
            'publish_not_ready_addresses'       => $this->translate('Publish Not Ready Addresses'),
            'ip_families'                       => $this->translate('IP Families'),
            'ip_family_policy'                  => $this->translate('IP Family Policy'),
            'allocate_load_balancer_node_ports' => $this->translate('Allocated Load Balancer Node Ports'),
            'load_balancer_class'               => $this->translate('Load Balancer Class'),
            'internal_traffic_policy'           => $this->translate('Internal Traffic Policy'),
            'icinga_state'                      => $this->translate('Icinga State'),
            'icinga_state_reason'               => $this->translate('Icinga State Reason'),
            'yaml'                              => $this->translate('YAML'),
            'created'                           => $this->translate('Created At')
        ];
    }

    public function getColumns(): array
    {
        return [
            'cluster_uuid',
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
            'icinga_state',
            'icinga_state_reason',
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
        return 'service';
    }
}
