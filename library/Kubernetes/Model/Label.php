<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Label extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations
            ->belongsToMany('event', Event::class)
            ->through('resource_label')
            ->setTargetCandidateKey('reference_uuid')
            ->setTargetForeignKey('resource_uuid')
            ->setJoinType('LEFT');

        $relations
            ->belongsToMany('config_map', ConfigMap::class)
            ->through('config_map_label');

        $relations
            ->belongsToMany('cron_job', CronJob::class)
            ->through('cron_job_label');

        $relations
            ->belongsToMany('daemon_set', DaemonSet::class)
            ->through('daemon_set_label');

        $relations
            ->belongsToMany('deployment', Deployment::class)
            ->through('deployment_label');

        $relations
            ->belongsToMany('endpoint_slice', EndpointSlice::class)
            ->through('endpoint_slice_label');

        $relations
            ->belongsToMany('job', Job::class)
            ->through('job_label');

        $relations
            ->belongsToMany('namespace', NamespaceModel::class)
            ->through('namespace_label');

        $relations
            ->belongsToMany('node', Node::class)
            ->through('node_label');

        $relations
            ->belongsToMany('pod', Pod::class)
            ->through('pod_label');

        $relations
            ->belongsToMany('pvc', PersistentVolumeClaim::class)
            ->through('pvc_label');

        $relations
            ->belongsToMany('replica_set', ReplicaSet::class)
            ->through('replica_set_label');

        $relations
            ->belongsToMany('secret', Secret::class)
            ->through('secret_label');

        $relations
            ->belongsToMany('service', Service::class)
            ->through('service_label');

        $relations
            ->belongsToMany('stateful_set', StatefulSet::class)
            ->through('stateful_set_label');

        $relations
            ->belongsToMany('ingress', Ingress::class)
            ->through('ingress_label');

        $relations
            ->belongsToMany('persistent_volume', PersistentVolume::class)
            ->through('persistent_volume_label');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'name'  => $this->translate('Name'),
            'value' => $this->translate('Value')
        ];
    }

    public function getColumns(): array
    {
        return [
            'name',
            'value'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['label.name'];
    }

    public function getKeyName(): array
    {
        return ['uuid'];
    }

    public function getTableName(): string
    {
        return 'label';
    }
}
