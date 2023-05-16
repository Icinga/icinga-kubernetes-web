<?php

/* Icinga Kubernetes Web | (c) 2023 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Kubernetes\Model;

use ipl\Orm\Behavior\Binary;
use ipl\Orm\Behavior\MillisecondTimestamp;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class NamespaceModel extends Model
{
    public const PHASE_ACTIVE = 'active';

    public const PHASE_TERMINATING = 'terminating';

    public function getTableName()
    {
        return 'namespace';
    }

    public function getKeyName()
    {
        return ['id'];
    }

    public function getColumns()
    {
        return [
            'id',
            'namespace',
            'name',
            'uid',
            'resource_version',
            'phase',
            'created'
        ];
    }

    public function getColumnDefinitions()
    {
        return [
            'namespace' => t('Namespace'),
            'name'      => t('Name'),
            'phase'     => t('Phase'),
            'created'   => t('Created At')
        ];
    }

    public function getDefaultSort()
    {
        return ['created desc'];
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $behaviors->add(new Binary([
            'id'
        ]));

        $behaviors->add(new MillisecondTimestamp([
            'created'
        ]));
    }

    public function createRelations(Relations $relations)
    {
    }
}
