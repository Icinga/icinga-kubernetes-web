<?php

/* Icinga for Kubernetes Web | (c) 2024 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Cluster extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid',
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->hasMany('config_map', ConfigMap::class);

        $relations->hasMany('cron_job', CronJob::class);

        $relations->hasMany('daemon_set', DaemonSet::class);

        $relations->hasMany('deployment', Deployment::class);

        $relations->hasMany('endpoint_slice', EndpointSlice::class);

        $relations->hasMany('event', Event::class);

        $relations->hasMany('ingress', Ingress::class);

        $relations->hasMany('job', Job::class);

        $relations->hasMany('namespace', NamespaceModel::class);

        $relations->hasMany('node', Node::class);

        $relations->hasMany('persistent_volume', PersistentVolume::class);

        $relations->hasMany('pod', Pod::class);

        $relations->hasMany('pvc', PersistentVolumeClaim::class);

        $relations->hasMany('replica_set', ReplicaSet::class);

        $relations->hasMany('secret', Secret::class);

        $relations->hasMany('service', Service::class);

        $relations->hasMany('stateful_set', StatefulSet::class);
    }

    public function getColumnDefinitions(): array
    {
        return [
            'name' => $this->translate('Name'),
        ];
    }

    public function getColumns(): array
    {
        return [
            'uuid',
            'name',
        ];
    }

    public function getDefaultSort(): array
    {
        return ['cluster.name desc'];
    }

    public function getKeyName(): array
    {
        return ['uuid'];
    }

    public function getSearchColumns(): array
    {
        return ['name'];
    }

    public function getTableName(): string
    {
        return 'cluster';
    }
}
