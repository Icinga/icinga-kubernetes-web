<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Label extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));
    }

    public function createRelations(Relations $relations)
    {
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
    }

    public function getColumnDefinitions()
    {
        return [
            'name'  => $this->translate('Name'),
            'value' => $this->translate('Value')
        ];
    }

    public function getColumns()
    {
        return [
            'name',
            'value'
        ];
    }

    public function getDefaultSort()
    {
        return ['name'];
    }

    public function getKeyName()
    {
        return ['uuid'];
    }

    public function getTableName()
    {
        return 'label';
    }
}
