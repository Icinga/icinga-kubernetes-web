<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class Event extends Model
{
    use Translation;

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Uuid([
            'uuid',
            'cluster_uuid',
            'reference_uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'first_seen',
            'last_seen',
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsToOne('cluster', Cluster::class);

        $relations
            ->belongsToMany('label', Label::class)
            ->through('resource_label')
            ->setCandidateKey('reference_uuid')
            ->setForeignKey('resource_uuid')
            ->setJoinType('LEFT');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('resource_annotation')
            ->setCandidateKey('reference_uuid')
            ->setForeignKey('resource_uuid')
            ->setJoinType('LEFT');
    }

    public function getColumnDefinitions(): array
    {
        return [
            'namespace'            => $this->translate('Namespace'),
            'name'                 => $this->translate('Name'),
            'uid'                  => $this->translate('UID'),
            'resource_version'     => $this->translate('Resource Version'),
            'reporting_controller' => $this->translate('Reporting Controller'),
            'reporting_instance'   => $this->translate('Reporting Instance'),
            'action'               => $this->translate('Action'),
            'reason'               => $this->translate('Reason'),
            'note'                 => $this->translate('Note'),
            'type'                 => $this->translate('Type'),
            'reference_kind'       => $this->translate('Referent Kind'),
            'reference_namespace'  => $this->translate('Referent Namespace'),
            'reference_name'       => $this->translate('Referent Name'),
            'first_seen'           => $this->translate('First Seen'),
            'last_seen'            => $this->translate('Last Seen'),
            'count'                => $this->translate('Count'),
            'created'              => $this->translate('Created')
        ];
    }

    public function getColumns(): array
    {
        return [
            'cluster_uuid',
            'reference_uuid',
            'namespace',
            'name',
            'uid',
            'resource_version',
            'reporting_controller',
            'reporting_instance',
            'action',
            'reason',
            'note',
            'type',
            'reference_kind',
            'reference_namespace',
            'reference_name',
            'first_seen',
            'last_seen',
            'count',
            'yaml',
            'created'
        ];
    }

    public function getDefaultSort(): array
    {
        return ['event.last_seen desc'];
    }

    public function getKeyName(): string
    {
        return 'uuid';
    }

    public function getTableName(): string
    {
        return 'event';
    }
}
