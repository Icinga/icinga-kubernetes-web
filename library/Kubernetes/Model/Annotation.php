<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Annotation extends Model
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
            ->belongsToMany('pod', Pod::class)
            ->through('pod_annotation');

        $relations
            ->belongsToMany('replica_set', ReplicaSet::class)
            ->through('replica_set_annotation');

        $relations
            ->belongsToMany('deployment', Deployment::class)
            ->through('deployment_annotation');

        $relations
            ->belongsToMany('daemon_set', DaemonSet::class)
            ->through('daemon_set_annotation');

        $relations
            ->belongsToMany('stateful_set', StatefulSet::class)
            ->through('stateful_set_annotation');

        $relations
            ->belongsToMany('namespace', NamespaceModel::class)
            ->through('namespace_annotation');

        $relations
            ->belongsToMany('node', Node::class)
            ->through('node_annotation');

        $relations
            ->belongsToMany('secret', Secret::class)
            ->through('secret_annotation');

        $relations
            ->belongsToMany('config_map', ConfigMap::class)
            ->through('config_map_annotation');

        $relations
            ->belongsToMany('service', Service::class)
            ->through('service_annotation');

        $relations
            ->belongsToMany('job', Job::class)
            ->through('job_annotation');

        $relations
            ->belongsToMany('cron_job', CronJob::class)
            ->through('cron_job_annotation');

        $relations
            ->belongsToMany('pvc', PersistentVolumeClaim::class)
            ->through('pvc_annotation');
    }

    public function getColumnDefinitions()
    {
        return [
            'name'  => $this->translate('Name'),
            'value' => $this->translate('Value'),
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
        return 'annotation';
    }
}
