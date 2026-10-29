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
            'uuid',
            'resource_uuid',
        ]));
    }

    public function createRelations(Relations $relations): void
    {
        $relations
            ->belongsTo('event', Event::class)
            ->setCandidateKey('resource_uuid')
            ->setForeignKey('reference_uuid')
            ->setJoinType('LEFT');

        $relations
            ->belongsTo('config_map', ConfigMap::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('cron_job', CronJob::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('daemon_set', DaemonSet::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('deployment', Deployment::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('endpoint_slice', EndpointSlice::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('job', Job::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('namespace', NamespaceModel::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('node', Node::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('pod', Pod::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('pvc', PersistentVolumeClaim::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('replica_set', ReplicaSet::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('secret', Secret::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('service', Service::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('stateful_set', StatefulSet::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('ingress', Ingress::class)
            ->setCandidateKey('resource_uuid');

        $relations
            ->belongsTo('persistent_volume', PersistentVolume::class)
            ->setCandidateKey('resource_uuid');
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
            'resource_uuid',
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
