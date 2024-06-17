<?php

/* Icinga for Kubernetes Web | (c) 2023 Icinga GmbH | AGPLv3 */

namespace Icinga\Module\Kubernetes\Model;

use Icinga\Module\Kubernetes\Model\Behavior\Uuid;
use ipl\I18n\Translation;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class ConfigMap extends Model
{
    use Translation;

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
            ->belongsToMany('data', Data::class)
            ->through('config_map_data');

        $relations
            ->belongsToMany('label', Label::class)
            ->through('config_map_label');

        $relations
            ->belongsToMany('annotation', Annotation::class)
            ->through('config_map_annotation');
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace'        => $this->translate('Namespace'),
            'name'             => $this->translate('Name'),
            'uid'              => $this->translate('UID'),
            'resource_version' => $this->translate('Resource Version'),
            'immutable'        => $this->translate('Immutable'),
            'yaml'             => $this->translate('YAML'),
            'created'          => $this->translate('Created At')
        ];
    }

    public function getColumns()
    {
        return [
            'uuid',
            'namespace',
            'name',
            'uid',
            'resource_version',
            'immutable',
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
        return 'config_map';
    }
}
