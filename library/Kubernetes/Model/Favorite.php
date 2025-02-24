<?php

/* Icinga for Kubernetes Web | (c) 2025 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Favorite extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'resource_uuid'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('cron_job', CronJob::class)->setJoinType('LEFT');

        $relations->belongsTo('daemon_set', DaemonSet::class)->setJoinType('LEFT');

        $relations->belongsTo('deployment', Deployment::class)->setJoinType('LEFT');

        $relations->belongsTo('ingress', Ingress::class)->setJoinType('LEFT');

        $relations->belongsTo('job', Job::class)->setJoinType('LEFT');

        $relations->belongsTo('namespace', NamespaceModel::class)->setJoinType('LEFT');

        $relations->belongsTo('node', Node::class)->setJoinType('LEFT');

        $relations->belongsTo('persistent_volume', PersistentVolume::class)->setJoinType('LEFT');

        $relations->belongsTo('pvc', PersistentVolumeClaim::class)->setJoinType('LEFT');

        $relations->belongsTo('pod', Pod::class)->setJoinType('LEFT');

        $relations->belongsTo('replica_set', ReplicaSet::class)->setJoinType('LEFT');

        $relations->belongsTo('service', Service::class)->setJoinType('LEFT');

        $relations->belongsTo('stateful_set', StatefulSet::class)->setJoinType('LEFT');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'resource_uuid' => $this->translate('Resource UUID'),
            'kind'          => $this->translate('Resource Kind'),
            'username'      => $this->translate('Username'),
            'priority'      => $this->translate('Priority')
        ];
    }

    public function getColumns(): array
    {
        return [
            'resource_uuid',
            'kind',
            'username',
            'priority'
        ];
    }

    public function getKeyName(): array
    {
        return ['resource_uuid', 'username'];
    }

    public function getTableName(): string
    {
        return 'favorite';
    }
}
