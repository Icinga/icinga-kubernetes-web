<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class NamespaceModel extends Model
{
    use Translation;

    public const PHASE_ACTIVE = 'Active';

    public const PHASE_TERMINATING = 'Terminating';

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Uuid([
            'uuid'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
        $relations
            ->belongsToMany('label', Label::class)
            ->through('namespace_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('namespace_annotation');
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'        => $this->translate('Namespace'),
            'name'             => $this->translate('Name'),
            'uid'              => $this->translate('UID'),
            'resource_version' => $this->translate('Resource Version'),
            'phase'            => $this->translate('Phase'),
            'yaml'             => $this->translate('YAML'),
            'created'          => $this->translate('Created At')
        ];
    }

    public function getColumns()
    {
        return [
            'namespace',
            'name',
            'uid',
            'resource_version',
            'phase',
            'yaml',
            'created'
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function getKeyName()
    {
        return ['uuid'];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getTableName()
    {
        return 'namespace';
    }
}
